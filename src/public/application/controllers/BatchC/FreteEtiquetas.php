<?php
/*

Baixas as etiquetas  no frete rápido
 * 
 * ***************************** NÂO É MAIS EXECUTADO **********************************

*/   
 class FreteEtiquetas extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders','myorders');
		$this->load->model('model_quotes_ship','myquotesship');
		$this->load->model('model_clients','myclients');
		$this->load->model('Model_freights','myfreights');
		$this->load->model('Model_frete_ocorrencias','myfreteocorrencias');
		$this->load->model('model_settings','mysettings');

    }
	
    function run($id=null,$params=null)
    {
    	
		die; /// nao e mais executado
    	/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->listaEtiquetas();
		
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function pathServer($folder) 
	{
		$serverpath = $_SERVER['SCRIPT_FILENAME'];
		$pos = strpos($serverpath,'assets');
		$serverpath = substr($serverpath,0,$pos);
		$targetDir = $serverpath . 'assets/images/'.$folder.'/'; 
		if (!file_exists($targetDir)) {
			// cria o diretorio para receber as etiquetas  
    		@mkdir($targetDir);
		}
		return $targetDir; 
	}

	function listaEtiquetas() {
		
		$pathEtiquetas = $this->pathServer("etiquetas");
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pego as informações de token de conexao com o frete rápido
		$setting = $this->mysettings->getSettingDatabyName('token_frete_rapido_master'); 
		if ($setting == false) {
			$this->log_data('batch',$log_name,'Falta o cadastro do parametro token_frete_rapido_master',"E");
			$this->gravaFimJob();
			return ;
		}
		$token_fr = $setting['value'];
		
		// pego somente o fretes sem etiqueta ou vencidas
		$fretes =$this->myfreights->getFreightsSemEtiqueta();
		if (!isset($fretes)) {
			$this->log_data('batch',$log_name,'Nenhum frete em andamento',"I");
			$this->gravaFimJob();
			return ;
		}
		
		$ind=0;
		echo 'Contratando '.count($fretes)." fretes\n";
		while($ind <count($fretes)) {
		//foreach ($fretes as $frete) {
			$frete = $fretes[$ind++];
			// $ocorrencias = $this->myfreteocorrencias->getFreteOcorrenciasDataByFreightsId($frete['id']);
			//pego as informações da orders 
			$order = $this->myorders->getOrdersData(0,$frete['order_id']);
			
			if (($frete['codigo_rastreio'] == "FR200622JCXMS")  
			 	|| ($frete['codigo_rastreio'] =="FR200623EAM5K")
				) {
				echo "pulando ". $frete['codigo_rastreio']. " do pedido ".$frete['order_id']."\n";
				continue;
			}
			echo 'fretes da ordem id '.$frete['order_id']."\n"; 
			 
			$rastreio = Array (Array('id_frete' => $frete['codigo_rastreio']));
			$rastreio = json_encode($rastreio,JSON_UNESCAPED_UNICODE);
	    	$rastreio = stripslashes($rastreio);

			$url = 'https://freterapido.com/api/external/embarcador/v1/labels';
			// pego o link da etiqueta Termica  layout = 2
			$params = '?token='.$token_fr.'&layout=2'; 
			
			$data = $this->executaPostEtiquetas($url.$params, $rastreio);
			
			// die; 
			$retorno_fr = $data['content'];
			
			//var_dump($retorno_fr);
			if (!($data['httpcode']=="200")) {
				echo 'Erro no pedido de geração de etiqueta termica'."\n";
				echo " http: ".$url.$params. "\n";
				echo " httpcode : ".$data['httpcode']. "\n";
				echo " enviado: ".$rastreio."\n";
				echo " recebido; ".print_r($data['content'],true);
				$this->log_data('batch',$log_name, 'ERRO - Etiqueta térmica. httpcode: '.$data['httpcode'].' RESPOSTA FR: '.print_r($data['content'],true).' DADOS ENVIADOS:'.$rastreio,"E");
				//mato para forcar a parada do job no dia de hoje
				die;
			} 
			
			// $retorno_fr = '[{"origem":{"cnpj":"30120829000199","endereco":{"cep":"88025300","rua":"RUA RUI BARBOSA","numero":"46","bairro":"AGRONOMICA","cidade":"Florian\u00f3polis","uf":"SC"}},"id_frete":"FR200518IEXR3","plp":"https:\/\/s3.amazonaws.com\/prod.freterapido.uploads\/correios\/vouchers\/voucher-5267-5ec293cbf2f469-25793722.pdf?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAJJKNUFFWI33F6GPA%2F20200518%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20200518T135540Z&X-Amz-SignedHeaders=host&X-Amz-Expires=86400&X-Amz-Signature=680c6b6a66cc75bebf2d95cf2c35d3b2267e28de2b1a2f8e692e692f2df1fe7f","etiqueta":"https:\/\/s3.amazonaws.com\/prod.freterapido.uploads\/correios\/labels\/etiquetas-5267-5ec293cbf2f469-25793722.pdf?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAJJKNUFFWI33F6GPA%2F20200518%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20200518T135540Z&X-Amz-SignedHeaders=host&X-Amz-Expires=86400&X-Amz-Signature=b3d936b7dff01ea277777e6cfc2bb418714b0242176d03ce5fb8f7ccfcf8ff15"}]';
			
			$retorno_fr = json_decode($retorno_fr,true);
			echo print_r($retorno_fr,true);
			if (array_key_exists('erro', $retorno_fr[0])) {
				if ($retorno_fr[0]['erro'] == "Erro ao obter etiqueta") {
					$ind--; //tenta de novo
					continue;
				}else {
					echo 'Erro no pedido de geração de etiqueta térmica. Rastreio='.$frete['codigo_rastreio']."\n";
					echo " http: ".$url.$params. "\n";
					echo " httpcode : ".$data['httpcode']. "\n";
					echo " enviado: ".$rastreio."\n";
					echo " recebido; ".print_r($data['content'],true);
					$this->log_data('batch',$log_name, "ERRO - Etiqueta térmica. httpcode : ".$data['httpcode']." Rastreio=".$frete['codigo_rastreio']." recebido:".print_r($data['content'],true)." DADOS ENVIADOS:".$rastreio,"E");
					die;
				}
				
			} 
			if (array_key_exists('avisos', $retorno_fr[0])) {
				echo 'Erro no pedido de geração de etiqueta térmica. Rastreio='.$frete['codigo_rastreio'].' Msg: '.print_r($retorno_fr[0]['avisos'],true)."\n";
				echo " http: ".$url.$params. "\n";
				echo " httpcode : ".$data['httpcode']. "\n";
				echo " enviado: ".$rastreio."\n";
				echo " recebido; ".print_r($data['content'],true);
				$this->log_data('batch',$log_name, 'ERRO - Etiqueta térmica. Rastreio='.$frete['codigo_rastreio'].' Msg:'.print_r($retorno_fr[0]['avisos'],true).' DADOS ENVIADOS:'.$rastreio,"E");
				die;
			} 
			$etiquetaPLP = '';
			if ($frete['ship_company'] == 'CORREIOS') {
				$etiquetaPLP = $retorno_fr[0]['plp'];
				copy($etiquetaPLP, FCPATH.$pathEtiquetas."P_".$frete['order_id']."_PLP.pdf");
				if (!file_exists(FCPATH.$pathEtiquetas."P_".$frete['order_id']."_PLP.pdf")) {
					echo 'Erro ao copiar o arquivo da frete Rápido '.$etiquetaPLP.' para '.$pathEtiquetas."P_".$frete['order_id']."_PLP.pdf"."\n";
					$this->log_data('batch',$log_name, 'Erro ao copiar o arquivo da frete Rápido '.$etiquetaTermica.' para '.$pathEtiquetas."P_".$frete['order_id']."_PLP.pdf","E");
					die;
				}
				$etiquetaPLP = base_url().$pathEtiquetas."P_".$frete['order_id']."_PLP.pdf";	
			}
			
			$etiquetaTermica = $retorno_fr[0]['etiqueta'];
			copy($etiquetaTermica, FCPATH.$pathEtiquetas."P_".$frete['order_id']."_Termica.pdf");
			if (!file_exists(FCPATH.$pathEtiquetas."P_".$frete['order_id']."_Termica.pdf")) {
				echo 'Erro ao copiar o arquivo da frete Rápido '.$etiquetaTermica.' para '.$pathEtiquetas."P_".$frete['order_id']."_Termica.pdf"."\n";
				$this->log_data('batch',$log_name, 'Erro ao copiar o arquivo da frete Rápido '.$etiquetaTermica.' para '.$pathEtiquetas."P_".$frete['order_id']."_Termica.pdf","E");
				die;
			}
			$etiquetaTermica = base_url().$pathEtiquetas."P_".$frete['order_id']."_Termica.pdf";
			
			$etiquetaA4 ='';
			if ($frete['ship_company'] != 'CORREIOS') {  //Infelizmente, para os correios só dá para ter um tipo de etiqueta
			    // pego o link da etiqueta A4 primeiro  layout = 1 
				$params = '?token='.$token_fr.'&layout=1'; 
				
				$data = $this->executaPostEtiquetas($url.$params, $rastreio);
				
				// die; 
				$retorno_fr = $data['content'];
				//var_dump($retorno_fr);
				if (!($data['httpcode']=="200")) {
					echo 'Erro no pedido de geração de etiqueta a4'."\n";
					var_dump($retorno_fr);
					$this->log_data('batch',$log_name, 'ERRO - Etiqueta A4. httpcode: '.$data['httpcode'].' RESPOSTA FR: '.print_r($data['content'],true).' DADOS ENVIADOS:'.$rastreio,"E");
					die;
				} 
				
				$retorno_fr = json_decode($retorno_fr,true);
				if (array_key_exists('erro', $retorno_fr[0])) {
					echo 'Erro no pedido de geração de etiqueta a4. Rastreio='.$frete['codigo_rastreio'].' Msg: '.$retorno_fr[0]['erro']."\n";
					// var_dump($retorno_fr);
					$this->log_data('batch',$log_name, 'ERRO - Etiqueta A4. Rastreio='.$frete['codigo_rastreio'].' Msg:'.$retorno_fr[0]['erro'].' DADOS ENVIADOS:'.$rastreio,"E");
					die;
				} 
				
				$etiquetaA4 = $retorno_fr[0]['etiqueta'];
			
			    copy($etiquetaA4, FCPATH.$pathEtiquetas."P_".$frete['order_id']."_A4.pdf");
				if (!file_exists(FCPATH.$pathEtiquetas."P_".$frete['order_id']."_A4.pdf")) {
					echo 'Erro ao copiar o arquivo da frete Rápido '.$etiquetaA4.' para '.$pathEtiquetas."P_".$frete['order_id']."_A4.pdf"."\n";
					$this->log_data('batch',$log_name, 'Erro ao copiar o arquivo da frete Rápido '.$etiquetaA4.' para '.$pathEtiquetas."P_".$frete['order_id']."_A4.pdf","E");
					die;
				}
				$etiquetaA4 = base_url().$pathEtiquetas."P_".$frete['order_id']."_A4.pdf";
			}
				//echo 'http='.$etiquetaA4."\n"; 
			//echo 'http='.$etiquetaTermica."\n"; 
			$frete['link_etiqueta_a4'] = $etiquetaA4;
			$frete['link_etiqueta_termica'] = $etiquetaTermica;
			$frete['link_plp'] = $etiquetaPLP;
			$frete['data_etiqueta'] = date('Y-m-d H:i:s');
			if (!($this->myfreights->replace($frete))) {
			    echo 'Erro ao gravar frete '.print_r($frete,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO ao gravar o frete '.print_r($frete,true),"E");
				die;
			} 
			
			echo 'etiquetas gravadas'."\n"; 
			
 		} 
	
	}

	
    function executaPostEtiquetas($url, $post_data){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_CONNECTTIMEOUT => 240000,      // timeout on connect
	        CURLOPT_TIMEOUT        => 240000,      // timeout on response
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array('Content-Type:application/json'),
	        CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

}

?>
