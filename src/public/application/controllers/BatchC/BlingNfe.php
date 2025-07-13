<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   
class BlingNfe extends BatchBackground_Controller {
		
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
		$this->load->model('model_integrations','myintegrations');
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
		$this->mandaNfeBling();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function getBlingKeys() {
		$apikeys = Array();
		
		// Pulo a B2W  e CAR 
		$sql = "select * from stores_mkts_linked where id_integration = 13 AND apelido != 'B2W' AND apelido != 'CAR' AND apelido != 'VIA' AND apelido != 'ML'";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}
		return $apikeys;
	}
	
	function mandaNfeBling()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 52, ordens que já tem rastrio no bling e envia para o Bling 
		$paid_status = '52';  
		
		$ordens_andamento =$this->myorders->getOrdensByPaidStatus($paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe para o Bling',"I");
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
			// Pulo as ordens da CAR 
			if ($order['origin'] == 'CAR') {
				echo "CAR - Pulei \n"; 
				continue; 
			}
			// Pulo as ordens da VIA 
			if ($order['origin'] == 'VIA') {
				echo "VIA - Pulei \n"; 
				continue; 
			}
			// Pulo as ordens da CAR 
			if ($order['origin'] == 'VIA') {
				echo "VIA - Pulei \n"; 
				continue; 
			}
			$frete=$this->myfreights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
				continue;

			}
			$frete = $frete[0];
			
			$nfes = $this->myorders->getOrdersNfes($order['id']);
			if (count($nfes) == 0) {
				echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}
			$nfe = $nfes[0]; 
			
			//pego o cliente 
			$cliente = $this->myclients->getClientsData($order['customer_id']); 
			if (!(isset($cliente))) {
				$this->log_data('batch',$log_name,'ERRO: pedido '.$order['id'].' sem o cliente '.$order['customer_id'],"E");
				continue;
			} 

			// pego o ID da loja no Bling 
			$sql = 'SELECT * FROM stores_mkts_linked WHERE apelido = ? and id_integration = 13 AND apelido != "B2W" AND apelido != "CAR" AND apelido != "VIA" AND apelido != "ML"'; 
			$query = $this->db->query($sql, array($order['origin']));
			$store_mkts_linked = $query->row_array(); 

			
			$bling = Array (
					'tipo' => 'S',
					'finalidade' => '1',
					'loja' => $store_mkts_linked['id_loja'],
					'numero_loja' => $order['numero_marketplace'], 
					'numero_nf'=> $nfe['nfe_num'],
					'nat_operacao' => 'Venda de mercadorias', 
					'data_operacao' => $nfe['date_emission'],
					'doc_referenciado' => Array(
						'modelo' => '55',
						'data' => substr($nfe['nfe_num'],8,2).substr($nfe['nfe_num'],3,2),
						'numero' => $nfe['nfe_num'],
						'serie' => $nfe['nfe_serie'],
						'chave_acesso' =>$nfe['chave'],
					),
					'cliente' => array(
						'nome' => $cliente['customer_name'],
						'tipo_pessoa' => 'F',
						'cpf_cnpj' => str_replace('-','',str_replace('.','',$cliente['cpf_cnpj'])), 
						'ie_rg' => trim($cliente['ie'].$cliente['rg']),
						'contribuinte' => '9',
						'endereco' => $cliente['customer_address'],
						'numero' => $cliente['addr_num'],
						'complemento' => $cliente['addr_compl'],
						'bairro' => $cliente['addr_neigh'],
						'cep' => $cliente['zipcode'],
						'cidade' => $cliente['addr_city'],
						'uf' => $cliente['addr_uf'],
						'fone' => $cliente['phone_1'],
						'email' => $cliente['email'],	
					),
					'volume' => Array(
						'servico' => "Entregadopedido".$order['id'],
						'codigoRastreamento' => $frete['codigo_rastreio'],
					),
					
				);
			$bling['pedido']['itens'] = array();
			$itens = $this->myorders->getOrdersItemData($order['id']);
			foreach($itens as $item) {
				// pego o SKU do Bling do produto. 
				$sql = 'SELECT * FROM prd_to_integration WHERE prd_id = ? and int_to = ?'; 
				$query = $this->db->query($sql, array($item['product_id'], $order['origin']));
				$prd_integration = $query->row_array(); 
						
				// pego o produto do  prd_to_integration
				$bling['pedido']['itens']['item'] = array(
					'codigo' => $prd_integration['skubling'],
					'descricao' => $item['name'],
					'un' => 'un',
					'qtde' => $item['qty'],
					'vlr_unit' => $item['rate'],
					'tipo' => 'P',
				);
			}
			$xml_entrega = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><pedido></pedido>');
			$this->array_to_xml($bling,$xml_entrega);
			
			$url = 'https://bling.com.br/Api/v2/notafiscal/json/';
			
		//	echo "xml_entrega=".rawurlencode($xml_entrega->asXML())."\n";
			echo "xml_entrega=".$xml_entrega->asXML()."\n";
			
			$apikey = $apikeys[$order['origin']];
			
			$dataNfe = array(
			    'apikey' => $apikey,
			    'xml' => $xml_entrega->asXML()
			);

			$resp = $this->executeSendFiscalDocument($url, $dataNfe);
			
			// var_dump($resp); 
			
			
			if (!($resp['httpcode']=="201") )  {  // created
				echo "Erro na respota do bling. httpcode=".$resp['httpcode']." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO na gravação da NFE no bling - httpcode: '.$resp['httpcode'].' RESPOSTA BLING: '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($xml_entrega->asXML(),true),"E");
				continue;
			} 
			
			if (!($resp['errno']==0 )) {
				echo "Erro na respota do bling. errno: ".$resp['errno']."-".$resp['errmsg']." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				$this->log_data('batch',$log_name,'ERRO na gravação da NFE no bling - errno: '.$resp['errno'].'-'.$resp['errmsg'].' RESPOSTA BLING: '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($xml_entrega->asXML(),true),"E");
				continue;
			} 
			
			$retornoBling = json_decode($resp['content'],true);
			var_dump($retornoBling);
	
			if (!(isset($retornoBling['retorno']['notasfiscais']))) {
				$erronum = $retornoBling['retorno']["erros"][0]['cod'];
				$erromsg = $retornoBling['retorno']["erros"][0]['msg'];
				echo "Erro na respota do bling. errno: ".$erronum."-".$erromsg." RESPOSTA BLING: ".print_r($resp['content'],true)." \n"; 
				$this->log_data('batch',$log_name,'ERRO cria logistica e serviço de entrega - errno: '.$erronum.'-'.$erromsg.' RESPOSTA BLING: '.print_r($retornoBling,true).' DADOS ENVIADOS:'.print_r($xml_entrega->asXML(),true),"E");
				continue;
			} 

			$nfe['id_nf_marketplace'] = $retornoBling['retorno']['notasfiscais'][0]['notaFiscal']['idNotaFiscal'];
			$this->mynfes->replace($nfe);
			 
			// Nota fiscal enviada 
			$order['paid_status'] = 53; // agora tudo certo para ficar rasteando o pedido
			$this->myorders->updateByOrigin($order['id'],$order);
			echo 'NFE enviado pro bling'."\n";
		} 

	}

	function array_to_xml( $data, &$xml_data ) {
	    foreach( $data as $key => $value ) {
	        if( is_array($value) ) {
	            if( is_numeric($key) ){
	                $key = 'item'.$key; //dealing with <0/>..<n/> issues
	            }
	            $subnode = $xml_data->addChild($key);
	            $this->array_to_xml($value, $subnode);
	        } else {
	            $xml_data->addChild("$key",htmlspecialchars("$value"));
	        }
	     }
	}
	
	function executeSendFiscalDocument($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
		$resp['httpcode'] = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
	    $resp['errno']   = curl_errno($curl_handle);
	    $resp['errmsg']  = curl_strerror($resp['errno']);
	    $resp['content'] = $response;
	    curl_close($curl_handle);
	    return $resp;
	}
	
}
?>
