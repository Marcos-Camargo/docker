<?php
/*
 
Atualiza informações dos pedidos no marketplace 

*/   
class OrdersStatus extends BatchBackground_Controller {
	
	var $int_to='';
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
		        'usercomp' 	=> 1,
				'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_integrations');
		$this->load->model('model_frete_ocorrencias');
		$this->load->model('model_shipping_company');
		$this->load->library('ordersMarketplace');
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
	
	//php index.php BatchC/Marketplace/Mirakl/OrdersStatus run null GPA
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
		if(!is_null($params)){
            $this->int_to = $params;
			$this->getkeys(1,0);
			$this->mandaTracking();
			$this->mandaNfe();
			$this->mandaEnviado();
			$this->mandaEntregue();
			$this->mandaCancelados();     
			$this->mandaExcecaoEntrega(); 
        }
		else{
			echo "É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
		}
		
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
	
	function mandaTracking()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de tracking da '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			//pego a cotação de frete
			
			if ($order['exchange_request']) { // pedido de troca não é atualizado
				echo 'pedido de troca'."\n";
				if (is_null($order['envia_nf_mkt'])) { 
					$order['paid_status'] = 52; // fluxo antigo, manda para a envio da Nota Fiscal 
				}
				else {
					$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
				}
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E" );
			     $order['paid_status'] = 101; // Precisa contratar o frete manualmente
				$this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			$frete = $frete[0];
			
			// Precisará trocar se mandou pelos correios. 
			/*
			if ($frete['ship_company']== 'CORREIOS') {
				$carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/default.cfm';
			} else {
				$carrier_url = 'https://ondeestameupedido.com.br/'.$frete['codigo_rastreio'];
			} */
			$carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/';
			if (!empty($frete['CNPJ'])) {
				$transportadora = $this->model_shipping_company->getShippingCompanyByCnpjAndStore($frete['CNPJ'], $order['store_id']);
				if ($transportadora) {
					if (!is_null($transportadora['tracking_web_site'])) {
						$carrier_url = $transportadora['tracking_web_site'];
					}
				}
			}

			$carrier_url = base_url('tracking');
			
			$tracking = Array (
						'carrier_name' => $frete['ship_company'],
						'carrier_url' => $carrier_url,
						'tracking_number' => $frete['codigo_rastreio'],
					);
			
			var_dump($tracking);

			$json_data = json_encode($tracking);
			$url = 'https://'.$this->getSite().'/api/orders/'.$order['numero_marketplace'].'/tracking';
	
			$resp = $this->putMirakl($url, $this->getApikey(),  $json_data);

			if (!($resp['httpcode']=="204") )  {
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA: ".print_r($resp['content'],true)." \n"; 
				echo "http:".$url."\n";
				echo "apikey:".$this->getApikey()."\n";
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação da tracking pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}
			 
			// Tracking enviado
		    if (is_null($order['envia_nf_mkt'])) { 
				$order['paid_status'] = 52; // fluxo antigo, manda para a envio da Nota Fiscal 
			}
			else {
				$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
			}
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Tracking enviado para '.$this->getInt_to()."\n";
		} 

	}

	function mandaNfe()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 52 Ordens que já enviou o tracking 
		$paid_status = '52';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			
			if ($order['exchange_request']) { // pedido de troca não é atualizado 
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

			$issue_date =  str_replace(" ","T",$nfe['date_emission'])."-03:00";
			$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission']);
			if ($issue_date_o == false) {
				$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission'].' 00:00:00');
				if ($issue_date_o == false) {
					$issue_date_o = DateTime::createFromFormat('Y-m-d H:i:s',$nfe['date_emission']);
					if ($issue_date_o == false) {
						$issue_date =  str_replace("T"," ",$nfe['date_emission']);
						$issue_date =  str_replace(".000Z","",$issue_date);
						$issue_date_o = DateTime::createFromFormat('Y-m-d H:i:s',$issue_date);
					}
				}
				
			}
			if ($issue_date_o == false) {
				$error = "PEDIDO ".$order['id']. " DATA DE EMISSÂO NÂO SUPORTADA ==> ".$nfe['date_emission'];
				echo $error." \n";
				$this->log_data('batch',$log_name, $error ,"E");
				continue;
			} 
			$issue_date = $issue_date_o->format('Y-m-d H:i:s');
			$issue_date = str_replace(" ","T",$issue_date)."-03:00";
			if ($this->int_to == 'CAR') {
				$invoiced = Array (
					'order_additional_fields' => array(
						array(
							'code' => 'number-nfe',
							'value' => $nfe['nfe_num']
						),
						array(
							'code' => 'date-nfe',
							'value' => $issue_date,
						),
						array(
							'code' => 'serial-nfe',
							'value' => $nfe['nfe_serie']
						),
						array(
							'code' => 'code-nfe',
							'value' => $nfe['chave']
						),
					)
				);
			}
			elseif ($this->int_to == 'GPA') {
				$invoiced = Array (
					'order_additional_fields' => array(
						array(
							'code' => 'chave-nf',
							'value' => array($nfe['chave'])
						),
					)
				);
			}			

			$json_data = json_encode($invoiced);
			$url = 'https://'.$this->getSite().'/api/orders/'.$order['numero_marketplace'].'/additional_fields';
			$resp = $this->putMirakl($url, $this->getApikey(),  $json_data);

			if (!($resp['httpcode']=="200") )  {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA : ".print_r($resp['content'],true)." \n"; 
				echo "http:".$url."\n";
				echo "apikey:".$this->getApikey()."\n";
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}
			
			$order['paid_status'] = 50; // agora tudo certo para contratar frete 
			$order['envia_nf_mkt'] = date('Y-m-d H:i:s');

			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'NFE enviado para '.$this->getInt_to()."\n";
		} 

	}
	
	function mandaEnviado()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'Marcando como enviado pedido ='.$order['id']."\n"; 
			
			if ($order['exchange_request']) { // pedido de troca não é atualizado 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			$url = 'https://'.$this->getSite().'/api/orders/'.$order['numero_marketplace'].'/ship';
			$resp = $this->putMiraklEmpty($url, $this->getApikey());

			if (!($resp['httpcode']=="204") )  {  // created
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA: ".print_r($resp['content'],true)." \n"; 
				echo "http:".$url."\n";
				echo "apikey:".$this->getApikey()."\n";
				$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido enviado pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true),"E");
				continue;
			}
			
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Envio enviado para '.$this->getInt_to()."\n";
		} 

	}

	function mandaEntregue()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 60, ordens que já tem mudaram o status para entregue no FreteRastrear
		$paid_status = '60';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Entregue da '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			if ($order['exchange_request']) { // pedido de troca não é atualizado mas tem que ser atualizado pelo pessoal de operações 
				echo 'pedido de troca'."\n";
				continue;
			}
			if ($this->int_to == 'CAR') {
				$delivered = Array (
					'order_additional_fields' => array(
						array(
							'code' => 'seller-delivery-confirmation',
							'value' => "true"
						)
					)
				);
			}
			elseif ($this->int_to == 'GPA') {
				$delivered = Array (
					'order_additional_fields' => array(
						array(
							'code' => 'data-entrega',
							'value' => array(date("Y-m-d",strtotime($order['data_entrega'])))
						),
					)
				);
			}
						
			$json_data = json_encode($delivered);
			$url = 'https://'.$this->getSite().'/api/orders/'.$order['numero_marketplace'].'/additional_fields';				
			// Depois que envia este campo preenchido, o marketplace não muda automaticamente de SHIPPED para RECEIVED. Demora algum tempo para acontecer
			$resp = $this->putMirakl($url, $this->getApikey(), $json_data);
			
		   // var_dump($resp); 

			if (!($resp['httpcode']=="200") )  {  // created
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
				echo "http:".$url."\n";
				echo "apikey:".$this->getApikey()."\n";
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido entregue pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}
		
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 6; // O pedido está entregue 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Entregue enviado para '.$this->getInt_to()."\n";
		} 

	}

	function mandaCancelados()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 99, ordens que canceladas que tem que ser avisadas no Marketplace
		$paid_status = '99';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem a cancelar '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'Cancelando pedido ='.$order['id']."\n"; 
			if ($order['exchange_request']) { // pedido de troca não é atualizado mas tem que ser atualizado pelo pessoal de operações 
				echo 'pedido de troca'."\n";
				continue;
			}
			$motivos =  $this->model_orders->getPedidosCanceladosByOrderId($order['id']);
			$motivo = 'Falta de produto';
			if (!empty($motivos)) {
				$motivo = $motivos['reason']; 
			}

			$cancel = Array (
						'body' => 'Por favor, cancelem o pedido '.$order['numero_marketplace'].'. Motivo: '.$motivo.'. Obrigado', 
						'subject' => 'Favor cancelar pedido '.$order['numero_marketplace'], 
						'to_customer' => false,
						'to_operator' => true,
						'to_shop' => false,
					);

			$json_data = json_encode($cancel);
			$url = 'https://'.$this->getSite().'/api/orders/'.$order['numero_marketplace'].'/messages';
	
			$resp = $this->postMirakl($url, $this->getApikey(), $json_data);
			
		    //var_dump($resp); 

			if (!($resp['httpcode']=="201") )  {  // created
				$erro = json_decode($resp['content'],true);
				$msg_erro = true;
				if (isset($erro['error'])) {
					if ($erro['error'] == "Transição inválida: CANCELED -> CANCELED") { // já estava cancelado no marketplace 
						$msg_erro = false;
					}
				}
				if ($msg_erro) {
					echo "Erro na respota do '.$this->getInt_to().' httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
					echo "http:".$url."\n";
					echo "apikey:".$this->getApikey()."\n";
					$this->log_data('batch',$log_name, 'ERRO ao cancelar pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
					continue;
				}
			}

			$this->ordersmarketplace->cancelOrder($order['id'], true);
			echo 'Pedido de cancelamento em '.$this->getInt_to()."\n";
		} 

	}

	function mandaExcecaoEntrega()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio as ocorrencias de frete com código 5 que não ainda não avisaram ao mktplace o que ocorreu. 
		
		$ocorrencias = $this->model_frete_ocorrencias->getFreightsOcorrenciasByCodigoSemAviso('5');		
		if (count($ocorrencias)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ocorrencia para avisar mktplace '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ocorrencias as $ocorrencia) {
			
			$order=$this->model_orders->getOrdersData(0,$ocorrencia['order_id']);
			if ($order['origin'] != $this->getInt_to()) {
			    echo 'ignorando pedido '.$order['id'].' do mktplace '.$order['origin']."\n";
				continue ; 
			}
			if ($order['exchange_request']) { // pedido de troca não é atualizado  
				echo 'pedido de troca'."\n";
				continue;
			}
			$date_ocorrencia = date("d/m/Y H:i:s",strtotime($ocorrencia['data_ocorrencia']));
			$msg = $ocorrencia['nome'];
			if (!is_null($ocorrencia['mensagem'])) {
				$msg .= ' '.$ocorrencia['mensagem'];
			}
			echo 'Enviando ocorrencia pedido ='.$order['id'].' '.$msg."\n"; 
			
			$ocorr = Array (
						'body' => 'Prezado(a), para a sua informação, a seguinte ocorrencia aconteceu no transporte do pedido '.$order['numero_marketplace'].': '.$msg.' em '.$date_ocorrencia, 
						'subject' => 'Ocorrência no transporte do pedido '.$order['numero_marketplace'], 
						'to_customer' => true,
						'to_operator' => false,
						'to_shop' => false,
					);

			$json_data = json_encode($ocorr);
			$url = 'https://'.$this->getSite().'/api/orders/'.$order['numero_marketplace'].'/messages';
	
			$resp = $this->postMirakl($url,$this->getApikey(),$json_data);
			
		    //var_dump($resp); 

			if (!($resp['httpcode']=="201") )  {  // created
				echo "Erro na respota do '.$this->getInt_to().' httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
				echo "http:".$url."\n";
				echo "apikey:".$this->getApikey()."\n";
				$this->log_data('batch',$log_name, 'ERRO ao mandar exceções de entrega pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}

			$this->model_frete_ocorrencias->updateFreightsOcorrenciaAviso($ocorrencia['id'],'1');
			echo 'Ocorrencia enviada para '.$this->getInt_to()."\n";
		} 

	}
	
	function getMirakl($url, $api_key){
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

	function putMirakl($url, $api_key, $data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "PUT",
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
	
	function putMiraklEmpty($url, $api_key){
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
				'Content-Length: 0'
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
	
	function postMirakl($url, $api_key,$data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "POST",
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
}
?>
