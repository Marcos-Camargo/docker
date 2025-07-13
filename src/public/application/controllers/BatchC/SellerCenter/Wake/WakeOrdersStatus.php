<?php

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
 *
 * @property OrdersMarketplace $ordersmarketplace
 * @property CalculoFrete $calculofrete
 */

class WakeOrdersStatus extends BatchBackground_Controller {
    var $int_to='';
    var $apikey='';
    var $site='';
    var $appToken='';
    var $accountName='';
    var $environment='';
    var $dns='.com.br';
	var $linkApiSite;
	var $linkApi = false;
    var $baseurl = '';
    var $api_url = '';

    public $result;
    public $responseCode;
    protected $header;
	

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
        $this->load->library('ordersMarketplace');
        $this->load->library('calculoFrete');
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

    private function setApiUrl($baseurl) {
        $this->baseurl = $baseurl;
    }
    private function getApiUrl() {
        return $this->baseurl;
    }

    // php index.php BatchC/SellerCenter/Wake/WakeOrdersStatus run null int_to
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

        /* faz o que o job precisa fazer */
        if (!is_null($params) && ($params != 'null')) {
        	$integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$params,"CONECTALA","DIRECT",0);
            $api_keys = json_decode($integration['auth_data']);
            $this->setApikey($api_keys ?? null);
            $this->setApiUrl($api_keys->api_url);
			if (!$integration) {
				echo " Marketplace ".$params." não encontrado!";
				die;
			}
        }
		else {
			$params = null; 
		}
		echo $params."\n"; 
      
        $this->mandaNfe($params);
        $this->mandaTracking($params);
        $this->mandaOcorrencia($params);
        $this->mandaEntregue($params);
        $this->mandaCancelados($params);
        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();

    }

    private function mandaNfe($int_to = null)
    {
        echo "Iniciando o mandaNfe.. \n";

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

            echo "Nenhuma order encontrada no mandaNfe... \n\n";

            echo "Encerrando o mandaNfe... \n\n";

            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            echo 'ordem n°= '. $order['id'] . "\n\n";

            $orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

            $nfes = $this->model_orders->getOrdersNfes($order['id']);
            if (count($nfes) == 0) {
                echo 'ERRO: pedido ' .$order['id']. ' não tem nota fiscal'."\n\n";
                $this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
                // ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50
                continue;
            }
            $nfe = $nfes[0];

           
            $nfe['nfe_num'] = (int)$nfe['nfe_num'];
            $nfe['chave'] = $nfe['chave'];

            $getStoreid = $this->model_integrations->getIntegrationbyStoreIdAndInto($order['store_id'],$int_to);
            
            if ($getStoreid) {
                $auth_dataSeller = json_decode($getStoreid['auth_data'], true);

                // Verifica se a decodificação JSON foi bem-sucedida e se 'token' existe
                if (is_array($auth_dataSeller) && isset($auth_dataSeller['token'])) {
                    $this->setAppToken($auth_dataSeller['token']);
                } else {
                    $error = "Não foi encontrado um token para a loja " . $order['store_id'] . " pulando para a próxima order \n\n";
                    echo $error;
                    $this->log_data('batch', $log_name, $error, "E");
                    continue;
                }
            } else {
                $error = "Erro ao obter dados de integração para a loja " . $order['store_id'] . " pulando para a próxima order \n\n";
                echo $error;
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }

            unset($responseBody);

            //usar o token do seller que esta em $auth_dataSeller para dar um get https://api.fbits.net/resellers/token e trazer o centroDistribuicaoId que estara no $sendNfe
            $endpoint = "resellers/token";

            $resp =  $this->processNew(new stdClass(),$endpoint,'GET');

            $responseBody = json_decode($this->result, true);

            if ($this->responseCode != 200) {
                    // Verifica se o código HTTP é 422 ou 500
                if ($this->responseCode == 422 || $this->responseCode == 500) {
                    $error = "Erro para pegar centroDistribuicaoId reseller " . $this->getInt_to() . ". httpcode=" . $this->responseCode . " RESPOSTA CAR: " . print_r($responseBody ?? $this->result) . " http:" . $endpoint  . "\n\n";
                } else {
                    $error = "Erro para pegar centroDistribuicaoId reseller diferente de 422 ou 500 " . $this->getInt_to() . ". httpcode=" . $this->responseCode . 
                            " RESPOSTA CAR: " . print_r($this->result, true) . " http:" . $endpoint  . "\n\n";
                }

                echo $error . "\n";
                
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }   
            
            if ($this->responseCode == 200) {

                if(isset($responseBody['mensagem'])){
                    $error = $responseBody['mensagem']  . $this->getInt_to() . ". httpcode=" . $this->responseCode . " RESPOSTA CAR: " . print_r($responseBody ?? $this->result, true) . " http:" . $endpoint .  "\n";
                    
                    echo $error . "\n";
                    $this->log_data('batch', $log_name, $error, "E");
                    continue;
                }
            }

            $sendNfe = Array (
                'situacaoPedidoId'        => 11,
                'centroDistribuicaoId'    => $responseBody['centroDistribuicaoId'],
                'dataEvento'              => date('d/m/Y H:i:s'),
                "numeroNotaFiscal"        => $nfe['nfe_num'],
                "chaveAcessoNFE"          => $nfe['chave']
            );

            unset($json_data);
            unset($resp);
            unset($responseBody);
            $json_data = json_encode($sendNfe);
			
            $endpoint = 'pedidos/'.$order['numero_marketplace'].'/rastreamento';
    
            $tokenObject = $this->getApikey();
               
            $resp =  $this->processNew($tokenObject,$endpoint,'POST',$json_data);

            $responseBody = json_decode($this->result, true);

            if ($this->responseCode != 200) {
                    // Verifica se o código HTTP é 422 ou 500
                if ($this->responseCode == 422 || $this->responseCode == 500) {
                    $error = "Erro para atualizar NFE " . $this->getInt_to() . ". httpcode=" . $this->responseCode . " RESPOSTA CAR: " . print_r($responseBody ?? $this->result) . " http:" . $endpoint . " Dados enviados=" . print_r($json_data, true) . "\n\n";
                } else {
                    $error = "Erro para atualizar diferente de 422 ou 500 NFE " . $this->getInt_to() . ". httpcode=" . $this->responseCode . 
                            " RESPOSTA CAR: " . print_r($this->result, true) . " http:" . $endpoint . " Dados enviados=" . print_r($json_data, true) . "\n\n";
                }

                echo $error . "\n";
                
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }   
            
            if ($this->responseCode == 200) {

                if(isset($responseBody['mensagem'])){
                    $error = "Erro wake " . $responseBody['mensagem']  . $this->getInt_to() . ". httpcode=" . $this->responseCode . " RESPOSTA CAR: " . print_r($responseBody ?? $this->result, true) . " http:" . $endpoint .  "\n";
                    
                    echo $error . "\n";
                    $this->log_data('batch', $log_name, $error, "E");
                    continue;
                }
            }

            $this->log_data('batch', $log_name, "Enviou NFe \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$this->result}\nRETORNO=" . json_encode($this->result), "I");

            $order['paid_status'] = $order['is_pickup_in_point'] ? 58 : 50; // agora tudo certo para contratar frete
            $order['envia_nf_mkt'] = date('Y-m-d H:i:s');

            $this->model_orders->updateByOrigin($order['id'],$order);
            echo "NFE do pedido {$order['id']} enviado para {$this->getInt_to()}\n";
        }

        echo "Encerrando o mandaNfe... \n\n";

    }
	
    private function mandaTracking($int_to = null)
    {
       
        echo "Iniciando o mandaTracking... \n";


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

            echo "Nenhuma order encontrada no mandaTracking... \n\n";

            echo "Encerrando o mandaTracking... \n\n";

            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de tracking',"I");
            return ;
        }     
      
        foreach ($ordens_andamento as $order) {
            echo 'ordem n°= '. $order['id']. "\n\n";

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

            $getStoreid = $this->model_integrations->getIntegrationsbyStoreId($order['store_id']);
                    
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


            $nfe['nfe_num'] = (int)$nfe['nfe_num'];
            $nfe['chave'] = $nfe['chave'];

            
            unset($resp);
            unset($responseBody);
            // Antes de atualizar o tracking precisamos do pedidoRastreamentoId
            $endpoint = 'pedidos/'.$order['numero_marketplace'].'/rastreamento';

            $tokenObject = $this->getApikey();
            $resp =  $this->processNew($tokenObject,$endpoint,'GET');
            $responseBody = json_decode($this->result, true);


            if ($this->responseCode  != 200) {
                // Verifica se o código HTTP é 422 ou 500
                if ($this->responseCode == 422 || $this->responseCode == 500) {
                    $error = "Erro para pegar pedidoRastreamentoId na resposta do " . $this->getInt_to() . ". httpcode=" . $this->responseCode . " RESPOSTA CAR: " . print_r($responseBody ?? $this->result, true) . " http:" . $endpoint .  "\n";
                } else {
                    $error = "Erro diferente de 422 ou 500 para pegar pedidoRastreamentoId na resposta do " . $this->getInt_to() . ". httpcode=" . $this->responseCode . " RESPOSTA CAR: " . print_r($this->result, true) . " http:" . $endpoint .  "\n";
                }
            
                echo $error . "\n";
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }

            if ($this->responseCode == 200) {

                if($responseBody['pedidoRastreamentoId'] == 0 || empty($responseBody['pedidoRastreamentoId'])){
                    $error = "pedidoRastreamentoId é 0 ou em branco na resposta do " . $this->getInt_to() . ". httpcode=" . $this->responseCode . " RESPOSTA CAR: " . print_r($responseBody ?? $this->result, true) . " http:" . $endpoint .  "\n";
                    
                    echo $error . "\n";
                    $this->log_data('batch', $log_name, $error, "E");
                    continue;
                }
            }

            $pedidoRastreamentoId = $responseBody['pedidoRastreamentoId'];

            // Endpoint para inserir o rastreamento na WAKe
            $endpoint = 'pedidos/'.$order['numero_marketplace'].'/rastreamento'.'/'.$pedidoRastreamentoId.'/parcial';
         
            $tracking = Array (
                'rastreamento'        => $frete['codigo_rastreio'],
                'urlRastreamento'    =>  $carrier_url
            );

            unset($json_data);
            unset($resp);
            unset($responseBody);

            $json_data = json_encode($tracking);

            $resp = $this->processNew($tokenObject,$endpoint,'PUT',$json_data);

            if ($this->responseCode != 200) {
                // Verifica se o código HTTP é 422 ou 500
                 if ($this->responseCode == 422 || $this->responseCode == 500) {
                    $responseBody = json_decode($this->result , true);
                    $error = 'ERRO na gravação da tracking pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                } else {
                    $error = 'ERRO na gravação da tracking pedido diferente de 422 e 500 ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                }
            
                echo $error . "\n";
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }

            //Como deu certo a chamada anterior, agora atualizamos o status na wake para enviado == 9
            $endpoint = 'pedidos/'.$order['numero_marketplace'].'/status';

           
            $status = Array (
                'id'        => 16 // 9
            );

            unset($json_data);
            unset($resp);
            unset($responseBody);

            $json_data = json_encode($status);

            $resp =  $this->processNew($tokenObject,$endpoint,'PUT',$json_data);
         
            $responseBody = json_decode($this->result , true);

            if ($this->responseCode != 200) {
                // Verifica se o código HTTP é 422 ou 500
                 if ($this->responseCode == 422 || $this->responseCode == 500) {
                    $responseBody = json_decode($this->result , true);
                    $error = 'ERRO na gravação do status dentro da WAKE, pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                } else {
                    $error = 'ERRO na gravação do status pedido diferente de 422 e 500 ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                }
            
                echo $error . "\n";
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }

            $this->log_data('batch',$log_name,"Enviou Tracking e atualizou status na Wake \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$json_data}\nRETORNO=".json_encode($this->result),"I");

            $order['paid_status'] = 53; // fluxo novo, manda para a rastreio

            $this->model_orders->updateByOrigin($order['id'],$order);
            echo 'Tracking enviado para '.$this->getInt_to()."\n";
        }

        echo "Encerrando o mandaTracking... \n\n";

    }

    private function mandaOcorrencia($int_to = null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        echo "Iniciando o mandaOcorrencia... \n";

            // status 9 pra wake e 
            // o paid_status interno é 5

        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        //leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para entregue no FreteRastrear
        $paid_status = '55';

        if (is_null($int_to)) {
			$ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
		}
		else {
			$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		}
        
        if (count($ordens_andamento)==0) {

            echo "Nenhuma order encontrada no mandaOcorrencia... \n\n";

            echo "Encerrando o mandaOcorrencia... \n\n";

            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status no mandaOcorrencia da '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            echo 'ordem ='.$order['id']."\n\n";

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
           
            $nfe['nfe_num'] = (int)$nfe['nfe_num'];

            $frete = $this->model_freights->getFreightsDataByOrderId($order['id']);
            if (count($frete)==0) {
                echo "Sem frete/rastreio \n";
                // Não tem frete, não deveria aconter
                $this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E" );
                continue;
            }

            $frete = $frete[0];

            if(empty($order['data_envio'])){
                echo "Pedido de sem Data de Envio, seguindo para o proximo item \n";
			    continue;
            }

            //Como deu certo a chamada anterior, agora atualizamos o status na wake para enviado == 9
            $endpoint = 'pedidos/'.$order['numero_marketplace'].'/status';

            $status = Array (
                'id'   => 9
            );

            unset($json_data);
            unset($resp);
            unset($responseBody);

            $tokenObject = $this->getApikey();

            $json_data = json_encode($status);
            $resp =  $this->processNew($tokenObject,$endpoint,'PUT',$json_data);
         
            $responseBody = json_decode($this->result, true);

            if ($this->responseCode != 200) {
                // Verifica se o código HTTP é 422 ou 500
                 if ($this->responseCode == 422 || $this->responseCode == 500) {
                    $responseBody = json_decode($this->result, true);
                    $error = 'ERRO na gravação do status Ocorrencia dentro da WAKE, pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                } else {
                    $error = 'ERRO na gravação do status Ocorrencia diferente de 422 e 500 ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                }
            
                echo $error . "\n";
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }

            $this->log_data('batch',$log_name,"Ocorrencia para o pedido \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$json_data}\nRETORNO=".json_encode($this->result),"I");

            // Avisado que foi entregue na transportadora
            $order['paid_status'] = 5; // O pedido está entregue
            $this->model_orders->updateByOrigin($order['id'],$order);

            echo 'Aviso de Ocorrencia enviada para '.$this->getInt_to()."\n\n";
        }

        echo "Encerrando o mandaOcorrencia... \n\n";

    }

    private function mandaEntregue($int_to = null)
    {

        echo "Iniciando o mandaEntregue... \n";


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

            echo "Nenhuma order encontrada no mandaEntregue... \n\n";

            echo "Encerrando o mandaEntregue... \n\n";

            $this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Entregue da '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {
            echo 'ordem ='.$order['id']."\n\n";


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

            $getStoreid = $this->model_integrations->getIntegrationsbyStoreId($order['store_id']);

            $ocorrencias = $this->model_frete_ocorrencias->getOcorrenciaByOrderId($order['id'], true);

            if (!count($ocorrencias))
                array_push($ocorrencias, ['id' => 0,'nome' => 'Objeto entregue ao destinatário', 'data_ocorrencia' => date('Y-m-d')]);

            $ocorrencias = end($ocorrencias);

            $stores = $this->model_stores->getStoresData($order['store_id']);
            $logistic = $this->calculofrete->getLogisticStore(array(
                'freight_seller'        => $stores['freight_seller'],
                'freight_seller_type'   => $stores['freight_seller_type'],
                'store_id'              => $stores['id'] // $stores['codigo']
            ));

    
            $invoiced = Array (
                "isDelivered" => true,
                "events" => array(
                    array(
                        "city"          => $stores['addr_city'],
                        "state"         => $stores['addr_uf'],
                        "description"   => $ocorrencias['nome'],
                        "date"          => $ocorrencias['data_ocorrencia']
                    )
                )
            );

            $nfe['nfe_num'] = (int)$nfe['nfe_num'];

            $frete = $this->model_freights->getFreightsDataByOrderId($order['id']);
            if (count($frete)==0) {
                echo "Sem frete/rastreio \n";
                // Não tem frete, não deveria aconter
                $this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E" );
                continue;
            }
            $frete = $frete[0];

            //atualiza data de entrega do pedido
            unset($json_data);
            unset($resp);
            unset($responseBody);

            $dataEntrega = Array (
                'rastreamento'        => $frete['codigo_rastreio'],
                'dataEntrega'         => $frete['date_delivered']
            );

            $endpoint = 'pedidos/'.$order['numero_marketplace'].'/rastreamento';

            $tokenObject = $this->getApikey();

            $json_data = json_encode($dataEntrega);
            $resp =  $this->processNew($tokenObject,$endpoint,'PUT',$json_data);

            //Como deu certo a chamada anterior, agora atualizamos o status na wake para enviado == 9
            $endpoint = 'pedidos/'.$order['numero_marketplace'].'/status';

            $status = Array (
                'id'        => 18
            );

            unset($json_data);
            unset($resp);
            unset($responseBody);

            
            $tokenObject = $this->getApikey();

            $json_data = json_encode($status);
            $resp =  $this->processNew($tokenObject,$endpoint,'PUT',$json_data);
         
            $responseBody = json_decode($this->result, true);

            if ($this->responseCode != 200) {
                // Verifica se o código HTTP é 422 ou 500
                 if ($this->responseCode == 422 || $this->responseCode == 500) {
                    $responseBody = json_decode($this->result, true);
                    $error = 'ERRO na gravação do status Entregue dentro da WAKE, pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                } else {
                    $error = 'ERRO na gravação do status Entregue diferente de 422 e 500 ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                }
            
                echo $error . "\n";
                $this->log_data('batch', $log_name, $error, "E");
                continue;
            }

            $this->log_data('batch',$log_name,"Entregou o pedido \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$json_data}\nRETORNO=".json_encode($this->result),"I");

            // Avisado que foi entregue na transportadora
            $order['paid_status'] = 6; // O pedido está entregue
            $this->model_orders->updateByOrigin($order['id'],$order);

            echo 'Aviso de Entregue enviado para '.$this->getInt_to()."\n\n";
        }

        echo "Encerrando o mandaEntregue... \n\n";
    }

    private function mandaCancelados($int_to = null)
    {

        echo "Iniciando o mandaCancelados... \n";

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

            echo "Nenhuma order encontrada no mandaCancelados... \n\n";

            echo "Encerrando o mandaCancelados... \n\n";

            $this->log_data('batch',$log_name,'Nenhuma ordem a cancelar '.$int_to,"I");
            return ;
        }

        foreach ($ordens_andamento as $order) {

            $timeCancel = null;
            $settingTimeCancel = $this->model_settings->getSettingDatabyName('time_not_return_stock_cancel_order');
            if ($settingTimeCancel && $settingTimeCancel['status'] == 1)
                $timeCancel = (int)$settingTimeCancel['value'];

            echo 'Cancelando pedido n° = ' . $order['id']. "\n\n";

            $orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item \n";
			    continue;
			}

            $getStoreid = $this->model_integrations->getIntegrationsbyStoreId($order['store_id']);

         
             //Como deu certo a chamada anterior, agora atualizamos o status na wake para enviado == 9
             $endpoint = 'pedidos/'.$order['numero_marketplace'].'/status';

           
            $status = Array (
                'id' => 8
            );
 
             unset($json_data);
             unset($resp);
             unset($responseBody);
             
             $tokenObject = $this->getApikey();
 
             $json_data = json_encode($status);
             $resp =  $this->processNew($tokenObject,$endpoint,'PUT',$json_data);
          
             $responseBody = json_decode($this->result, true);
 
            if ($this->responseCode != 200) {
                 // Verifica se o código HTTP é 422 ou 500
                  if ($this->responseCode == 422 || $this->responseCode == 500) {
                     $responseBody = json_decode($this->result, true);
                     $error = 'ERRO na gravação do status Cancelado dentro da WAKE, pedido ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                 } else {
                     $error = 'ERRO na gravação do status Cancelado diferente de 422 e 500 ' . $order['id'] . ' ' . $order['numero_marketplace'] . ' no ' . $this->getInt_to() . ' http:' . $endpoint . ' - httpcode: ' . $this->responseCode . ' RESPOSTA ' . $this->getInt_to() . ': ' . print_r($responseBody ?? $this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . "\n";
                 }
             
                 echo $error . "\n";
                 $this->log_data('batch', $log_name, $error, "E");
                 continue;
            }
 
            // Altero o status e estorno o estoque pois não é feito requisição para essa função ser feito por api
            $this->ordersmarketplace->cancelOrder($order['id'], true);

            $this->log_data('batch',$log_name,"Cancelado o pedido \nPEDIDO={$order['id']}\nMKT={$this->getInt_to()}\nENVIADO={$json_data}\nRETORNO=".json_encode($this->result),"I");


            echo "Pedido {$order['id']} cancelado em {$this->getInt_to()}\n\n";
        }

            echo "Encerrando o mandaCancelados... \n\n";
    }

    private function processNew($tokenObject, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
        	
		if (property_exists($tokenObject, 'token')) {
	        $this->api_url = $tokenObject->api_url;

	
            $this->header = [
                'content-type: application/json',
                'accept:application/json',
                'Authorization:Basic '.$tokenObject->token,
            ];
	
	        $url = 'https://'.$this->api_url.'/'.$endPoint;
		}else{
            //apenas utilizado para a chamada resellers/token onde não usamos o token principal
            $url = 'https://'.$this->getApiUrl().'/'.$endPoint;

            $this->header = [
                'content-type: application/json',
                'accept:application/json',
                'Authorization:Basic '.$this->getAppToken(),
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		// curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $err        = curl_errno($ch);
	    $errmsg     = curl_error($ch);
        
		curl_close($ch);

		if ($err) {
			echo "Houve Erro no curl: ". $errmsg."\n";
		}
		

		if ($this->responseCode == 429) {
		    echo "Wake OrderStatusMuitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($tokenObject, $endPoint, $method, $data);
		}
		if ($this->responseCode == 504) {
		    echo "Wake OrderStatus Deu Timeout httpcode=504. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($tokenObject, $endPoint, $method, $data);
		}
        if ($this->responseCode == 503) {
		    echo "Wake OrderStatus com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($tokenObject, $endPoint, $method, $data);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}

        return;
    }


}