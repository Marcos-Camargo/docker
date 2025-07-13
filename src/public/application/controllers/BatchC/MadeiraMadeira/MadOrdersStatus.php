<?php
/*
 
Atualiza os pedidos  do Madeira Madeira 

*/   
require APPPATH . "controllers/BatchC/MadeiraMadeira/Main.php";

class MadOrdersStatus extends Main {

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
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_frete_ocorrencias');
		$this->load->model('model_integrations');
		$this->load->model('model_orders_item');
		$this->load->model('model_shipping_company');
		
		
		$this->load->library('ordersMarketplace');
    }

	// php index.php BatchC/MadeiraMadeira/MadOrdersStatus run 
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		If (is_null($params) || ($params=='null')) {  // se não passou parâmetro, é o conecta Lá 
			$this->store_id=0;
			$this->int_to = 'MAD';
		}
		else {
			$this->store_id= $params;
			$this->int_to = 'H_MAD';
		}
		$this->getIntegration();
		$this->mandaNfe();
		$this->mandaTracking();
		$this->mandaEnviado();
		$this->mandaEntregue();
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}

	function mandaNfe()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '52';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de nfe da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			//pego o frete se existir 
			//$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			
			if ($order['exchange_request']) { // pedido de troca não é atualizado no Madeira Madeira 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 50; // agora tudo certo para contratar frete 
				$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			$nfes = $this->model_orders->getOrdersNfes($order['id']);
			if (count($nfes) == 0) {
				echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}
			$nfe = $nfes[0]; 
			
	    	$items = $this->model_orders_item->getItensByOrderId($order['id']);
			$faturamento = array();
			
			$tot_frete = $order['total_ship'] ; 

			foreach ($items as $item) {
				if (is_null($item['kit_id'])) {
					$faturamento[] = array (
						'sku'			=> $item['skumkt'],
				      	'quantidade'	=> (int)$item['qty'],
				      	'chave_acesso'	=> $nfe['chave'],
				      	'data_emissao' 	=> $this->dataBr($nfe['date_emission'],true),
				      	'valor' 		=> (float)$item['amount'] + (float)$tot_frete,
					);
				} 
				else {
					if (!is_null($item['original_qty'])) {
						$faturamento[] = array (
							'sku'			=> $item['skumkt'],
					      	'quantidade'	=> (int)$item['original_qty'],
					      	'chave_acesso'	=> $nfe['chave'],
					      	'data_emissao' 	=> $this->dataBr($nfe['date_emission'],true),
					      	'valor' 		=> (float)$item['original_amount'] + (float)$tot_frete,
						);
					}
				}
				$tot_frete = 0; // somo só no  primeiro item.
			}
			
			$url = "/v1/pedido/invoiced";
			$data = array(
				array(
					'id_pedido'   	=> (int)$order['numero_marketplace'], 
					'faturamento' 	=> $faturamento,
 				)
			);
			$data = json_encode($data, JSON_UNESCAPED_UNICODE);
			$data = stripcslashes($data);
			
			$this->processURL($url,'PUT', $data);
			
			if ($this->responseCode == 422)  {  // deu algum probliema na atualizacao de status. vou ver se já está atualizado lá no mkt
				$oldresult = $this->result;
				$oldresp = $this->responseCode;
				$pedido_mm = $this->getOrderMkt($order['numero_marketplace']);
				if ($pedido_mm) {  // consegui achar o pedido....
					if ($pedido_mm['data']['status']==6) { // este pedido já foi atualizado no marketplace
						$order['paid_status'] = 50; // agora tudo certo para contratar frete 
						$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
						if (!is_null($pedido_mm['data']['datahora_faturamento'])) {
							$order['envia_nf_mkt'] = $pedido_mm['data']['datahora_faturamento'];
						}
						$this->model_orders->updateByOrigin($order['id'],$order);
						echo 'NFE enviado para '.$this->int_to."\n";
						continue;
					}
					// ái tem problema. precisa ser analizado. 
				}
				$error = 'Erro na resposta ao informar como recebido o pedido de id '.$order['id'].' site: '.$this->site.$url.' httpcode:'.$oldresp.' RESPOSTA: '.print_r($oldresult,true). ' DADOS ENVIADOS: '.print_r($data,true); 
				echo $error."\n";
				$this->log_data('batch',$log_name, $error,"E");
				continue;
			
			} elseif (($this->responseCode != 200) && ($this->responseCode != 204))  {
				$error = 'Erro na resposta ao informar como recebido o pedido de id '.$order['id'].' site: '.$this->site.$url.' httpcode:'.$this->responseCode.' RESPOSTA: '.print_r($this->result,true). ' DADOS ENVIADOS: '.print_r($data,true); 
				echo $error."\n";
				$this->log_data('batch',$log_name, $error,"E");
				continue;
			}
			$order['paid_status'] = 50; // agora tudo certo para contratar frete 
			$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'NFE enviado para '.$this->int_to."\n";
		} 

	}

	function mandaTracking()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de tracking da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			
			// Madeira madeira não manda tracking por enquanto

			$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Tracking enviado para '.$this->int_to."\n";
		} 
	}

	function mandaEnviado()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
		
			if ($order['exchange_request']) { // pedido de troca não é atualizado no Madeira Madeira 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 5; // agora tudo certo para contratar frete 
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			//pego o frete se existir 
			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
				continue;
			}
			
			$frete = $frete[0];
			
			$carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/';
			if (!empty($frete['CNPJ'])) {
				$transportadora = $this->model_shipping_company->getShippingCompanyByCnpjAndStore($frete['CNPJ'], $order['store_id']);
				if ($transportadora) {
					if (!is_null($transportadora['tracking_web_site'])) {
						$carrier_url = $transportadora['tracking_web_site'];
					}
				}
			}
			
			$data_envio = $this->dataBr($order['data_envio'],true);
			$envio = array();
			$items = $this->model_orders_item->getItensByOrderId($order['id']);
			foreach ($items as $item) {
				if (is_null($item['kit_id'])) {
					$envio[] = array (
						'sku'					=> $item['skumkt'],
				      	'quantidade'			=> (int)$item['qty'],
				      	'data_transportadora'	=> $data_envio, 
				      	'codigo_rastreio'		=> $frete['codigo_rastreio'],
				      	'nome_transportadora'	=> $frete['ship_company'],
				      	'url_rastreio'			=> $carrier_url,
					);
				} 
				else {
					if (!is_null($item['original_qty'])) {
						$envio[] = array (
							'sku'					=> $item['skumkt'],
					      	'quantidade'			=> (int)$item['original_qty'],
					      	'data_transportadora'	=> $data_envio, 
				      		'codigo_rastreio'		=> $frete['codigo_rastreio'],
				      		'nome_transportadora'	=> $frete['ship_company'],
				      		'url_rastreio'			=> $carrier_url,
						);
					}
				}
				
			}
			
			$url = "/v1/pedido/shipped";
			$data = array( 
				array(
					'id_pedido' => (int)$order['numero_marketplace'], 
					'envio' 	=> $envio,
 				)
			);
			$data = json_encode($data, JSON_UNESCAPED_UNICODE);
			$data = stripcslashes($data);
			$this->processURL($url,'PUT', $data);
			if ($this->responseCode == 422)  {  // deu algum probliema na atualizacao de status. vou ver se já está atualizado lá no mkt
				$oldresult = $this->result;
				$oldresp = $this->responseCode;
				$pedido_mm = $this->getOrderMkt($order['numero_marketplace']);
				if ($pedido_mm) {  // consegui achar o pedido....
					if ($pedido_mm['data']['status']==7) { // este pedido já foi atualizado no marketplace
						$order['paid_status'] = 5; // agora tudo certo para contratar frete 
						$this->model_orders->updateByOrigin($order['id'],$order);
						echo 'Aviso de Envio enviado para '.$this->int_to."\n";
						continue;
					}
					// ái tem problema. precisa ser analizado. 
				}
				$error = 'Erro na resposta ao informar como recebido o pedido de id '.$order['id'].' site: '.$this->site.$url.' httpcode:'.$oldresp.' RESPOSTA: '.print_r($oldresult,true). ' DADOS ENVIADOS: '.print_r($data,true); 
				echo $error."\n";
				$this->log_data('batch',$log_name, $error,"E");
				continue;
			
			} elseif (($this->responseCode != 200) && ($this->responseCode != 204))  {
				$error = 'Erro na resposta ao informar como recebido o pedido de id '.$order['id'].' site: '.$this->site.$url.' httpcode:'.$this->responseCode.' RESPOSTA: '.print_r($this->result,true). ' DADOS ENVIADOS: '.print_r($data,true); 
				echo $error."\n";
				$this->log_data('batch',$log_name, $error,"E");
				continue;
			}

			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Envio enviado para '.$this->int_to."\n";
		} 

	}

	function mandaEntregue()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '60';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
		
			if ($order['exchange_request']) { // pedido de troca não é atualizado no Madeira Madeira 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 6; // agora tudo certo para contratar frete 
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			$data_entrega = $this->dataBr($order['data_entrega'],true);
			$entrega = array();
			$items = $this->model_orders_item->getItensByOrderId($order['id']);
			foreach ($items as $item) {
				if (is_null($item['kit_id'])) {
					$entrega[] = array (
						'sku'			=> $item['skumkt'],
				      	'quantidade'	=> (int)$item['qty'],
				      	'data_entrega'	=> $data_entrega, 
					);
				} 
				else {
					if (!is_null($item['original_qty'])) {
						$entrega[] = array (
					      	'sku'			=> $item['skumkt'],
				      		'quantidade'	=> (int)$item['original_qty'],
				      		'data_entrega'	=> $data_entrega, 
						);
					}
				}
				
			}
			
			$url = "/v1/pedido/delivered";
			$data = array(
				array(
					'id_pedido' => (int)$order['numero_marketplace'] , 
					'entrega' 	=> $entrega,
 				)
			);
			$data = json_encode($data, JSON_UNESCAPED_UNICODE);
			$data = stripcslashes($data);
			$this->processURL($url,'PUT', $data);
			if ($this->responseCode == 422)  {  // deu algum probliema na atualizacao de status. vou ver se já está atualizado lá no mkt
				$oldresult = $this->result;
				$oldresp = $this->responseCode;
				$pedido_mm = $this->getOrderMkt($order['numero_marketplace']);
				if ($pedido_mm) {  // consegui achar o pedido....
					if ($pedido_mm['data']['status']==8) { // este pedido já foi atualizado no marketplace
						$order['paid_status'] = 6; // 
						$this->model_orders->updateByOrigin($order['id'],$order);
						echo 'Aviso de Entregue enviado para '.$this->int_to."\n";
						continue;
					}
					// ái tem problema. precisa ser analizado. 
				}
				$error = 'Erro na resposta ao informar como recebido o pedido de id '.$order['id'].' site: '.$this->site.$url.' httpcode:'.$oldresp.' RESPOSTA: '.print_r($oldresult,true). ' DADOS ENVIADOS: '.print_r($data,true); 
				echo $error."\n";
				$this->log_data('batch',$log_name, $error,"E");
				continue;
			
			} elseif (($this->responseCode != 200) && ($this->responseCode != 204))  {
				$error = 'Erro na resposta ao informar como recebido o pedido de id '.$order['id'].' site: '.$this->site.$url.' httpcode:'.$this->responseCode.' RESPOSTA: '.print_r($this->result,true). ' DADOS ENVIADOS: '.print_r($data,true); 
				echo $error."\n";
				$this->log_data('batch',$log_name, $error,"E");
				continue;
			}

			$order['paid_status'] = 6; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Entregue enviado para '.$this->int_to."\n";
		} 

	}

	function dataBr($data, $justdate = true) {
		$newData = DateTime::createFromFormat('d/m/Y H:i:s',$data);
		if ($newData == false) {
			$newData = DateTime::createFromFormat('d/m/Y H:i:s',$data.' 00:00:00');
			if ($newData == false) {
				$newData = DateTime::createFromFormat('Y-m-d H:i:s',$data);
			}
		}
		if ($justdate) return $newData->format('d/m/Y');
		return $newData->format('d/m/Y H:i:s');
	}
	
	public function getOrderMkt($id) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$url = "/v1/pedido/id/".$id;
		$this->processURL($url,'GET', null); 
		if ($this->responseCode == 404) {
			echo "Não achei o pedido {$id}\n";
			return false;
		}
		if ($this->responseCode != 200) {
			$error = "Erro {$this->responseCode} ao acessar {$this->site}{$url} na função GET";
			echo $error."\n";
			$this->log_data('batch',$log_name,$error,"E");
			die;
		}
		return json_decode($this->result,true);
	}
}
?>
