<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   
class BlingCancelar extends BatchBackground_Controller {
		
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
		$this->load->model('model_clients','myclients');
    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			get_instance()->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		get_instance()->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->cancelaPedidoBling();
		
		/* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
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
	
	function cancelaPedidoBling()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 52, ordens que já tem rastrio no bling e envia para o Bling 
		$paid_status = '99';  
		
		$ordens_andamento =get_instance()->myorders->getOrdensByPaidStatus($paid_status);
		if (count($ordens_andamento)==0) {
			get_instance()->log_data('batch',$log_name,'Nenhum pedido pendente de envio de cancelamento para o Bling',"I");
			return ;
		}
		
	//	$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$apikeys = $this->getBlingKeys();
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			// Pulo as ordens da B2W 
			if ($order['origin'] == 'B2W') {
				echo "B2W - Pulei \n"; 
				continue; 
			}
			// Pulo as ordens da CAR 
			if ($order['origin'] == 'CAR') {
				echo "CAR - Pulei \n"; 
				continue; 
			}
			// Pulo as ordens da VIA 
			if ($order['origin'] == 'VIA') {
				echo "VIA - Pulei \n"; 
				continue; 
			}// Pulo as ordens da ML 
			if ($order['origin'] == 'ML') {
				echo "ML - Pulei \n"; 
				continue; 
			}
		/* Lista de estados de pedidos de Vendas do Bling 
		 curl -X GET "https://bling.com.br/Api/v2/situacao/vendas/json/"
     		-G
     		-d "apikey={apikey}"
                    "id": "6",  "nome": "Em aberto",
                    "id": "9",  "nome": "Atendido",
                    "id": "12", "nome": "Cancelado",
                    "id": "15", "nome": "Em andamento",
                    "id": "18", "nome": "Venda Agenciada",
                    "id": "21", "nome": "Em digitação",
                    "id": "24", "nome": "Verificado",
		*/
			$apikey = $apikeys[$order['origin']];
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?><pedido><idSituacao>12</idSituacao></pedido>';
			$url = 'https://bling.com.br/Api/v2/pedido/'.$order['bill_no'].'/json';
			
			$posts  = array(
			    'apikey' => $apikey,
			    'xml' => rawurlencode($xml)
			);

			$resp = $this->executeUpdateOrder($url, $posts);
			
			if (!($resp['httpcode']=="200") )  {  // created
				echo "Erro na respota do bling. httpcode=".$resp['httpcode']." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				get_instance()->log_data('batch',$log_name, 'ERRO no cancelamento do pedido no bling - httpcode: '.$resp['httpcode'].' RESPOSTA BLING: '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($xml_entrega->asXML(),true),"E");
				continue;
			} 
			
			if (!($resp['errno']==0 )) {
				echo "Erro na respota do bling. errno: ".$resp['errno']."-".$resp['errmsg']." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				get_instance()->log_data('batch',$log_name,'ERRO no cancelamento do pedido no bling - errno: '.$resp['errno'].'-'.$resp['errmsg'].' RESPOSTA BLING: '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($xml_entrega->asXML(),true),"E");
				continue;
			} 
			
			$retornoBling = json_decode($resp['content'],true);
			//var_dump($retornoBling);

			if (!(isset($retornoBling['retorno']['pedidos']))) {
				echo "Erro na respota do bling. RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				get_instance()->log_data('batch',$log_name,'ERRO no cancelamento do pedido. URL= '.$url.' RESPOSTA BLING: '.print_r($retornoBling,true).' DADOS ENVIADOS:'.print_r($posts,true),"E");
				continue;
			} 
			
			// Nota fiscal enviada 
			$order['paid_status'] = 98; // agora tudo certo para ficar rasteando o pedido
			get_instance()->myorders->updateByOrigin($order['id'],$order);
			echo 'Pedido marcado para cancelar no Marketplace'."\n";
		} 

	}

	function executeUpdateOrder($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $content  = curl_exec($curl_handle);
		$httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		$err      = curl_errno( $curl_handle );
	    $errmsg   = curl_error( $curl_handle );
	    $header   = curl_getinfo( $curl_handle );
	    curl_close( $curl_handle );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	
}
?>
