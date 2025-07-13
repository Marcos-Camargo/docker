<?php
/*
 
Verifica quais ordens contrataram o frete e envia para o Bling

*/   
class BlingRastreio extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders','myorders');
		$this->load->model('model_nfes','mynfes');
		$this->load->model('model_quotes_ship','myquotesship');
		$this->load->model('Model_freights','myfreights');

    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->envioRastreioBling();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getBlingKeys() {
		$apikeys = Array();
		$sql = "select * from stores_mkts_linked where id_integration = 13";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}
		return $apikeys;
	}
	
    function envioRastreioBling()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';  
		
		$ordens_andamento =$this->myorders->getOrdensByPaidStatus($paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de rastreio para o Bling');
			return ;
		}
		
		//$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$apikeys = $this->getBlingKeys();
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			// Pulo as ordens da B2W  
			if ($order['origin'] == 'B2W') {
				echo "B2W - Pulei \n"; 
				continue; 
			}
			// Pulo as ordens do CAR
			if ($order['origin'] == 'CAR') {
				echo "CAR - Pulei \n"; 
				continue; 
			}
			// Pulo as ordens do VIA
			if ($order['origin'] == 'VIA') {
				echo "VIA - Pulei \n"; 
				continue; 
			}
			// Pulo as ordens do ML
			if ($order['origin'] == 'ML') {
				echo "ML - Pulei \n"; 
				continue; 
			}
			//pego a cotação de frete
			/*
			$quote = $this->myquotesship->getQuoteShipsData($order['quote_ship_id']); 
			if (!(isset($quote))) {
				// Sem cotação, não deveria aconter
				echo "Sem cotação\n"; 
				$this->log_data('batch',$log_name,'ERRO: Cotação '.$order['quote_ship_id'].'do pedido'.$order['id'].' não encontrado ',"E");
				$order['paid_status'] = 101; // Precisa contratar o frete manualmente
				$this->myorders->updateByOrigin($order['id'],$order);
				continue;
			}
			 * 
			 */
			//var_dump($data);
			$frete=$this->myfreights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E" );
			     $order['paid_status'] = 101; // Precisa contratar o frete manualmente
				$this->myorders->updateByOrigin($order['id'],$order);
				continue;
			}
			$frete = $frete[0];
			
			//criar serviço no bling 
			//$dadosCotacao = json_decode($quote['retorno'],true);
			
			$xml_entrega =              "<logistica>";
			$xml_entrega = $xml_entrega." <descricao>Logistica FR ".$order['bill_no']."</descricao>";
			$xml_entrega = $xml_entrega." <servicos>";
  			$xml_entrega = $xml_entrega."  <servico>";
   			//$xml_entrega = $xml_entrega."   <id_servico>0</id_servico>"; // 0 CRIA NOVO
      		$xml_entrega = $xml_entrega."   <descricao>Entrega do pedido ".$order['id']."</descricao>";
            $xml_entrega = $xml_entrega."   <frete_item>".$frete['ship_value']."</frete_item>";
         	//$xml_entrega = $xml_entrega."   <est_entrega>".$dadosCotacao['transportadoras'][0]['prazo_entrega']."</est_entrega>";
			$xml_entrega = $xml_entrega."   <est_entrega>".$frete['prazoprevisto']."</est_entrega>";
         	$xml_entrega = $xml_entrega."   <codigo />";
         	$xml_entrega = $xml_entrega."   <aliases>";
            $xml_entrega = $xml_entrega."    <alias>Entregadopedido".$order['id']."</alias>";
         	$xml_entrega = $xml_entrega."   </aliases>";
         	$xml_entrega = $xml_entrega."   <id_transportadora>7283638859</id_transportadora>"; //Código da frete rápido
      		$xml_entrega = $xml_entrega."  </servico>";
			$xml_entrega = $xml_entrega." </servicos>";
			$xml_entrega = $xml_entrega."</logistica>";
			
			$url = 'https://bling.com.br/Api/v2/logistica/servicos/json';
			
			$apikey = $apikeys[$order['origin']];
			$dataEntrega = array(
			    'apikey' => $apikey,
			    'xml' => rawurlencode($xml_entrega)
			);
			
			/* removido pois o bling não está fazendo nada com isso 
			$resp = $this->executePostServicoEntrega($url, $dataEntrega);
			
			if (!($resp['httpcode']=="201") )  {  // created
				echo "Erro na respota do bling. httpcode=".$resp['httpcode']." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO cria logistica e serviço de entrega - httpcode: '.$resp['httpcode'].' RESPOSTA BLING: '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($dataEntrega,true),"E");
				continue;
			} 
			
			if (!($resp['errno']==0 )) {
				echo "Erro na respota do bling. errno: ".$resp['errno']."-".$resp['errmsg']." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				$this->log_data('batch',$log_name,'ERRO cria logistica e serviço de entrega - errno: '.$resp['errno'].'-'.$resp['errmsg'].' RESPOSTA BLING: '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($dataEntrega,true),"E");
				continue;
			} 
			
			$retornoBling = json_decode($resp['content'],true);
			if (!(isset($retornoBling['retorno']['logisticas']))) {
				$erronum = $retornoBling['retorno']["erros"][0]['cod'];
				$erromsg = $retornoBling['retorno']["erros"][0]['msg'];
				echo "Erro na respota do bling. errno: ".$erronum."-".$erromsg." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				$this->log_data('batch',$log_name,'ERRO cria logistica e serviço de entrega - errno: '.$erronum.'-'.$erromsg.' RESPOSTA BLING: '.print_r($retornoBling,true).' DADOS ENVIADOS:'.print_r($dataEntrega,true),"E");
				continue;
			} 
				
		    $id_servico = $retornoBling['retorno']['logisticas'][0]['id_logistica'];
			$id_servico_entrega = $retornoBling['retorno']['logisticas'][0]['servicos'][0]['servico']['id_servico'];
			
			//var_dump($retornoBling);
			
			//var_dump($id_servico);
			
			//var_dump($id_servico_entrega);
			//{"retorno":{"logisticas":[{"descricao":"Logistica FR","servicos":[{"servico":{"id_servico":7509864473,"descricao":"Entrega do pedido 4","frete_item":71.47,"est_entrega":5,"codigo":"","aliases":[{"alias":"Pedido Conectala 15"}],"id_transportadora":"7283638859"}}],"situacao":null,"id_logistica":"62405"}]}}"
			//die; 
			
			//pego as itens do pedido 
			$order_items = $this->myorders->getOrdersItemData($order['id']);
			if (isset($order_items)) {
				$xml_rastreio = "<rastreamentos>";
				foreach ($order_items as $order_item) {
					$xml_rastreio = $xml_rastreio."<rastreamento>";
					$xml_rastreio = $xml_rastreio."<id_servico>".$id_servico_entrega."</id_servico>";
					$xml_rastreio = $xml_rastreio."<codigo>".$frete['codigo_rastreio']."</codigo>";
					$xml_rastreio = $xml_rastreio."</rastreamento>";			
				}
				$xml_rastreio = $xml_rastreio."</rastreamentos>";
			}
			//echo $xml; 
			echo rawurlencode($xml_rastreio);

			$url = 'https://bling.com.br/Api/v2/logistica/rastreamento/pedido/' . $order['bill_no'] . '/json/';
			$dataEnvio = array(
			    'apikey' => $apikey,
			    'xml' => rawurlencode($xml_rastreio)
			);
			//echo $url;
			//var_dump($dataEnvio);
			$data = $this->executeSendTracking($url,$dataEnvio);
			// die; 
			$retorno_fr = $data['content'];
			//var_dump($data);
			//die;
			
			//echo $data['httpcode']; 
			//echo implode($dataEnvio); 
			if (!($data['httpcode']=="201") )  {
				echo 'Erro no envio do Rastrio pro bling. httpcode: '.$data['httpcode'].' RESPOSTA BLING: '.print_r($data['content'],true).' DADOS ENVIADOS:'.print_r($dataEnvio,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO grava rastreamento - httpcode: '.$data['httpcode'].' RESPOSTA BLING: '.print_r($data['content'],true).' DADOS ENVIADOS:'.print_r($dataEnvio,true),"E");
				continue;
			} 
			*/
			
			// Rastreio enviado
			$order['paid_status'] = 52; // Agora preciso enviar a NF para o Bling 
			
			$order['paid_status'] = 53; // Pulando direto para o Rastreio 
			$this->myorders->updateByOrigin($order['id'],$order);
			echo 'Rastreio enviado pro bling'."\n";
		} 

	}

	function executePostServicoEntrega($url, $params) {
	    $curlHandle = curl_init();
	    curl_setopt_array($curlHandle, [
	        CURLOPT_URL => $url . '?' . http_build_query($params),
	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_CUSTOMREQUEST => 'POST'
	    ]);
	    $response = curl_exec($curlHandle);
		$resp['httpcode'] = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
	    $resp['errno']   = curl_errno($curlHandle);
	    $resp['errmsg']  = curl_strerror($resp['errno']);
	    $resp['content'] = $response;
	    curl_close($curlHandle);
	    return $resp;
	}

	function executeSendTracking($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	//	curl_setopt($curl_handle,CURLOPT_HTTPHEADER,array('Content-Type:application/json'));
	    $content = curl_exec($curl_handle);
		$httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		$err     = curl_errno( $curl_handle );
	    $errmsg  = curl_error( $curl_handle );
		$header  = curl_getinfo( $curl_handle );
	    curl_close($curl_handle);
		$header['httpcode'] = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

}
?>
