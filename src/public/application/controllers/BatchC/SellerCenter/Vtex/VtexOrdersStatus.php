<?php

require APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 *
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Router $router
 *
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_integrations $model_integrations
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_shipping_company $model_shipping_company
 * @property Model_stores $model_stores
 * @property Model_settings $model_settings
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 * @property Model_products $model_products
 * @property Model_promotions $model_promotions
 * @property Model_product_return $model_product_return
 * @property Model_legal_panel $model_legal_panel
 * @property Model_order_items_cancel $model_order_items_cancel
 * @property Model_conciliation $model_conciliation
 *
 * @property OrdersMarketplace $ordersmarketplace
 * @property CalculoFrete $calculofrete
 * @property \Marketplaces\Utilities\Order $marketplace_order
 */

class VtexOrdersStatus extends BatchBackground_Controller {
    var $int_to='';
    var $apikey='';
    var $site='';
    var $appToken='';
    var $accountName='';
    var $environment='';
    var $dns='.com.br';
	var $linkApiSite;
	var $linkApi = false;
	

    public function __construct()
    {
        parent::__construct();

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
        $this->load->model('model_integrations');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_shipping_company');
        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_vtex_ult_envio');
        $this->load->model('model_products');
        $this->load->model('model_promotions');
        $this->load->model('model_product_return');
        $this->load->model('model_legal_panel');
        $this->load->model('model_legal_panel_fiscal');
        $this->load->model('model_order_items_cancel');
        $this->load->model('model_conciliation');
        $this->load->library('ordersMarketplace');
        $this->load->library('calculoFrete');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');

        $this->load->library('Tunalibrary');

        //Starting Tuna integration library
        $this->integration = new Tunalibrary();
    }

    private function setInt_to($int_to) {
        $this->int_to = $int_to;
    }
    private function getInt_to() {
        return $this->int_to;
    }
    private function setApikey($apikey) {
        $this->apikey = $apikey;
    }
    private function getApikey() {
        return $this->apikey;
    }
    private function setAppToken($appToken) {
        $this->appToken = $appToken;
    }
    private function getAppToken() {
        return $this->appToken;
    }
    private function setAccoutName($accountName) {
        $this->accountName = $accountName;
    }
    private function getAccoutName() {
        return $this->accountName;
    }
    private function setEnvironment($environment) {
        $this->environment = $environment;
    }
    private function getEnvironment() {
        return $this->environment;
    }
    private function setDns($dns) {
        $this->dns = $dns;
    }
    private function getDns() {
        return $this->dns;
    }

    private function getBaseUrlVtex(): string
    {
        return "https://{$this->getAccoutName()}.{$this->getEnvironment()}{$this->getDns()}/api/oms/pvt";
    }

    public function run($id=null,$params=null)
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

        //$this->changeSeller();

