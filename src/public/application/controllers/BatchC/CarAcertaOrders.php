<?php
/*
 
Baixa os pedidos que chegaram no Carrefour

*/   
class CarAcertaOrders extends BatchBackground_Controller {
	
	var $int_to='CAR';
	var $apikey='';
	var $site='';
	
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
		$this->load->model('model_orders');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_products');
    }
	
	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setApikey($apikey) {
		$this->apikey = $apikey;
	}
	function getApikey() {
		return $this->apikey;
	}
	function setSite($site) {
		$this->site = $site;
	}
	function getSite() {
		return $this->site;
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
		$this->getkeys(1,0);
		$this->getorders();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setSite($api_keys['site']);
	}

    function getorders()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->load->library('calculoFrete');
		
		$order_state_codes="WAITING_ACCEPTANCE,WAITING_DEBIT_PAYMENT,SHIPPING,CANCELED,SHIPPED"; 
		$start_update_date= date("Y-m-d\TH:i:s",time() - 60 * 60 * 24*7);
		$end_update_date= date("Y-m-d\TH:i:s",time() );
		
		$sql = 'SELECT * FROM orders where origin = "CAR"';
		$query = $this->db->query($sql);
		$errados = $query->result_array();
		foreach ($errados as $errado) {
			echo ' processando id '.$errado['id'].' - '. $errado['numero_marketplace']." ";
			$url = 'https://'.$this->getSite().'/api/orders?order_ids='.$errado['numero_marketplace'];
			//echo 'url='.$url."\n";
			$retorno = $this->getCarrefour($url, $this->getApikey());
			if ($retorno['httpcode'] == 429) {
				echo 'dormindo ..';
				sleep(60);
				$retorno = $this->getCarrefour($url, $this->getApikey());
			}
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
				return;
			}
			
			$pedidos = json_decode($retorno['content'],true);
			foreach ($pedidos['orders'] as $pedido) {
				
				//var_dump($pedido);
	
				$clients = array();
				// PRIMEIRO INSERE O CLIENTE
				$clients['customer_name'] = $pedido['customer']['firstname'].' '.$pedido['customer']['lastname'];
				$orders['customer_name'] = $clients['customer_name'];	
				$clients['phone_1'] = '';
				foreach ($pedido['order_additional_fields'] as $campo) {
					if ($campo['code'] == 'customer-cpf') {
						$clients['cpf_cnpj'] = preg_replace("/[^0-9]/", "",$campo['value']);
					}elseif ($campo['code'] == 'delivery-address') {
						$orders['customer_address']  = $campo['value'];
					}elseif ($campo['code'] == 'delivery-complement-address') {
						$orders['customer_address_compl'] = $campo['value'];
					}elseif ($campo['code'] == 'delivery-district-address') {
						$orders['customer_address_neigh'] = $campo['value'];
					}elseif ($campo['code'] == 'delivery-number-address') {
						$orders['customer_address_num'] = $campo['value'];
					}elseif ($campo['code'] == 'delivery-postal-code') {
						$orders['customer_address_zip'] = preg_replace("/[^0-9]/", "",$campo['value']);
					}elseif ($campo['code'] == 'delivery-state') {
						$orders['customer_address_uf'] = $campo['value'];
					}elseif ($campo['code'] == 'delivery-town') {
						$orders['customer_address_city']  = $campo['value'];
					}elseif ($campo['code'] == 'tel-number') {
						$clients['phone_1'] = $campo['value'];
					}
				}
				$clients['customer_address'] = $orders['customer_address'];
				$clients['addr_num'] = $orders['customer_address_num'];
				$clients['addr_compl'] = $orders['customer_address_compl'];
				$clients['addr_neigh'] = $orders['customer_address_neigh'];
				$clients['addr_city'] = $orders['customer_address_city']; 
				$clients['addr_uf'] = $orders['customer_address_uf'];
				$clients['country'] = 'BR';
				$clients['zipcode'] = preg_replace("/[^0-9]/", "",$orders['customer_address_zip']);
				
				$clients['origin'] = $this->getInt_to();
				$clients['origin_id'] = $pedido['customer']['customer_id'];
				// campos que não tem no carrefour 
				$clients['phone_2'] = '';
				$clients['email'] =  '';
				$clients['ie'] = '';
				$clients['rg'] = '';
				
				// var_dump($clients);
				$cliente_atual = $this->model_clients->getClientsData($errado['customer_id']);
				if ($client_id = $this->model_clients->getByOrigin($this->getInt_to(),$pedido['customer']['customer_id'])) {
					if  ($client_id['id'] == $errado['customer_id']) {
						echo "OK\n";
					}
					else {
						if ( preg_replace("/[^0-9]/", "",$cliente_atual['cpf_cnpj']) == $clients['cpf_cnpj']) {
							echo "OK ".$cliente_atual['cpf_cnpj']." 2\n";
						}
						else {
							echo " --------------------  ANALIZAR ".$pedido['customer']['customer_id'];
							$client_id = $this->model_clients->replace($clients);
							echo '*************   Cliente Alterado: CPF '.$clients['cpf_cnpj']."\n";
							$errado['customer_id']= $client_id; 
							$this->model_orders->updateByOrigin($errado['id'],$errado);
						}
					}
				} else {
					$client_id = $this->model_clients->replace($clients);
					echo '*************   Cliente Inserido: CPF '.$clients['cpf_cnpj']."\n";
					$errado['customer_id']= $client_id; 
					//$this->model_orders->updateByOrigin($errado['id'],$errado);
				}	
			}	
		}
		
	}
	
	function cancelaPedido($pedido)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo 'Cancelando pedido ='.$pedido."\n"; 
		
		$motivo = 'Falta de produto';

		$cancel = Array (
					'body' => 'Por favor, cancelem o pedido '.$pedido.'. Motivo: '.$motivo.'. Obrigado', 
					'subject' => 'Favor cancelar pedido '.$pedido, 
					'to_customer' => false,
					'to_operator' => true,
					'to_shop' => false,
				);

		$json_data = json_encode($cancel);
		$url = 'https://'.$this->getSite().'/api/orders/'.$pedido.'/messages';

		$resp = $this->postCarrefour($url, $this->getApikey(), $json_data);
		
	    //var_dump($resp); 

		if (!($resp['httpcode']=="201") )  {  // created
			echo 'Erro na respota do '.$this->getInt_to().' httpcode='.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true)."\n"; 
			$this->log_data('batch',$log_name, 'ERRO ao cancelar no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return false;
		}
		return true;
	}


	function getCarrefour($url, $api_key){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "GET",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json',
				'Authorization: '.$api_key,
				)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}

	function putCarrefour($url, $api_key,$data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "PUT",
			CURLOPT_HTTPHEADER 		=>  array(
				'Accept: application/json',
				'Authorization: '.$api_key,
				'Content-Type: application/json'
				)
	    );
		if ($data != '') {
			$options[CURLOPT_POSTFIELDS] = $data;
		}
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}
	
	function postCarrefour($url, $api_key,$data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "POST",
	        CURLOPT_POSTFIELDS     => $data,
			CURLOPT_HTTPHEADER 		=>  array(
				'Accept: application/json',
				'Authorization: '.$api_key,
				'Content-Type: application/json'
				)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}

}

?>
