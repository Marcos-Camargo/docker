<?php
/*
SW Serviços de Informática 2019
 
Atualiza pedidos que chegaram no BLING

*/   
class BlingAcertaOrders extends BatchBackground_Controller {
		
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
		$this->load->model('model_products','myproducts');
		$this->load->model('model_company','mycompany');
		$this->load->model('model_stores','mystores');
		$this->load->model('model_clients');
		$this->load->model('model_integrations','myintegrations');
		$this->load->model('model_contratar_fretes','mycontratarfretes');
		$this->load->model('model_blingultenvio','myblingultenvio');
		$this->load->model('model_promotions','mypromotions');

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
		$apikeys = $this->getBlingKeys();
		$feitos = array();
		foreach($apikeys as $mkt => $apikey) {
			echo 'Pegando ordens do marketplace '.$mkt."\n";
			if (!(in_array($apikey,$feitos))) {
				$feitos[] = $apikey;
				$this->getorders($apikey);
			}
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getBlingKeys() {
		$apikeys = Array();
		$sql = "select * from stores_mkts_linked where id_integration = 13 AND apelido='VIA'";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}
		return $apikeys;
	}

    function getorders($apikey)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
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

		// $apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$this->load->library('calculoFrete');
		
		$sql = 'SELECT * FROM orders where origin = "VIA" ';
		$query = $this->db->query($sql);
		$errados = $query->result_array();
		foreach ($errados as $errado) {
			
			if (($errado['bill_no'] == '17a') || ($errado['bill_no'] == '16a')) continue;
			$outputType = "json";
			$url = 'https://bling.com.br/Api/v2/pedido/'.$errado['bill_no'].'/'. $outputType;
			$retorno = $this->executeGetOrder($url, $apikey,'','');
			if (!isset($retorno['retorno']['pedidos'])) {
				var_dump($retorno);
				return json_encode($retorno);
			}
			$pedidos = $retorno['retorno']['pedidos'];

			foreach($pedidos as $pedido) {
				echo ' processando id '.$errado['id'].' - '. $errado['numero_marketplace']." ";
				
				$pedido = $pedido['pedido'];
				//echo " PEDIDO ".$pedido['loja']. "\n";
			//	$this->log_data('batch',$log_name,json_encode($pedido),"I");
				$id_mkt = $pedido['loja'];
				$mkt = $this->myintegrations->getMktbyStore($id_mkt);
				$mktname = $mkt['apelido'];	
				//echo $mktname."\n"; 
	
				// PRIMEIRO INSERE O CLIENTE
				$clients = array();
				$clients['customer_name'] = $pedido['cliente']['nome'];
				$clients['customer_address'] = $pedido['cliente']['endereco'];
				$clients['addr_num'] = $pedido['cliente']['numero'];
				$clients['addr_compl'] = $pedido['cliente']['complemento'];
				$clients['addr_neigh'] = $pedido['cliente']['bairro'];
				$clients['addr_city'] = $pedido['cliente']['cidade'];
				$clients['addr_uf'] = $pedido['cliente']['uf'];
				$clients['country'] = 'BR';
				if (is_null($pedido['cliente']['fone'])) {
					$pedido['cliente']['fone']= '';
				}
				$clients['phone_1'] = $pedido['cliente']['fone'];
				if (is_null($pedido['cliente']['celular'])) {
					$pedido['cliente']['celular']= '';
				}
				$clients['phone_2'] = $pedido['cliente']['celular'];
				$clients['zipcode'] = $pedido['cliente']['cep'];
				if (is_null($pedido['cliente']['email'])) {
					$pedido['cliente']['email'] = '';
				}
				$clients['email'] = $pedido['cliente']['email'];
				$clients['cpf_cnpj'] = $pedido['cliente']['cnpj'];
				if (is_null($pedido['cliente']['ie'])) {
					$pedido['cliente']['ie']= '';
				}
				$clients['ie'] = $pedido['cliente']['ie'];
				$clients['rg'] = $pedido['cliente']['rg'];
				$clients['origin'] = $mktname;
				$clients['origin_id'] = $pedido['cliente']['id'];
	
				$cliente_atual = $this->model_clients->getClientsData($errado['customer_id']);
				if ($client_id = $this->model_clients->getByOrigin($errado['origin'], $pedido['cliente']['id'])) {
					if  ($client_id['id'] == $errado['customer_id']) {
						echo "OK\n";
					}else {
						if ( preg_replace("/[^0-9]/", "",$cliente_atual['cpf_cnpj']) == preg_replace("/[^0-9]/", "",$clients['cpf_cnpj'])) {
							echo "OK ".$cliente_atual['cpf_cnpj']." 2\n";
						}
						else {
							echo " --------------------  ANALIZAR \n";
							//$client_id = $this->model_clients->replace($clients);
							echo '*************   Cliente Alterado: CPF '.$clients['cpf_cnpj']."\n";
							$errado['customer_id']= $client_id; 
							//$this->model_orders->updateByOrigin($errado['id'],$errado);
						}
					}
				} else {
				//	$client_id = $this->model_clients->replace($clients);
					echo '*************   Cliente Inserido: CPF '.$clients['cpf_cnpj']."\n";
					$errado['customer_id']= $client_id; 
					//$this->model_orders->updateByOrigin($errado['id'],$errado);
				}
			}
		}
	}


	function executeGetOrder($url, $apikey,$filters,$idSituacao=null){
	    $curl_handle = curl_init();

	  // echo "http = ".$url . '&apikey=' . $apikey."\n";
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return json_decode($response,true);
	}

}

?>