        /* faz o que o job precisa fazer */
        if (!is_null($params) && ($params != 'null')) {
        	$integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$params,"CONECTALA","DIRECT",0);
			if (!$integration) {
                $this->log_data('batch',$log_name,"Marketplace $params não encontrado!","E");
                $this->gravaFimJob();
				return;
			}
        }
		else {
			$params = null; 
		}
		echo $params."\n"; 
        $this->cancelaComProblema($params);
        $this->mandaTracking($params);
        $this->mandaNfe($params);
        $this->mandaOcorrencia($params);
        $this->mandaEntregue($params);
        $this->mandaCancelados($params);
        $this->sendRefunded($params);
        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();

    }

    private function setkeys($int_to) {
        $this->setInt_to($int_to);

        //pega os dados da integração. Por enquanto só a conectala faz a integração direta
        $integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$this->getInt_to(),"CONECTALA","DIRECT",0);
        $api_keys = json_decode($integration['auth_data'],true);
        $this->setApikey($api_keys['X_VTEX_API_AppKey'] ?? null);
        $this->setAppToken($api_keys['X_VTEX_API_AppToken'] ?? null);
        $this->setAccoutName($api_keys['accountName'] ?? null);
        $this->setEnvironment($api_keys['environment'] ?? null);
        $this->setDns($api_keys['suffixDns'] ?? '.com.br');
		
        $this->linkApi = false; 
		if (key_exists('apiKey', $api_keys)) {
			$this->apikey = $api_keys['apiKey'];
			$this->linkApiSite = $api_keys['site'] ;
			$this->linkApi = true;
		}
	}
	
    private function mandaTracking($int_to = null)
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        //leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
        $paid_status = '51';

		if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}
		
        if (count($ordens_andamento)==0) {
            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de tracking',"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            echo 'ordem ='.$order['id']."\n";

            $orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

            $frete = $this->model_freights->getFreightsDataByOrderId($order['id']);
            if (count($frete)==0) {
                echo "Sem frete/rastreio \n";
                // Não tem frete, não deveria aconter
                $this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E" );
                $order['paid_status'] = 101; // Precisa contratar o frete manualmente
                $this->model_orders->updateByOrigin($order['id'],$order);
                continue;
            }
            $frete = $frete[0];

            $nfes = $this->model_orders->getOrdersNfes($order['id']);
            if (count($nfes) == 0) {
                echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
                $this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
                // ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50
                continue;
            }
            $nfe = $nfes[0];

            // define as chaves do marketplace
            if ($this->int_to != $order['origin']) {
            	$this->setkeys($order['origin']);
            	if (!$this->apikey) {
	                echo "Credenciais mal configuradas\n";
	                continue;
	            }
            }
                
            $carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/';
            if (!empty($frete['url_tracking']))
                $carrier_url = $frete['url_tracking'];
            else {
                if (!empty($frete['CNPJ'])) {
                    $transportadora = $this->model_shipping_company->getShippingCompanyByCnpjAndStore($frete['CNPJ'], $order['store_id']);
                    if ($transportadora && !is_null($transportadora['tracking_web_site'])) {
                        $carrier_url = $transportadora['tracking_web_site'];
                    }
                }
            }

            $tracking = Array (
                'courier'        => $frete['ship_company'],
                'trackingUrl'    => $carrier_url,
                'trackingNumber' => $frete['codigo_rastreio'],
                "dispatchedDate" => null
            );

            $nfe['nfe_num'] = (int)$nfe['nfe_num'];
            $json_data = json_encode($tracking);
			if ($this->linkApi) {
				$url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}/invoice/{$nfe['nfe_num']}?apiKey=".$this->apikey;
				$resp = $this->restVtex($url, $json_data, array(), 'PATCH');	
			}
			else {
				$url = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/invoice/{$nfe['nfe_num']}";
            	$resp = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'PATCH');
			}
           
            $contentError   = json_decode($resp['content']);

            if ($resp['httpcode'] != 200) {
                $changedInvoice = false;

                // possível erro de nota fiscal errada
                // pode ocorrer quando está sendo enviado uma nfe para a vtex e é trocada no pedido
                // if ($resp['httpcode'] == 500 && isset($contentError->error->code) && $contentError->error->code == 'OMS001') {

                if (
                    ($resp['httpcode'] == 404 && isset($contentError->error->code) && $contentError->error->code == 'NotFound') ||
                    ($resp['httpcode'] == 500 && isset($contentError->error->code) && $contentError->error->code == 'OMS001')
                ){
                    $changedInvoiceRequest = $this->changeNfeOrderDiff($order, $nfe);
                    if ($changedInvoiceRequest === false) continue;
                    elseif ($changedInvoiceRequest === true) $changedInvoice = true;
                    elseif (is_null($changedInvoiceRequest)) { // não achou nenhuma nota fiscal 
                        $respSendNfe = $this->sendNfeVtex($order, $nfe);
                        if ($respSendNfe['httpcode'] == 400) {
                            $vtexresponse = json_decode($respSendNfe['response'],true);
        //                            var_dump($vtexresponse);                            
                            if ($vtexresponse['error']['message'] == 'Unable to set invoice when status is cancel, canceled or cancellation_requested') {
                                echo "Está cancelando na Vtex. Cancelando Aqui\n";
                                if (in_array($order['paid_status'], [1 , 2])) {
                                    return $this->ordersmarketplace->cancelOrder($order['id'], false, false);
                                }
                                if (!in_array($order['paid_status'], [95, 96, 97, 98, 99])) {
                                    $order['paid_status'] = 99;               
                                    $this->model_orders->updateByOrigin($order['id'], $order);
                                }
                                continue; 
                            }
                        }

                        if ($respSendNfe['httpcode'] != 200) {
                            echo "
                                            Erro para atualizar NFE no pedido {$order['id']} em {$this->getInt_to()}\n
                                            URL={$respSendNfe['url']}\n
                                            HTTPCODE={$respSendNfe['httpcode']}\n
                                            RESPOSTA={$respSendNfe['response']}\n
                                            BODY={$respSendNfe['body']}\n";
                            $this->log_data('batch', $log_name, "Erro para atualizar NFE no pedido {$order['id']} em {$this->getInt_to()}\nURL={$respSendNfe['url']}\nHTTPCODE={$respSendNfe['httpcode']}\nRESPOSTA={$respSendNfe['response']}\nBODY={$respSendNfe['body']}", "E");
                            continue;
                        } else {
                            echo "Nota fiscal do pedido {$order['id']} foi enviada. nfe: {$nfe['nfe_num']}\n";
                            $changedInvoice = true; 
                        }                           
                    }
                }

                if (!$changedInvoice) {
                    echo "Erro na respota do " . $this->getInt_to() . ". httpcode=" . $resp['httpcode'] . " RESPOSTA: " . print_r($resp['content'], true) . " \n";
                    echo "http:" . $url . "\n";
                    echo "Dados enviados=" . print_r($json_data, true) . "\n";
                    $this->log_data('batch', $log_name, 'ERRO na gravação da tracking pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $url . ' - httpcode: ' . $resp['httpcode'] . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($resp['content'], true) . ' DADOS ENVIADOS:' . print_r($json_data, true), "E");
                }

                continue;
            }

            $this->log_data('batch',$log_name,"Enviou Tracking \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$json_data}\nRETORNO=".json_encode($resp),"I");

            $order['paid_status'] = 53; // fluxo novo, manda para a rastreio

            $this->model_orders->updateByOrigin($order['id'],$order);
            echo 'Tracking enviado para '.$this->getInt_to()."\n";
        }

    }

    private function mandaNfe($int_to = null)
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        //leio os pedidos com status paid_status = 52 Ordens que já enviou o tracking para o Carrefour
        $paid_status = '52';

		if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}

        if (count($ordens_andamento)==0) {
            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            echo 'ordem ='.$order['id']."\n";

            $orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
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

            // define as chaves do marketplace
            if ($this->int_to != $order['origin'])
                $this->setkeys($order['origin']);

            if (!$this->apikey) {
                echo "Credenciais mal configuradas\n";
                continue;
            }

            $resp = $this->sendNfeVtex($order, $nfe);

            if ($resp['httpcode'] != 200)  {
                $error =  "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA CAR: ".print_r($resp['response'],true)." http:".$resp['url']." Dados enviados=".print_r($resp['body'],true)."\n";
				echo $error."\n";
                $this->log_data('batch',$log_name, $error,"E");
                continue;
            }

            try {
                $this->marketplace_order->updateToInvoiceSentToMarketplace($order);
                echo "NFE do pedido {$order['id']} enviado para {$this->getInt_to()}\n";
            } catch (Exception $exception) {
                echo "Erro para pagar o pedido {$order['id']}. {$exception->getMessage()}\n";
                $this->log_data('batch', $log_name, "Erro para pagar o pedido {$order['id']}.\n{$exception->getMessage()}", 'E');
            }
        }
    }

    private function mandaOcorrencia($int_to = null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        // passar os pedido do status 55 para 5
        // não tem um status de enviado, precisa ir atualizando o rastreio
        // $ordens_atualizar_status = $this->model_orders->getOrdensByPaidStatus(55);
		$paid_status = 55; 
		if (is_null($int_to)) {
			$ordens_atualizar_status = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_atualizar_status = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}

        foreach ($ordens_atualizar_status as $order)
            $this->model_orders->updatePaidStatus($order['id'], 5);

        //$ordens_andamento = $this->model_orders->getOrdensByPaidStatus(5);
		$paid_status = 5; 
		if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}
        if (count($ordens_andamento) == 0) {
            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            echo 'Enviando ocorrencias do pedido ='.$order['id']."\n";

            $orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

            $ocorrencias = $this->model_frete_ocorrencias->getOcorrenciaByOrderId($order['id'], true);

            // não encontrou ocorrências para enviar
            if (!count($ocorrencias)) {
                echo "Não tem novas ocorrencias para enviar\n";
                continue;
            }

            $nfes = $this->model_orders->getOrdersNfes($order['id']);
            if (count($nfes) == 0) {
                echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
                $this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal para enviar as ocorrencias',"E");
                // ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50
                continue;
            }
            $nfe = $nfes[0];

            // define as chaves do marketplace
            if ($this->int_to != $order['origin'])
                $this->setkeys($order['origin']);

            if (!$this->apikey) {
                echo "Credenciais mal configuradas\n";
                continue;
            }

            $arrOcorrencia = array();
            $arrIdOcorrencias = array();
            foreach ($ocorrencias as $ocorrencia) {
                $arrOcorrencia[] = array(
                    "city"          => $ocorrencia['city'] ?? '',
                    "state"         => $ocorrencia['state'] ?? '',
                    "description"   => $ocorrencia['nome'],
                    "date"          => $ocorrencia['data_ocorrencia']
                );

                $arrIdOcorrencias[] = $ocorrencia['id'];
            }

            $invoiced = Array (
                "isDelivered"   => false,
                "events"        => $arrOcorrencia
            );

            $nfe['nfe_num'] = (int)$nfe['nfe_num'];
            $json_data = json_encode($invoiced);
           
			if ($this->linkApi) {
				$url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}/invoice/{$nfe['nfe_num']}/tracking?apiKey=".$this->apikey;
				$resp = $this->restVtex($url, $json_data, array(), 'PUT');	
			}
			else {
				$url = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/invoice/{$nfe['nfe_num']}/tracking";
            	$resp = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'PUT');
			}
            
            $contentError = json_decode($resp['content']);

            if ($resp['httpcode'] != 200) {  // created

                $changedInvoice = false;

                // possível erro de nota fiscal errada
                // pode ocorrer quando está sendo enviado uma nfe para a vtex e é trocada no pedido
                if ($resp['httpcode'] == 400 && isset($contentError->error->code) && $contentError->error->code == 'OMS008') {
                    $changedInvoiceRequest = $this->changeNfeOrderDiff($order, $nfe);
                    if ($changedInvoiceRequest === false) continue;
                    elseif ($changedInvoiceRequest === true) $changedInvoice = true;
                }

                if (!$changedInvoice) {
                    echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA CAR: ".print_r($resp['content'],true)." \n";
                    echo "http:".$url."\n";
                    echo "apikey:".$this->getApikey()."\n";
                    $this->log_data('batch',$log_name, 'ERRO para enviar ocorrencias do pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true),"E");
                }

                continue;
            }

            $this->log_data('batch',$log_name,"Enviou Ocorrencia \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$json_data}\nRETORNO=".json_encode($resp),"I");

            foreach ($arrIdOcorrencias as $idOcorrencia)
                $this->model_frete_ocorrencias->updateFreightsOcorrenciaAviso($idOcorrencia, 1);
        }

    }

    private function mandaEntregue($int_to = null)
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        //leio os pedidos com status paid_status = 60, ordens que já tem mudaram o status para entregue no FreteRastrear
        $paid_status = '60';

        if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}
        // $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        if (count($ordens_andamento)==0) {
            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Entregue da '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            echo 'ordem ='.$order['id']."\n";


            $orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
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

            // define as chaves do marketplace
            if ($this->int_to != $order['origin'])
                $this->setkeys($order['origin']);

            if (!$this->apikey) {
                echo "Credenciais mal configuradas\n";
                continue;
            }

            $ocorrencias = $this->model_frete_ocorrencias->getOcorrenciaByOrderId($order['id'], true);

            $delivered = Array (
                "isDelivered" => true
            );

            $arrOcorrencia = array();
            $arrIdOcorrencias = array();
            foreach ($ocorrencias as $ocorrencia) {
                $arrOcorrencia[] = array(
                    "city"          => $ocorrencia['city'] ?? '',
                    "state"         => $ocorrencia['state'] ?? '',
                    "description"   => $ocorrencia['nome'],
                    "date"          => $ocorrencia['data_ocorrencia']
                );

                $arrIdOcorrencias[] = $ocorrencia['id'];
            }

            if (!empty($arrOcorrencia)) {
                $delivered["events"] = $arrOcorrencia;
            }

            $nfe['nfe_num'] = (int)$nfe['nfe_num'];
            $json_data = json_encode($delivered);
            if ($this->linkApi) {
				$url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}/invoice/{$nfe['nfe_num']}/tracking?apiKey=".$this->apikey;
				$resp = $this->restVtex($url, $json_data, array(), 'PUT');	
			}
			else {
				$url = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/invoice/{$nfe['nfe_num']}/tracking";
            	$resp = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'PUT');
            }
            
            
            $contentError   = json_decode($resp['content']);

            // var_dump($resp);

            if ($resp['httpcode'] != 200) {

                $changedInvoice = false;

                // possível erro de nota fiscal errada
                // pode ocorrer quando está sendo enviado uma nfe para a vtex e é trocada no pedido
                if ($resp['httpcode'] == 400 && isset($contentError->error->code) && $contentError->error->code == 'OMS008') {
                    $changedInvoiceRequest = $this->changeNfeOrderDiff($order, $nfe);
                    if ($changedInvoiceRequest === false) continue;
                    elseif ($changedInvoiceRequest === true) $changedInvoice = true;
                }

                if (!$changedInvoice) {
                    echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n";
                    echo "http:".$url."\n";
                    echo "Dados enviados=".print_r($json_data,true)."\n";
                    $this->log_data('batch',$log_name, 'ERRO na marcacao de pedido entregue pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
                }

                continue;
            }

            foreach ($arrIdOcorrencias as $idOcorrencia) {
                $this->model_frete_ocorrencias->updateFreightsOcorrenciaAviso($idOcorrencia, 1);
            }

            $this->log_data('batch',$log_name,"Entregou o pedido \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$json_data}\nRETORNO=".json_encode($resp),"I");

            // Avisado que foi entregue na transportadora
            $order['paid_status'] = 6; // O pedido está entregue
            $this->model_orders->updateByOrigin($order['id'],$order);
            echo 'Aviso de Entregue enviado para '.$this->getInt_to()."\n";
        }

    }

    private function mandaCancelados($int_to = null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        //leio os pedidos com status paid_status = 99, ordens que canceladas que tem que ser avisadas no Marketplace
        $paid_status = '99';
		if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}

        // $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        if (count($ordens_andamento)==0) {
            $this->log_data('batch',$log_name,'Nenhuma ordem a cancelar '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {

            $timeCancel = null;
            $settingTimeCancel = $this->model_settings->getSettingDatabyName('time_not_return_stock_cancel_order');
            if ($settingTimeCancel && $settingTimeCancel['status'] == 1)
                $timeCancel = (int)$settingTimeCancel['value'];

            echo 'Cancelando pedido ='.$order['id']."\n";

            $orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

            // define as chaves do marketplace
            if ($this->int_to != $order['origin'])
                $this->setkeys($order['origin']);

            if (!$this->apikey) {
                echo "Credenciais mal configuradas\n";
                continue;
            }

            $nfes = $this->model_orders->getOrdersNfes($order['id']);
            $arrItems = array();
            $itens = $this->model_orders->getOrdersItemData($order['id'], true);
            $order_items_cancel = count($nfes) != 0 ? $this->model_order_items_cancel->getItemsCanceledProductsByOrder($order['id']) : array();
            $total_canceled = 0;
            foreach($itens as $item) {
                $item_qty = $item['qty'];
                // Existe cancelamento parcial.
                if (!empty($order_items_cancel)) {
                    $order_item_cancel = getArrayByValueIn($order_items_cancel, $item['id'], 'id');
                    if ($order_item_cancel) {
                        // ignorar o que já está cancelado.
                        $total_canceled += $order_item_cancel['total_amount_canceled_mkt'];
                        $qty_cancel = $order_item_cancel['qty_cancel'];
                        if ($qty_cancel == $item['qty']) {
                            continue;
                        }
                        $item_qty = $item['qty'] - $qty_cancel;
                    }
                }
                $arrItems[] = array(
                    "id"        => $item['mkt_sku_id'],
                    "quantity"  => (int)$item_qty,
                    "price"     => (int)str_replace('.', '', number_format($item['rate'], 2, '.', ''))
                );

                $this->model_orders->insertOrderCancellationTuna([
                    "sku" => $item['mkt_sku_id'],
                    "order_id" => $order['id'],
                ]);
            }

            if (count($nfes) != 0) {

                echo "Pedido {$order['id']} tem NFe\n";

                $nfe = $nfes[0];

                $issuanceDate = DateTime::createFromFormat('d/m/Y H:i:s', $nfe['date_emission']);
                if (!$issuanceDate) {
                    $issuanceDate = DateTime::createFromFormat('d/m/Y H:i:s', date('d/m/Y H:i:s', strtotime($nfe['date_emission'])));
                }
                $net_amount = $order['net_amount'] - $total_canceled;
                $invoiced = array(
                    "type"              => "Input",
                    "invoiceNumber"     => (int)$nfe['nfe_num'] + 1,
                    "courier"           => "",
                    "trackingNumber"    => "",
                    "trackingUrl"       => "",
                    "items"             => $arrItems,
                    "issuanceDate"      => $issuanceDate->format('Y-m-d H:i:s'),
                    "invoiceValue"      => (int)str_replace('.', '', number_format($net_amount, 2, '.', ''))
                );

                $json_data = json_encode($invoiced);
                if ($this->linkApi) {
					$url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}/invoice?apiKey=".$this->apikey;
					$resp = $this->restVtex($url, $json_data, array(), 'POST');	
				}
				else {
					$url = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/invoice";
                	$resp = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'POST');
				}
                $contentError   = json_decode($resp['content']);

                if ($resp['httpcode'] != 200) {

                    $changedInvoice = false;

                    // possível erro de nota fiscal errada
                    // pode ocorrer quando está sendo enviado uma nfe para a vtex e é trocada no pedido
                    if ($resp['httpcode'] == 400 && isset($contentError->error->code) && $contentError->error->code == 'OMS008') {
                        $changedInvoiceRequest = $this->changeNfeOrderDiff($order, $nfe);
                        if ($changedInvoiceRequest === false) continue;
                        elseif ($changedInvoiceRequest === true) $changedInvoice = true;
                    }
                    elseif (
                        is_object($contentError) &&
                        isset($contentError->error->message) &&
                        $contentError->error->message == 'Unable to set invoice when status is cancel, canceled or cancellation_requested'
                    ) {
                        echo "Não permite mais enviar NFE para a Vtex\n";
                        $changedInvoice = true;
                    }

                    if (!$changedInvoice) {
                        echo "Erro na respota do " . $this->getInt_to() . ". httpcode=" . $resp['httpcode'] . " RESPOSTA CAR: " . print_r($resp['content'], true) . " \n";
                        echo "http:" . $url . "\n";
                        echo "Dados enviados=" . print_r($json_data, true) . "\n";
                        $this->log_data('batch', $log_name, 'ERRO no cancelamento da NFE pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $url . ' - httpcode: ' . $resp['httpcode'] . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($resp['content'], true) . ' DADOS ENVIADOS:' . print_r($json_data, true), "E");
                        continue;
                    }                    
                }

                if (count($order_items_cancel)) {
                    $legal_panel_id = $order_items_cancel[0]['legal_panel_id'] ?? null;

                    if (!is_null($legal_panel_id)) {
                        // verificar se o jurídico atual foi executado.
                        $legal_panel_conciliation = $this->model_conciliation->getConciliationByLegalPanelId($legal_panel_id);
                        if ($legal_panel_conciliation) {
                            $this->model_legal_panel->createDebit(
                                $order['id'],
                                "Reembolso de cancelamento de produto.",
                                'Chamado Aberto',
                                str_replace('Cancelamento de produto', 'Reembolso de cancelamento de produto', $legal_panel_conciliation['description']),
                                -$legal_panel_conciliation['balance_debit'], // mandar com o valore negativo.
                                $legal_panel_conciliation['accountable_opening']
                            );
                        } else {
                            $this->model_legal_panel->update(array('status' => 'Chamado Fechado'), $legal_panel_id);
                        }
                    }
                }

                echo "Estornou da Nfe do pedido {$order['id']}\n";
                $this->log_data('batch',$log_name, "Pedido {$order['id']} cancelado(estonou NFe) \n\nURL={$url} \nENVIADO={$json_data} \nRESPOSTA=".json_encode($resp),"I");

                if ($timeCancel === null || ($order['paid_status'] == 1 && time() < strtotime("+{$timeCancel} minutes", strtotime($order['date_time'])))) {
                    foreach ($itens as $item) {
                        $this->model_products->adicionaEstoque($item['product_id'], $item['qty'], $item['variant'], $order['id']); // adiciona estoque no produto
                        $this->model_vtex_ult_envio->adicionaEstoque($order['origin'], $item['product_id'], $item['qty']); // adiciona estoque vtex_ult_envio
                        $this->model_promotions->updatePromotionByStock($item['product_id'], -$item['qty'], $item['rate']); // adiciona estoque caso haja promoção
                    }
                }


                //Cancela o resto do pedido na TUNA
                $gatewayID = $this->model_settings->getSettingDatabyName('payment_gateway_id');
                if($gatewayID['value'] == 8){

                    echo "Manda Cancelados {$order['id']}\n";
                    $this->log_data('batch',$log_name, "Pedido Array - \nRESPOSTA=".json_encode($orderArray),"I");

                    $ordersCancellation = $this->model_orders->getOrderCancellationTuna($order['id']);
                    foreach ($ordersCancellation as $item) {
                        if ($item['status'] == 0)
                            $orderArray = $this->model_orders->getOrdersData(0, $item['id']);
                            echo "Manda Cancelados {$order['id']}\n";
                            
                            $this->integration->geracancelamentotuna($orderArray, $net_amount);
                            $this->model_orders->updateOrderCancellationTuna($order['id']);
                    }
                }


                echo "Pedido {$order['id']} cancelado por aqui, pois não é feito requisição de cancelamento.\n";
            } else {
                // É preciso enviar duas requisições para cancelar o pedido na vtex
                // A primeira requisição o pedido vai para "Aguardando decisão do seller"
                // Na segunda requisição fica como "Cancelado".
                for ($cancelCount = 1; $cancelCount <= 2; $cancelCount++) {

                    // esperar 15s para enviar a proxima requisição.
                    // Para não enviar uma requisição antes da VTEX vir aqui.
                    if ($cancelCount == 2) {
                        echo "Esperar 15s para a próxima requisição\n";
                        sleep(15);
                    }

                    if ($this->linkApi) {
                        $url = $this->linkApiSite . "/api/oms/pvt/orders/{$order['numero_marketplace']}/cancel?apiKey=" . $this->apikey;
                        $resp = $this->restVtex($url, '', array(), 'POST');
                    } else {
                        $url = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/cancel";
                        $resp = $this->restVtex($url, '', array('x-vtex-api-appkey: ' . $this->getApikey(), 'x-vtex-api-apptoken: ' . $this->getAppToken()), 'POST');
                    }

                    if ($resp['httpcode'] != 200) {
                        echo "Erro na respota do " . $this->getInt_to() . ". httpcode=" . $resp['httpcode'] . " RESPOSTA " . $this->getInt_to() . ": " . print_r($resp['content'], true) . " \n";
                        echo "http:" . $url . "\n";
                        $this->log_data('batch', $log_name, 'ERRO para cancelar o pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $url . ' - httpcode: ' . $resp['httpcode'] . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($resp['content'], true), "E");
                        continue 2;
                    }
                    echo "Cancelou o pedido {$order['id']}. {$cancelCount}ªvez\n";
                    $this->log_data('batch', $log_name, "Pedido {$order['id']} cancelado(status de cancelado) {$cancelCount}ªvez \n\nURL={$url} \nRESPOSTA=" . json_encode($resp), "I");
                }
            }

            $incomplete = false;
            if ($order['is_incomplete']) {
                if ($this->linkApi) {
                    $url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}?apiKey=".$this->apikey;
                    $respGetOrder = $this->restVtex($url, '' , array(), 'GET');
                }
                else {
                    $url  = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}";
                    $respGetOrder = $this->restVtex($url, '', array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()));
                }

                $responseGetOrder = json_decode($respGetOrder['content']);
                $httpGetOrder = $respGetOrder['httpcode'];

                if (
                    $httpGetOrder == 200 &&
                    is_object($responseGetOrder) &&
                    property_exists($responseGetOrder, 'isCompleted') &&
                    !$responseGetOrder->isCompleted
                ) {
                    $incomplete = true;
                }
            }

            // Altero o status e estorno o estoque pois não é feito requisição para essa função ser feito por api
            $this->ordersmarketplace->cancelOrder($order['id'], true, true, $incomplete);

            // adicionar log
            $motivos    = $this->model_orders->getPedidosCanceladosByOrderId($order['id']);
            $motivo     = $motivos['motivo_cancelamento'] ?? 'Falta de produto';
            $json_data  = json_encode(array("source" => "Cancelamento", "message" => $motivo));
            if ($this->linkApi) {
				$url    = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}/interactions?apiKey=".$this->apikey;
				$resp   = $this->restVtex($url, $json_data, array(), 'POST');
			}
			else {
				$url    = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/interactions";
            	$resp   = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'POST');
			}	
            echo "Pedido {$order['id']} cancelado em {$this->getInt_to()}\n";
        }
    }

    private function changeSeller($int_to = null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        //leio os pedidos com status paid_status = 70 para trocar de seller
        $paid_status = '70';

		if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}
        // $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        if (count($ordens_andamento)==0) {
            $this->log_data('batch',$log_name,'Nenhuma ordem a trocar de seller '.$int_to,"I");
            return ;
        }

        $setting_cs = $this->model_settings->getSettingDatabyName('token_change_seller');
        if ($setting_cs == false) {
            return [];
        }
        $token_cs = $setting_cs['value'];

        foreach ($ordens_andamento as $order) {

            // Inicia transação
            $this->db->trans_begin();

            echo 'Trocar o pedido '.$order['id']." de seller \n";

            // define as chaves do marketplace
            if ($this->int_to != $order['origin'])
                $this->setkeys($order['origin']);

            if (!$this->apikey) {
                echo "Credenciais mal configuradas\n";
                $this->db->trans_rollback();
                continue;
            }

            $countChange = $this->model_orders->countHistoryChangeSeller($order['id']);

            if ($countChange == 1) { // nunca foi rejeitado

                // monta matriz com os dados para achar o novo seller, com os dados para consultar o frete
                $arrOrder               = array();
                $arrOrder['cep']        = preg_replace('/[^0-9]/', '', $order['customer_address_zip']);
                $arrOrder['service']    = $order['ship_service_preview'];
                $arrOrder['store_id']   = $order['store_id'];
                $arrOrder['items']      = array();
                $logisticsInfo          = array();

                $itens = $this->model_orders->getOrdersItemData($order['id']);

                foreach($itens as $itemIndex => $item) {
                    array_push($arrOrder['items'], array(
                        "sku"           => $item['sku'],
                        "skumkt"        => $item['skumkt'],
                        "qty"           => (int)$item['qty'],
                        "rate"          => $item['rate'],
                        "pesobruto"     => $item['pesobruto'],
                        "largura"       => $item['largura'],
                        "altura"        => $item['altura'],
                        "profundidade"  => $item['profundidade'],
                    ));
                    array_push($logisticsInfo, array(
                        "itemIndex"                 => $itemIndex,
                        "selectedSla"               => $order['ship_service_preview'],
                        "selectedDeliveryChannel"   => "delivery"
                    ));
                }

                // recuperar o seller com a entrega mais rápida
                $newSeller = $this->getSellerWithFastDelivery($arrOrder);

                $store_id   = $newSeller['store_id'];
                $deadline   = $newSeller['deadline'];
                $value      = $newSeller['value'];

                if ($store_id) { // encontrou um seller
                    // Chama o changeseller da vtex indicando o novo seller
        //                    $url = "https://{$this->getAccoutName()}.{$this->getEnvironment()}{$this->getDns()}/api/checkout/pvt/orders/{$order['numero_marketplace']}/sellers";
        //                    $datachangeSeller = array(
        //                        "marketplacePaymentValue" => (int)str_replace('.', '', number_format($order['gross_amount'], 2, '.', '')),
        //                        "newSellerId" => $store_id,
        //                        "shippingData" => array(
        //                            "logisticsInfo" => $logisticsInfo
        //                        )
        //                    );
        //                    $resp = $this->restVtex($url, '', array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'PUT');
        //
        //                    if ($resp['httpcode'] == 404) {
        //                        $response = json_decode($resp['content']);
        //                        if (isset($response->error->code) && $response->error->code == "OMS007") {
        //                            $this->model_orders->updatePaidStatus($order['id'], 97);
        //                            echo "Cancelado com erro - ( {$order['numero_marketplace']} ) - ( {$order['id']} )\n";
        //                        }
        //                    }

                    $response['http_code'] = 200; // TESTE (Pedro)

                    if ($response['http_code'] != 200) {
                        echo "HTTP_CODE={$response['http_code']} - RESPONSE={$response['content']}\n";
                        $this->log_data('batch', $log_name, "Não foi possível trocar o seller - HTTP_CODE={$response['http_code']} - RESPONSE={$response['content']}", "E");
                        $this->db->trans_rollback();
                        continue;
                    }

                    foreach($itens as $item) {

                        // voltar estoque do seller que pediu troca
                        $this->model_products->adicionaEstoque($item['product_id'], $item['qty'], $item['variant'], $order['id']); // adiciona estoque produto
                        $this->model_vtex_ult_envio->adicionaEstoque($order['origin'], $item['product_id'], $item['qty']); // adiciona estoque vtex_ult_envio
                        $this->model_promotions->updatePromotionByStock($item['product_id'], -$item['qty'], $item['rate']); // adiciona estoque caso haja promoção

                        // trocar item de seller
                        $dataVtexUltEnvio = $this->model_vtex_ult_envio->getProductBySkumktAndStore($item['skumkt'], $store_id);
                        $this->model_orders->updateItenByOrderAndId($item['id'], [
                                'sku' => $dataVtexUltEnvio['sku'],
                                'product_id' => $dataVtexUltEnvio['prd_id'],
                                'company_id' => $dataVtexUltEnvio['company_id'],
                                'store_id' => $store_id
                            ]
                        );

                        // retirar estoque do novo seller
                        $this->model_products->reduzEstoque($dataVtexUltEnvio['prd_id'], $item['qty'], $item['variant'], $order['id']); // tira estoque produto
                        $this->model_vtex_ult_envio->reduzEstoque($order['origin'], $dataVtexUltEnvio['prd_id'], $item['qty']); // tira estoque vtex_ult_envio
                        $this->model_promotions->updatePromotionByStock($dataVtexUltEnvio['prd_id'], $item['qty'], $item['rate']); // tira estoque caso haja promoção
                    }

                    //trocar pedido de seller
                    $this->model_orders->updateByOrigin($order['id'], ['paid_status' => $order['data_pago'] ? 3 : 1, 'store_id' => $store_id, 'company_id' => $dataVtexUltEnvio['company_id'], 'first_change_seller' => $order['store_id']]);
                    // cancelar pedido no erp do seller caso tenha sido pago, caso use integração
                    $this->model_orders->createOrderToIntegration($order['id'], $order['company_id'], $order['store_id'], $order['data_pago'] ? 97 : 96, 0);
                    // deixar o novo pedido com o new_order=1, para ser integrado no erp do seller, caso use integração
                    $this->model_orders->updateOrderToIntegrationByOrderAndStatus($order['id'], $store_id, $order['data_pago'] ? 3 : 1, ['new_order' => 1]);

                } else { // não encontrou seller para atender
                    echo json_encode('Não encontrou seller')."\n";
                    // chamar o changeseller da Grupo Soma pedindo um novo seller
                    $this->requestRealocate($order, $token_cs, 'Seller center não encontrou seller para atender o pedido!');
                }

                // altera o pedido internamente para o novo seller e marca o pedido como rejeitado 1 vez
            } else { // já foi rejeitado
                echo json_encode('Ja foi rejeitado uma vez')."\n";
                // chamar o changeseller da Grupo Soma pedindo um novo seller
                $this->requestRealocate($order, $token_cs, $countChange.'ª tentativa de troca de seller');
            }

            echo json_encode('Fim')."\n";

            if ($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $this->log_data('api',$log_name,"Erro para executar as queries de troca de seller. PEDIDO={$order['id']}","E");
                return false;
            }
            $this->db->trans_commit();
        }
        die;
    }

    /**
     * Cancela pedidos que foi retornado sucesso, mas
     * deu problema na VTEX para continuar o processo
     * de pagamento
     */
    private function cancelaComProblema($int_to = null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
	
        $dateFilter = date('Y-m-d', strtotime('-1 day', time()));

		if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdersByFilter("paid_status = 1 AND date_time >= '{$dateFilter}'");
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdersByFilter("paid_status = 1 AND date_time >= '{$dateFilter}' AND origin = '{$int_to}'");
		}

        if (count($ordens_andamento)==0) {
            $this->log_data('batch',$log_name,'Nenhuma ordem a cancelar por erro '.$int_to ,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            // define as chaves do marketplace
            if ($this->int_to != $order['origin'])
                $this->setkeys($order['origin']);

            if (!$this->apikey) {
                echo "Credenciais mal configuradas\n";
                continue;
            }

            if ($this->linkApi) {
				$url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}?apiKey=".$this->apikey;
				$resp = $this->restVtex($url, '', array(), 'GET');	
			}
			else {
				$url = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}";
            	$resp = $this->restVtex($url, '', array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'GET');
			}
		   
		    $response = json_decode($resp['content']);
			//var_dump($response);
			
            if ($resp['httpcode'] == 404 && isset($response->error->code) && $response->error->code == "OMS007") {
                $this->model_orders->updatePaidStatus($order['id'], 96);
                echo "Cancelado com erro - ( {$order['numero_marketplace']} ) - ( {$order['id']} )\n";
            }
        }
    }

    private function restVtex($url, $data, $httpHeader = array(), $method = 'GET'){

        $httpHeader = array_merge(array(
            'Accept: application/json',
            'Content-Type: application/json'
        ), $httpHeader);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING 	   => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER 	   => $httpHeader
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

    private function getSellerWithFastDelivery($order)
    {

        $data_freight       = array();
        $smallestMeasure    = array();
        $items              = array();
        $logistic           = array();
        $arrServicos        = array();
        $itemsToMeet        = array();
        $qtyProduct         = array();

        $setting_services_id    = $this->model_settings->getSettingDatabyName('services_id_sgp');
        $servico_nome 			= json_decode($setting_services_id['value']);
        $orderService           = strtoupper($order['service']);
        $serviceSend            = empty($order['service']) ? $servico_nome->PAC : $servico_nome->$orderService;

        foreach ($order['items'] as $index => $iten) {
            $countFreight           = 0;
            $altura_correios        = 0;
            $largura_correios       = 0;
            $comprimento_correios   = 0;
            $sku                    = $iten['sku'];
            $skumkt                 = $iten['skumkt'];
            $qty_item_prd           = $iten['qty'];
            $cepDestino             = $order['cep'];
            $qtyProduct[$skumkt]    = $qty_item_prd;

            array_push($itemsToMeet, $iten['skumkt']);

            for ($qty_iten = 0; $qty_iten < $qty_item_prd; $qty_iten++) {

                $profundidade_iten  = $iten['profundidade'];
                $largura_iten       = $iten['largura'];
                $altura_iten        = $iten['altura'];
                $peso_iten          = $iten['pesobruto'];

                $comprimento_correios   += $profundidade_iten;
                $largura_correios       += $largura_iten;
                $altura_correios        += $altura_iten;

                if ($comprimento_correios > 105 || $largura_correios > 105 || $altura_correios > 105) {
                    $countFreight++;
                    $comprimento_correios = $profundidade_iten;
                    $largura_correios = $largura_iten;
                    $altura_correios = $altura_iten;
                }

                if ($largura_correios < $comprimento_correios) $smallestMeasure[$skumkt][$countFreight] = "L";
                if ($altura_correios < $comprimento_correios) $smallestMeasure[$skumkt][$countFreight] = "A";

                if ($comprimento_correios <= $largura_correios && $comprimento_correios <= $altura_correios) $smallestMeasure[$skumkt][$countFreight] = "C";
                if ($largura_correios <= $comprimento_correios && $largura_correios <= $altura_correios) $smallestMeasure[$skumkt][$countFreight] = "L";
                if ($altura_correios <= $comprimento_correios && $altura_correios <= $largura_correios) $smallestMeasure[$skumkt][$countFreight] = "A";
                if ($altura_correios == $comprimento_correios && $altura_correios == $largura_correios) $smallestMeasure[$skumkt][$countFreight] = "A";

                $data_freight[$skumkt][$countFreight] = array(
                    'altura'        => $altura_iten,
                    'largura'       => $largura_iten,
                    'profundidade'  => $profundidade_iten,
                    'peso_bruto'    => $peso_iten,
                    'qty'           => $qty_iten + 1,
                    "rate"          => (float)$iten['rate'],
//                    "prd_id"        => $iten['prd_id']
                );
            }
        }

        foreach ($data_freight as $index => $data_item) {
            $arrServicos[$index] = array();
            foreach ($data_item as $idFreight => $freight) {

                $peso           = 0;
                $comprimento    = 0;
                $largura        = 0;
                $altura         = 0;
                $quantidade     = 0;

                if ($smallestMeasure[$index][$idFreight] == null)
                    $smallestMeasure[$index][$idFreight] = "A";

                switch ($smallestMeasure[$index][$idFreight]) {
                    case "L":
                        $comprimento = $comprimento < (float)$freight['profundidade'] ? (float)$freight['profundidade'] : $comprimento;
                        $altura = $altura < (float)$freight['altura'] ? (float)$freight['altura'] : $altura;

                        $largura += (float)$freight['largura'] * (int)$freight['qty'];
                        break;
                    case "C":
                        $altura = $altura < (float)$freight['altura'] ? (float)$freight['altura'] : $altura;
                        $largura = $largura < (float)$freight['largura'] ? (float)$freight['largura'] : $largura;

                        $comprimento += $freight['profundidade'] * (int)$freight['qty'];
                        break;
                    case "A":
                        $largura = $largura < (float)$freight['largura'] ? (float)$freight['largura'] : $largura;
                        $comprimento = $comprimento < (float)$freight['profundidade'] ? (float)$freight['profundidade'] : $comprimento;

                        $altura += (float)$freight['altura'] * (int)$freight['qty'];
                        break;
                }

                $peso += (float)$freight['peso_bruto'] * (int)$freight['qty'];
                $valor_declarado = (float)$freight['rate'];
                $preco_item = (float)$freight['rate'];
                $quantidade += (int)$freight['qty'];

                // medidas e valor minímo para correios
                $comprimento     = $comprimento < 16 ? 16 : $comprimento;
                $largura         = $largura < 11 ? 11 : $largura;
                $altura          = $altura < 2 ? 2 : $altura;
                $valor_declarado = $valor_declarado < 20.50 ? 20.50 : $valor_declarado;
                $valor_declarado = $valor_declarado > 3000 ? 3000 : $valor_declarado;

                $arrTestaServico[$index][$idFreight] = array();
                $arrTestaServico[$index][$idFreight]['identificador'] = $idFreight;
                $arrTestaServico[$index][$idFreight]['cep_origem'] = '';
                $arrTestaServico[$index][$idFreight]['cep_destino'] = $cepDestino;
                $arrTestaServico[$index][$idFreight]['formato'] = "1";
                $arrTestaServico[$index][$idFreight]['peso'] = number_format($peso, 3, ',', '');
                $arrTestaServico[$index][$idFreight]['comprimento'] = number_format($comprimento, 2, ',', '');
                $arrTestaServico[$index][$idFreight]['altura'] = number_format($altura, 2, ',', '');
                $arrTestaServico[$index][$idFreight]['largura'] = number_format($largura, 2, ',', '');
                $arrTestaServico[$index][$idFreight]['valor_declarado'] = number_format($valor_declarado, 2, ',', '');
                $arrTestaServico[$index][$idFreight]['mao_propria'] = "N";
                $arrTestaServico[$index][$idFreight]['aviso_recebimento'] = "N";
                $arrTestaServico[$index][$idFreight]['servicos'] = array($serviceSend);
//                $arrTestaServico[$index][$idFreight]['prd_id'] = $freight['prd_id'];
            }
        }

        $setting_sgp = $this->model_settings->getSettingDatabyName('token_sgp_correios');
        if ($setting_sgp == false) {
            return [];
        }
        $token_sgp = $setting_sgp['value'];
        $arrayResult = array(
            'store_id'  => null,
            'deadline'  => null,
            'value'     => null
        );

        // rj - 08532410
        // sc - 88049445
        // ba - 41500620
        // sp - 01034902

        $_stores = $this->db
            ->select('vtex_ult_envio.zipcode, vtex_ult_envio.store_id, vtex_ult_envio.skumkt, products.qty')
            ->from('vtex_ult_envio')
            ->join('products', 'products.id = vtex_ult_envio.prd_id')
            ->where(
                array(
                    'products.qty >' => 0,
                    'vtex_ult_envio.store_id !=' => $order['store_id']
                )
            )
            ->where_in('vtex_ult_envio.skumkt', $itemsToMeet)
            ->get()
            ->result_array();

        $stores = array();
        foreach ($_stores as $_store) {
            if (!key_exists($_store['store_id'], $stores)) $stores[$_store['store_id']] = array();

            array_push($stores[$_store['store_id']], ['qty' => $_store['qty'], 'skumkt' => $_store['skumkt'], 'zipcode' => $_store['zipcode']]);
        }

        foreach ($stores as $store_id => $store) {

            foreach ($store as $dataStore) {
                $cepOrigem = $dataStore['zipcode'];
                if ($dataStore['qty'] < $qtyProduct[$dataStore['skumkt']]) continue 2;
            }

            $valor = 0;
            $prazo = 0;

            foreach ($arrTestaServico as $freights) {

                foreach ($freights as $freight) {

                    $freight['cep_origem'] = $cepOrigem;

                    // consulta no sgp preço e prazo com o serviço
                    $url = "https://gestaodeenvios.com.br/sgp_login/v/2.2/api/consulta-precos-prazos?chave_integracao={$token_sgp}";
                    $data_json = json_encode($freight);
                    $data_retorno = $this->restVtex($url, $data_json, array(), 'POST');
                    $retorno_decode = json_decode($data_retorno['content']);

                    if (!isset($retorno_decode->servicos)) continue;

                    foreach ($retorno_decode->servicos as $service) {

                        if (isset($service->Valor) && isset($service->PrazoEntrega) && $service->Valor != "0,00" && $service->PrazoEntrega != 0) {

                            $_valor = (float)number_format($this->fmtNum($service->Valor), 2, '.', '');
                            $_prazo = (int)$service->PrazoEntrega;

                            if ($serviceSend == $service->Codigo) {
                                $valor += $_valor;
                                $prazo = $_prazo > $prazo ? $_prazo : $prazo;
                            }
                        }
                    }
                }
            }

            if (!$arrayResult['deadline'] && $valor) {
                $arrayResult['value'] = $valor;
                $arrayResult['deadline'] = $prazo;
                $arrayResult['store_id'] = $store_id;
            }
            if ($valor && $prazo < $arrayResult['deadline']) {
                $arrayResult['value'] = $valor;
                $arrayResult['deadline'] = $prazo;
                $arrayResult['store_id'] = $store_id;
            }
            if ($prazo == $arrayResult['deadline'] && $valor < $arrayResult['value']) {
                $arrayResult['value'] = $valor;
                $arrayResult['deadline'] = $prazo;
                $arrayResult['store_id'] = $store_id;
            }
        }

        return $arrayResult;
    }

    private function requestRealocate($order, $token_cs, $reason)
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
//        $response = $this->restVtex(
//            "https://gestao-omni-dot-apt-bonbon-179602.appspot.com/v0/orders/{$orderMkt['numero_marketplace']}/realocate",
//            json_encode([
//                'algorithm' => 'conecta-la-teste'
//            ]),
//            array('Authorization' => $token_cs),
//            'POST'
//        );
        // TESTE (Pedro)
        $response['http_code'] = 200;
        $response['content'] = '{}';

        $content = json_decode($response['content']);

        // Não há sellers disponíveis!
        if ($response['http_code'] == 500 && isset($content->message) && $content->message == 'Não há sellers disponíveis!') {
            echo "HTTP_CODE={$response['http_code']} - RESPONSE={$response['content']}\n";
            $this->log_data('batch', $log_name, "Não foi possível realocar o seller para outro pedido - HTTP_CODE={$response['http_code']} - RESPONSE={$response['content']}", "E");
        }
        // cancelar pedido
        elseif ($response['http_code'] == 200) {

            $itens = $this->model_orders->getOrdersItemData($order['id']);
            foreach ($itens as $item) {
                $this->model_products->adicionaEstoque($item['product_id'], $item['qty'], $item['variant'], $order['id']); // adiciona estoque produto
                $this->model_vtex_ult_envio->adicionaEstoque($order['origin'], $item['product_id'], $item['qty']); // adiciona estoque vtex_ult_envio
                $this->model_promotions->updatePromotionByStock($item['product_id'], -$item['qty'], $item['rate']); // adiciona estoque caso haja promoção
            }

            $data = array(
                'order_id'      => $order['id'],
                'reason'        => $reason,
                'date_update'   => date("Y-m-d H:i:s"),
                'status'        => 1,
                'penalty_to'    => $this->ordersmarketplace->getCancelReasonDefault(),//'1-Seller',
                'user_id'       => 1,
                'store_id'      => $order['first_change_seller'] ?? $order['store_id']
            );
            $this->model_orders->insertPedidosCancelados($data);
            $this->model_orders->updatePaidStatus($order['id'], $order['data_pago'] ? 97 : 96);
        } else {
            echo "HTTP_CODE={$response['http_code']} - RESPONSE={$response['content']}\n";
            $this->log_data('batch', $log_name, "Não foi possível realocar o seller para outro pedido - HTTP_CODE={$response['http_code']} - RESPONSE={$response['content']}", "E");
        }
    }

    private function sendNfeVtex($order, $nfe)
    {
        try {
            $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
            if ($external_marketplace_integration) {
                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                $this->marketplace_order->external_integration->notifyNfeValidation($order['id']);
                echo "Validação de integrador externo validando a NFe para o pedido $order[id]\n";
                $order_updated = $this->model_orders->getOrdersData(0, $order['id']);

                if (
                    $this->model_orders->PAID_STATUS['sented_nfe_to_marketplace'] == $order['paid_status'] &&
                    $this->model_orders->PAID_STATUS['checking_invoice'] == $order_updated['paid_status']
                ) {
                    return [
                        'url'       => '',
                        'httpcode'  => 400,
                        'response'  => "Pedido $order[id] enviado para validação da nota fiscal.",
                        'body'      => ''
                    ];
                }
            }
        } catch (Exception | Error $exception) {
            $this->log_data('batch', __CLASS__.'/'.__FUNCTION__, "Não foi possível faturar o integrador externo não aprovou a NFe enviada ou algum dado está inválido para o pedido $order[id]. {$exception->getMessage()}", "E");
            return [
                'url'       => '',
                'httpcode'  => 400,
                'response'  => "Não foi possível faturar o integrador externo não aprovou a NFe enviada ou algum dado está inválido para o pedido $order[id]. {$exception->getMessage()}",
                'body'      => ''
            ];
        }

        $arrItems = array();
        $order_items_cancel = $this->model_order_items_cancel->getItemsCanceledProductsByOrder($order['id']);
        $itens = $this->model_orders->getOrdersItemData($order['id'], true);

        // Itens da nota fiscal.
        $arrItemsToCancel = array();
        $description_legal_panel  = array();
        $value_cancel_order = 0;
        $value_invoice_order = $order['net_amount'];
        $value_cancel_shipping = 0;
        $value_invoice_shipping = 0;
        if (count($order_items_cancel)) {
            $value_invoice_order = 0;
            $urlOrder  = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}";
            $respGetOrder = $this->restVtex($urlOrder, '', array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()));

            if ($respGetOrder['httpcode'] != 200) {
                echo "Não encontrou o pedido $order[id], com o código no marketplace $order[numero_marketplace].\n";
                return [
                    'url'       => $urlOrder,
                    'httpcode'  => $respGetOrder['httpcode'],
                    'response'  => $respGetOrder['content'],
                    'body'      => json_decode($respGetOrder['content'])
                ];
            }

            $responseGetOrder = json_decode($respGetOrder['content']);
            $logisticsInfos = $responseGetOrder->shippingData->logisticsInfo;

            // Itens da nota fiscal de cancelamento.
            $arrItemsToCancel = array_map(function($item) use ($responseGetOrder, $logisticsInfos, &$value_cancel_shipping, &$value_cancel_order, &$description_legal_panel) {
                $item_index = getArrayByValueIn($responseGetOrder->items, $item['skumkt'], 'sellerSku', true);
                $item_vtex = getArrayByValueIn($responseGetOrder->items, $item['skumkt'], 'sellerSku');
                $logisticsInfo  = $logisticsInfos[$item_index];

                $shipping_price = moneyVtexToFloat($logisticsInfo->price);

                // Se devolveu todas as unidades, devolve o valor integral.
                if ($item['qty'] == $item['qty_cancel']) {
                    $cancel_shipping = $shipping_price;
                }
                // Se não devolveu todas as unidades, devolve o valor parcial.
                else {
                    $cancel_shipping = ($shipping_price / $item['qty']) * $item['qty_cancel'];
                }

                $cancel_item = ($cancel_shipping + ($item['rate'] * $item['qty_cancel']));

                $value_cancel_shipping  += $cancel_shipping;
                $value_cancel_order     += $cancel_item;
                $total_amount_cancel    = $cancel_item;

                $this->model_order_items_cancel->updateByItemId($item['id'], array('total_amount_canceled_mkt' => $total_amount_cancel));

                $description_legal_panel_str = "(Producto ($item[product_id]";
                if (!is_null($item['variant']) && $item['variant'] !== '') {
                    $description_legal_panel_str .= " - Variação ($item[variant])";
                }
                $description_legal_panel_str .= ") ";

                $description_legal_panel[] = $description_legal_panel_str;

                return array(
                    "id"        => $item_vtex->id, //$item['skumkt'],
                    "quantity"  => $item['qty_cancel'],
                    "price"     => moneyFloatToVtex($item['rate'])
                );
            }, $order_items_cancel);

            $arrItemsToInvoice = array_filter(array_map(function($item) use ($order_items_cancel, $logisticsInfos, $responseGetOrder, &$value_invoice_shipping, &$value_invoice_order) {
                $item_index = getArrayByValueIn($responseGetOrder->items, $item['skumkt'], 'sellerSku', true);
                $logisticsInfo  = $logisticsInfos[$item_index];

                $shipping_price = moneyVtexToFloat($logisticsInfo->price);

                $qty = (int)$item['qty'];

                $item_cancel = getArrayByValueIn($order_items_cancel, $item['id'], 'id');
                if ($item_cancel) {
                    $qty -= (int)$item_cancel['qty_cancel'];
                }

                // Cancelou tudo
                if (empty($qty)) {
                    return null;
                }
                // Se não devolveu todas as unidades, devolve o valor parcial.
                $invoice_shipping = ($shipping_price / $item['qty']) * $qty;

                $value_invoice_shipping += $invoice_shipping;
                $value_invoice_order    += ($invoice_shipping + ($item['rate'] * $qty));

                $this->model_orders->insertOrderCancellationTuna([
                    "sku" => $item['mkt_sku_id'],
                    "order_id" => $item['id'],
                ]);

                return array(
                    "id"        => $item['mkt_sku_id'],
                    "quantity"  => $qty,
                    "price"     => moneyFloatToVtex($item['rate'])
                );
            }, $itens), function($item) {
                return !is_null($item);
            });

            if (empty($arrItemsToCancel) || empty($arrItemsToInvoice)) {
                return [
                    'url'       => '',
                    'httpcode'  => 0,
                    'response'  => 'Dados para faturar incompletos. arrItemsToCancel=' . json_encode($arrItemsToCancel) . ' | arrItemsToInvoice=' . json_encode($arrItemsToInvoice),
                    'body'      => 'Dados para faturar incompletos. arrItemsToCancel=' . json_encode($arrItemsToCancel) . ' | arrItemsToInvoice=' . json_encode($arrItemsToInvoice)
                ];
            }

            rsort($arrItemsToInvoice);
        } else {
            
            $arrItemsToInvoice = array_map(function($item) {

                $this->model_orders->insertOrderCancellationTuna([
                    "sku" => $item['mkt_sku_id'],
                    "order_id" => $item['id'],
                ]);

                return array(
                    "id"        => $item['mkt_sku_id'],
                    "quantity"  => (int)$item['qty'],
                    "price"     => moneyFloatToVtex($item['rate'])
                );
            }, $itens);
        }
        $value_invoice_order    = roundDecimal($value_invoice_order);
        $value_cancel_order     = roundDecimal($value_cancel_order);
        $value_cancel_shipping  = roundDecimal($value_cancel_shipping);
        $value_invoice_shipping = roundDecimal($value_invoice_shipping);

        $issuanceDate = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission']);

        if (!$issuanceDate) {
            $issuanceDate = DateTime::createFromFormat('d/m/Y H:i:s', date('d/m/Y H:i:s', strtotime($nfe['date_emission'])));
        }

        if (count($order_items_cancel) && ($value_invoice_order + $value_cancel_order) != $order['net_amount']) {
            $diff = $order['net_amount'] - ($value_invoice_order + $value_cancel_order);
            if ($diff <= 0) {
                $diff = ($value_invoice_order + $value_cancel_order) - $order['net_amount'];
            }
            $value_cancel_order -= $diff;
        }

        $invoiced = Array (
            "type"           => "Output",
            "invoiceNumber"  => (int)$nfe['nfe_num'],
            "invoiceKey"  	 => $nfe['chave'],
            'invoiceUrl'     => 'https://www.nfe.fazenda.gov.br/portal/consultaRecaptcha.aspx',
            "courier"        => "",
            "trackingNumber" => "",
            "trackingUrl"    => "",
            "items"          => $arrItemsToInvoice,
            "issuanceDate"   => $issuanceDate->format('Y-m-d H:i:s'),
            "invoiceValue"   => moneyFloatToVtex($order['net_amount'])
        );

        $json_data = json_encode($invoiced);
       
		if ($this->linkApi) {
			$url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}/invoice?apiKey=".$this->apikey;
			$resp = $this->restVtex($url, $json_data, array(), 'POST');	
		}
		else {
			$url = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/invoice";
        	$resp = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'POST');
		}

        if ($resp['httpcode'] < 200 || $resp['httpcode'] > 299) {
            return [
                'url'       => $url,
                'httpcode'  => $resp['httpcode'],
                'response'  => $resp['content'],
                'body'      => $json_data
            ];
        }

        if (count($order_items_cancel)) {
            $cancel_invoice = ((int)$nfe['nfe_num']) + 2;
            $cancelInvoiced = array(
                "type"           => "Input",
                "invoiceNumber"  => $cancel_invoice,
                'invoiceUrl'     => 'https://www.nfe.fazenda.gov.br/portal/consultaRecaptcha.aspx',
                "items"          => $arrItemsToCancel,
                "issuanceDate"   => $issuanceDate->format('Y-m-d H:i:s'),
                "invoiceValue"   => moneyFloatToVtex($value_cancel_order)
            );

            $json_cancel_data = json_encode($cancelInvoiced);

            if ($this->linkApi) {
                $cancelUrl = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}/invoice?apiKey=".$this->apikey;
                $cancelResp = $this->restVtex($cancelUrl, $json_cancel_data, array(), 'POST');
            }
            else {
                $cancelUrl = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}/invoice";
                $cancelResp = $this->restVtex($cancelUrl, $json_cancel_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'POST');
            }

            if ($cancelResp['httpcode'] < 200 || $cancelResp['httpcode'] > 299) {
                $message = "Erro na confirmação do estorno na nfe de entrada(cancelamento parcial). de={$this->getInt_to()}\nendpoinbt=$cancelUrl\nhttpcode=$cancelResp[httpcode]\nresponse=$cancelResp[content]\nrequest=$json_cancel_data";
                echo $message . "\n";
                $this->log_data('batch', __CLASS__ . '/' . __FUNCTION__, $message, "E");
            } else {

                if ($order['freight_seller']) {
                    $description_legal_panel[] = " Frete de: " . money($value_cancel_shipping);
                }

                // Criar débito no extrato.
                $this->model_legal_panel->createDebit(
                    $order['id'],
                    "Cancelamento de produto.",
                    'Chamado Aberto',
                    'Cancelamento de produto. ' . implode(', ', $description_legal_panel),
                    $order['freight_seller'] ? $value_cancel_order : ($value_cancel_order - $value_cancel_shipping),
                    'Rotina API'
                );

                $legal_panel_id = $this->db->insert_id();

                foreach ($order_items_cancel as $item_cancel) {
                    $this->model_order_items_cancel->updateById($item_cancel['id'], array('legal_panel_id' => $legal_panel_id));
                }

                //envia o cancelamento para a Tuna caso o Gateway esteja configurado para ele -- payment_gateway_id
                $gatewayID = $this->model_settings->getSettingDatabyName('payment_gateway_id');
                if($gatewayID['value'] == 8){

                    $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

                    $valorEstorno = $value_cancel_order;
                    $ordersCancellation = $this->model_orders->getOrderCancellationTuna($order['id']);
                    foreach ($ordersCancellation as $item) {
                        if ($item['status'] == 0)
                            $orderArray = $this->model_orders->getOrdersData(0, $item['id']);

                            echo "Manda Cancelados {$order['id']}\n";
                            $this->log_data('batch',$log_name, "Pedido Array - \nRESPOSTA=".json_encode($orderArray),"I");

                            $this->integration->geracancelamentotuna($orderArray, $valorEstorno);
                            $this->model_orders->updateOrderCancellationTuna($order['id']);
                    }
                }

                if ($this->linkApi) {
                    $deliveryUrl = $this->linkApiSite . "/api/oms/pvt/orders/$order[numero_marketplace]/invoice/$cancel_invoice/tracking";
                    $deliveryResp = $this->restVtex($deliveryUrl, json_encode(array("isDelivered" => true)), array(), 'PUT');
                } else {
                    $deliveryUrl = "{$this->getBaseUrlVtex()}/orders/$order[numero_marketplace]/invoice/$cancel_invoice/tracking";
                    $deliveryResp = $this->restVtex($deliveryUrl, json_encode(array("isDelivered" => true)), array('x-vtex-api-appkey: ' . $this->getApikey(), 'x-vtex-api-apptoken: ' . $this->getAppToken()), 'PUT');
                }

                if ($deliveryResp['httpcode'] < 200 || $deliveryResp['httpcode'] > 299) {
                    $message = "Erro na confirmação da entrega do cancelamento de={$this->getInt_to()}\nendpoinbt=$deliveryUrl\nhttpcode=$deliveryResp[httpcode]\nresponse=$deliveryResp[content]";
                    echo $message . "\n";
                    $this->log_data('batch', __CLASS__ . '/' . __FUNCTION__, $message, "E");
                }
            }
        }

        return [
            'url'       => $url,
            'httpcode'  => $resp['httpcode'],
            'response'  => $resp['content'],
            'body'      => $json_data
        ];
    }

    private function changeNfeOrderDiff($order, $nfe)
    {
        $log_name           = __CLASS__.'/'.__FUNCTION__;
      //  $urlGetOrder       = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}";
      //  $respGetOrder       = $this->restVtex($urlGetOrder, '', array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()));
        if ($this->linkApi) {
			$url = $this->linkApiSite ."/api/oms/pvt/orders/{$order['numero_marketplace']}?apiKey=".$this->apikey;
			$respGetOrder = $this->restVtex($url, '' , array(), 'GET');	
		}
		else {
			$url  = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}";
        	$respGetOrder = $this->restVtex($url, '', array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'GET');
		}
        
        $responseGetOrder   = json_decode($respGetOrder['content']);
        echo "Pedido com status ".$responseGetOrder->status."\n";
        $httpGetOrder       = $respGetOrder['httpcode'];

        if ($httpGetOrder == 200 && isset($responseGetOrder->packageAttachment->packages) && count($responseGetOrder->packageAttachment->packages)) {

            $invoiceDiff = true;
            foreach ($responseGetOrder->packageAttachment->packages as $pack) {
                // verifica se existe nfe e se está diferente do que está no seller center
                if ($pack->invoiceNumber == $nfe['nfe_num'] && strlen($nfe['nfe_num']) == strlen($pack->invoiceNumber))
                    $invoiceDiff = false;
            }
            if ($invoiceDiff) {
                echo "Pedido {$order['id']} está com NFe diferente do que está no pedido.\n";
                $respSendNfe = $this->sendNfeVtex($order, $nfe);

                if ($respSendNfe['httpcode'] != 200) {
                    echo "
                                    Erro para atualizar NFE no pedido {$order['id']} em {$this->getInt_to()}\n
                                    URL={$respGetOrder['url']}\n
                                    HTTPCODE={$respGetOrder['httpcode']}\n
                                    RESPOSTA={$respGetOrder['response']}\n
                                    BODY={$respGetOrder['body']}\n";
                    $this->log_data('batch', $log_name, "Erro para atualizar NFE no pedido {$order['id']} em {$this->getInt_to()}\nURL={$respGetOrder['url']}\nHTTPCODE={$respGetOrder['httpcode']}\nRESPOSTA={$respGetOrder['response']}\nBODY={$respGetOrder['body']}", "E");
                    return false;
                } else {
                    echo "Nota fiscal do pedido {$order['id']} foi alterada. Estava com NFe: {$pack->invoiceNumber}, foi alterada para: {$nfe['nfe_num']}\n";
                    return true;
                }
            }
        }
        return null;
    }

    private function sendRefunded(string $int_to = null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        if (is_null($int_to)) {
            $orders = $this->model_orders->getOrdensByPaidStatus([111,81,7]);
        } else {
            $orders = $this->model_orders->getOrdensByOriginPaidStatus($int_to, [111,81,7]);
        }

        if (!count($orders)) {
            $this->log_data('batch',$log_name,"Nenhuma ordem a reembolsar $int_to");
            return ;
        }

        foreach ($orders as $order) {
            // define as chaves do marketplace
            if ($this->int_to != $order['origin']) {
                $this->setkeys($order['origin']);
            }

            if ($this->linkApi) {
                if($order['paid_status'] == 81){
                    echo "Pedido $order[id] para {$this->getInt_to()} é LinkApi e não tem devolução.\n";
                    $this->model_orders->updateByOrigin($order['id'], array('paid_status' => $this->model_orders->PAID_STATUS['refunded']));
                    continue;
                }
            }

            $items_canceled                   = 0;
            $value_refund                     = 0;
            $value_refund_complete                     = 0;
            $value_commission                  = 0;
            $arrItems                         = array();
            $description_legal_panel          = array();
            $data_product_return              = array();
            $itens                            = $this->model_product_return->getByOrderId($order['id']);
            $check_return_with_shipping_value = array();
            $order_values_to_refund           = array();

            foreach($itens as $item) {
                // Não foi devolvido.
                if ($item['status'] !== Model_product_return::REFUNDED && $item['status'] !== Model_product_return::REFUNDED_PARCIAL ) {
                    $items_canceled++;
                    continue;
                }
                $data_product_return = $item;

                // Consulta mkt_sku_id da VTEX para enviar na devolução.
                $prd_to_integration = $this->model_products->getByPrdIdIntToVariant($item['product_id'], $order['origin'], $item['variant']);
                if (empty($prd_to_integration)) {
                    echo "Não localizado o código mkt_sku_id para o produto $item[product_id] do pedido $order[id].\n";
                    continue 2;
                }
                $skumkt = (int)$prd_to_integration["mkt_sku_id"];
                $check_return_with_shipping_value[$skumkt] = array(
                    'id'                => $item['id'],
                    'returned_shipping' =>$item["returned_shipping"]
                );

                $description_legal_panel_str = "(Producto ($item[product_id]";
                if (!is_null($item['variant'])) {
                    $description_legal_panel_str .= " - Variação ($item[variant])";
                }
                $description_legal_panel_str .= ") ";

                $description_legal_panel[] = $description_legal_panel_str;

                // Cria o vetor com os itens.
                $arrItems[] = array(
                    "id"        => $skumkt,
                    "quantity"  => (int)$item['quantity_requested'],
                    "price"     => moneyFloatToVtex($item['return_total_value'] / ((int)$item['quantity_requested']))
                );

                $this->model_orders->insertOrderCancellationTuna([
                    "sku" => $skumkt,
                    "order_id" => $item['id'],
                ]);

                //Busca a comissão a ser aplicada no item para descontar do valor total
                $commission = $this->ordersmarketplace->checkComissionOrdersItem($order['id'], $item['product_id']);

                $value_refund_item = (float) ($item['return_total_value'] - ($item['return_total_value'] * ($commission['commission']/100))) + (($commission['total_channel'] * $item['quantity_requested']) - ( ($commission['total_channel'] * $item['quantity_requested']) * ($commission['commission'] / 100 ) )  );
                $value_refund_complete += (float) $item['return_total_value'];
                $value_commission += (float) ( ( $item['return_total_value'] * ($commission['commission']/100) )) + ( ( ($commission['total_channel'] * $item['quantity_requested']) *($commission['commission'] / 100)))- ($commission['total_channel'] * $item['quantity_requested']);
                $value_refund += $value_refund_item;

                $order_values_to_refund[$skumkt] = array(
                    'product_value' => $value_refund_item,
                    'shipping_value' => 0
                );
            }

            if (empty($data_product_return)) {
                echo "Não localizado informações de devolução para o pedido $order[id]. Items=".json_encode($itens)."\n";
                // Se todos os itens da devolução foram cancelados, completa a devolução.
                if ($items_canceled === count($itens)) {
                    if($order['paid_status'] == 81){
                         $this->model_orders->updateByOrigin($order['id'], array('paid_status' => $this->model_orders->PAID_STATUS['refunded']));
                    }
                }
                continue;
            }

            $url  = "{$this->getBaseUrlVtex()}/orders/{$order['numero_marketplace']}";
            $respGetOrder = $this->restVtex($url, '', array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'GET');

            if ($respGetOrder['httpcode'] != 200) {
                echo "Não encontrou o pedido $order[id], com o código no marketplace $order[numero_marketplace].\n";
                continue;
            }

            $responseGetOrder = json_decode($respGetOrder['content']);
            $value_refund_shipping = 0;

            foreach ($responseGetOrder->shippingData->logisticsInfo as $logisticsInfo) {
                $item           = $responseGetOrder->items[$logisticsInfo->itemIndex];
                $skumkt_id      = $item->id;
                $quantity       = $item->quantity;
                $shipping_price = moneyVtexToFloat($logisticsInfo->price);

                $dataItem = current(array_filter($arrItems, function($item) use ($skumkt_id) {
                    return $item['id'] == $skumkt_id;
                }));

                // Se devolveu todas as unidades, devolve o valor integral.
                if ($dataItem['quantity'] == $quantity ) {
                    $returned_shipping = $shipping_price;
                }
                // Se não devolveu todas as unidades, devolve o valor parcial.
                else {
                    $returned_shipping = ($shipping_price / $quantity) * $dataItem['quantity'];
                }

                if (array_key_exists($skumkt_id, $check_return_with_shipping_value)) {
                    $order_values_to_refund[$skumkt_id]['shipping_value'] = $returned_shipping;

                    $value_refund_shipping += $returned_shipping;
                    if (is_null($check_return_with_shipping_value[$skumkt_id]['returned_shipping'])) {
                        if($order['paid_status'] == 81){
                             $this->model_product_return->updateById($check_return_with_shipping_value[$skumkt_id]['id'], array('returned_shipping' => $returned_shipping));
                        }
                    }
                }
            }
            $value_refund_shipping = roundDecimal($value_refund_shipping); 
            
            // Nova conta de frete para calcular o % de comissão de frete e diminuir do valor total
            $value_refund_shipping_fiscal = roundDecimal(($value_refund_shipping * ( $order['service_charge_freight_value']/100 ) ) );
            $shippingCommission = $value_refund_shipping * ( $order['service_charge_freight_value']/100 );
            $value_refund_shipping = roundDecimal($value_refund_shipping - $shippingCommission );


            // Adiciona valor do frete.
            $value_refund += $value_refund_shipping;
            $value_refund_complete += $value_refund_shipping+$shippingCommission;
            $value_commission = $value_commission * -1;


            $invoice = array(
                "type"              => "Input",
                "invoiceNumber"     => (int)$data_product_return["devolution_invoice_number"],
                //"invoiceKey"        => "",
                'invoiceUrl'        => 'https://www.nfe.fazenda.gov.br/portal/consultaRecaptcha.aspx',
                "courier"           => $data_product_return["logistic_operator_name"],
                "trackingNumber"    => $data_product_return["reverse_logistic_code"],
                //"trackingUrl"       => "",
                "items"             => $arrItems,
                "issuanceDate"      => $data_product_return["return_nfe_emission_date"],
                "invoiceValue"      => moneyFloatToVtex($value_refund_complete)
            );

            $json_data = json_encode($invoice);

            $url    = "{$this->getBaseUrlVtex()}/orders/$order[numero_marketplace]/invoice";
            $resp   = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'POST');

            if ($resp['httpcode'] != 200) {
                $message = "Erro na devolução de={$this->getInt_to()}\nendpoinbt=$url\nhttpcode=$resp[httpcode]\nresponse=$resp[content]";
                echo $message . "\n";
                $this->log_data('batch', $log_name, $message, "E");
                continue;
            }

            try {
                // Criar débito no extrato.
                $this->model_legal_panel_fiscal->createDebit(
                    $order['id'],
                    "Devolução de produto.",
                    'Chamado Aberto',
                    "Devolução de produto. ".implode(', ', $description_legal_panel),
                    $order['freight_seller'] ? $value_commission : ($value_commission + $value_refund_shipping_fiscal),
                    'Rotina API'
                );

                $this->marketplace_order->updateToRefunded($order['id'], $order['freight_seller'], $value_refund, $value_refund_shipping, implode(', ', $description_legal_panel), $order_values_to_refund);
                echo "Devolução em {$this->getInt_to()} do pedido $order[id] concluída\n";
            } catch (Exception $exception) {
                echo "Erro para devolver o pedido {$order['id']}. {$exception->getMessage()}\n";
                $this->log_data('batch', $log_name, "Erro para pagar o pedido {$order['id']}.\n{$exception->getMessage()}", 'E');
            }

            //envia o cancelamento para a Tuna caso o Gateway esteja configurado para ele -- payment_gateway_id
            $gatewayID = $this->model_settings->getSettingDatabyName('payment_gateway_id');
            if($gatewayID['value'] == 8){
                $valorEstorno = $value_refund_complete;
                $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

                $ordersCancellation = $this->model_orders->getOrderCancellationTuna($order['id']);
                foreach ($ordersCancellation as $item) {
                    if ($item['status'] == 0)
                        $orderArray = $this->model_orders->getOrdersData(0, $item['order_id']);

                        echo "Manda Cancelados {$order['id']}\n";
                        $this->log_data('batch',$log_name, "Pedido Array - \nRESPOSTA=".json_encode($orderArray),"I");

                        $this->integration->geracancelamentotuna($orderArray, $valorEstorno);
                        $this->model_orders->updateOrderCancellationTuna($order['id']);
                }

            }

            $url    = "{$this->getBaseUrlVtex()}/orders/$order[numero_marketplace]/invoice/$invoice[invoiceNumber]/tracking";
            $resp   = $this->restVtex($url, json_encode(array("isDelivered" => true)), array('x-vtex-api-appkey: '.$this->getApikey(),'x-vtex-api-apptoken: '.$this->getAppToken()), 'PUT');

            if ($resp['httpcode'] != 200) {
                $message = "Erro na confirmação da entrega da devolução de={$this->getInt_to()}\nendpoinbt=$url\nhttpcode=$resp[httpcode]\nresponse=$resp[content]";
                echo $message . "\n";
                $this->log_data('batch', $log_name, $message, "E");
            }

        }
    }
}