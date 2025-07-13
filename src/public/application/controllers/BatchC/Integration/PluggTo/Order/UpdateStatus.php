<?php

/**
 * Class UpdateStatus
 *
 * php index.php BatchC/PluggTo/Order/UpdateStatus run
 *
 */

require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";

class UpdateStatus extends Main
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setJob('UpdateStatus');
        $this->load->model('model_orders');
        $this->load->model('model_freights');
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando pedidos para atualizar... \n";

        // Define a loja, para recuperar os dados para integração
        
        $this->setDataIntegration($store);

        $this->setStore($store);

        try {
            // Recupera os pedidos
            $this->updateOrders();
        } catch (Throwable $e) {
            $this->log_integration('Erro ao processar pedidos', $e->getMessage(), "E");
        }
        
        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Atualizaçao dos pedidos já integrados
     */
    private function updateOrders()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }
       
        $orders = $this->getOrdersForUpdate();        
        $access_token = $this->getToken();   

        foreach ($orders as $orderIntegration) {        
            $bolProblema = false;
            $idIntegration  = $orderIntegration['id'];
            $orderId        = $orderIntegration['order_id'];
            $paidStatus     = $orderIntegration['paid_status'];

            $companyId      = $orderIntegration['company_id'];
            $this->setCompany($companyId); 

            $removeList     = true;
            $this->setUniqueId($orderId); // define novo unique_id
           
            // Código do pedido integrado na pluggto
            $idPluggto = $this->getOrderIdPluggto($orderId);

            echo "ORDER ID: {$orderId} - QUEUE STATUS: {$paidStatus} / PLUGGTO ID: {$idPluggto}\n";
            // Não encontrou o código do pedido integrado
            if (!$idPluggto) {              
                $msgError = "Não foi possível localizar o código do pedido integrador. PEDIDO_CONECTA={$orderId}, para atualizar! ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";                
                //$this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar o pedido {$orderId}</h4> <ul><li>Pedido ainda não integrado com a PluggTo.</li></ul>", "E");
                $bolProblema = true;
                continue;
            }
            // Pedido ainda não foi integrado
            $integratedOrder = $this->getIntegratedOrder($orderId);
            if (!$integratedOrder) {
                $msgError = "Pedido ainda não integrado. PEDIDO_CONECTA={$orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                $bolProblema = true;              
                continue;
            }
            // Pedido para cancelar
            $orderCancel = $this->getOrderCancel($orderId);
            if ($orderCancel) {
                //cancelar na pluggto
                $msgError = "Pedido deve ser cancelado. PEDIDO_CONECTA={$orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "I");                
                $paidStatus = OrderStatusConst::CANCELED_AFTER_PAYMENT;
            }
            $status = $this->getStatusIntegration($paidStatus);

            echo "STATUS INTEGRATION:\n";
            print_r($status);
            echo "\n";
            // Ignorar status, não deve ser alterado na pluggto
            if ($status['status'] === null) {
                $msgError = "Chegou status={$paidStatus}, não deve ser integrado, apenas remover da fila! ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_integration("Não é possível atualizar o status do pedido {$orderId}", "<h4>Não foi possível atualizar o pedido {$orderId}</h4><ul><li>Status do pedido {$paidStatus} não elegível para atualização com PluggTo.</li></ul>", "I");
                $this->removeOrderIntegration($idIntegration);
                $this->log_data('batch', $log_name, "Remove da fila status nulo, Pedido: {$orderId}, paidStatus = {$paidStatus}, status = {$status['status']}", "I");
                $bolProblema = true;
                continue;
            }
            // Aguardar pedido ser faturado na Status = 3 pronto_para_picking
            if ($status['status'] == OrderStatusConst::WAITING_INVOICE) {
                echo "AGUARDANDO NOTA FISCAL!\n";
                               // Pedido já tem uma NF-e, atualizar o status
                $orderWithNfe = $this->getOrderWithNfe($orderId);
                if ($orderWithNfe) {
                    $msgError = "Pedido já tem uma NF-e. Será atualizado apenas seu status para 52. PEDIDO_CONECTA={$orderId} para atualizar! ORDER_INTEGRATION=".json_encode($orderIntegration);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    //$this->log_integration("Não é possível atualizar o status do pedido {$orderId}", "<h4>O pedido {$orderId} já tem uma NF-e</h4><ul><li>O status do pedido será atualizado para Pedido faturado (Enviar NF-e para o marketplace) [52].</li></ul>", "E");
					$this->updateStatusForOrder($orderId, 52, 3);
                    $this->removeOrderIntegration($idIntegration);
                    $bolProblema = true;
                    continue;
                }
                
                // Obter dados do pedido
                $url          = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                $data         = "";                
                $dataOrder    = json_decode(json_encode($this->sendREST($url, $data)));
               
                if ($dataOrder->httpcode != 200) {
                    $msgError = "Não foi possível localizar o pedido {$orderId}, idPluggto: {$idPluggto}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não localizamos os dados do pedido {$orderId} na PluggTo</h4>", "E");
                    $bolProblema = true;
                    continue;
                }
                
                $registro = json_decode($dataOrder->content);
                $registro = $registro->Order;                
                $historico = $registro->status;
               
                // Recupera código da nfe
                $nfe_number = $registro->shipments[0]->nfe_number ?? null;

                // Se for null, não existe nfe ainda
                if (!isset($nfe_number) || $idIntegration == 0 || $nfe_number == "") {
                    echo "Pedido ainda não faturado\n";
                    $bolProblema = true;
                    /*$this->log_integration(
                        "Erro para atualizar o pedido {$orderId}",
                        "<h4>Pedido {$orderId} ainda não foi faturado, sem dados de NF-e na PluggTo</h4>",
                        "E"
                    );*/
                    continue;
                }

                foreach($registro->shipments as $shipment){

                    $nfe_number = $shipment->nfe_number ?? null;

                    // Dados da NF-e
                    $nfe_key        = $shipment->nfe_key ?? null;
                    $nfe_link       = $shipment->nfe_link ?? null;                
                    $nfe_serie      = $shipment->nfe_serie ?? null;
                    $nfe_date       = $shipment->nfe_date ?? null;
                    $nfe_valorNota  = number_format($registro->total, 2, ',', '.') ?? null;

                    if(
                        empty($nfe_number) ||
                        empty($nfe_key) ||
                        empty($nfe_link) ||
                        empty($nfe_serie) ||
                        empty($nfe_date) ||
                        empty($nfe_valorNota)
                    ) {
                        $this->log_integration(
                            "Erro para atualizar o pedido {$orderId}",
                            "<h4>Dados da NF-e do pedido {$orderId} estão incompletos.</h4>
                            <ul>
                                <li><strong>Chave:</strong> {$nfe_key}</li>
                                <li><strong>Número:</strong> {$nfe_number}</li>
                                <li><strong>Série:</strong> {$nfe_serie}</li>
                                <li><strong>Data de Emissão:</strong> {$nfe_date}</li>
                                <li><strong>URL NFe:</strong> {$nfe_link}</li>
                                <li><strong>Valor:</strong> {$nfe_valorNota}</li>
                            </ul>",
                            "E"
                        );
                        $bolProblema = true;
                        continue 2;
                    }
                            
                    // Dados para inserir a NF-e
                    $arrNfe = array(                        
                        'order_id'          => $orderId,
                        'company_id'        => $this->company,
                        'store_id'          => $this->store,
                        'date_emission'     => date('d/m/Y H:i:s', strtotime($nfe_date)),
                        'nfe_value'         => $nfe_valorNota,
                        'nfe_serie'         => $nfe_serie,
                        'nfe_num'           => $nfe_number,
                        'chave'             => $nfe_key,
                        'id_nf_marketplace' => $idPluggto,
                    );           
                    
                    // Inserir NF-e
                    $insertNfe = $this->createNfe($arrNfe);

                    // Erro para inserir a NF-e
                    if (!$insertNfe) {
                        $msgError = "Não foi possível inserir dados de faturamento do pedido {$orderId}! DATA_NFE_INSERT=" . json_encode($arrNfe) . ", DATA_NFE_pluggto=" . json_encode($dataNfe) . " RETORNO=" . json_encode($insertNfe);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration(
                            "Erro para atualizar o pedido {$orderId}",
                            "<h4>Não foi possível atualizar dados de faturamento do pedido {$orderId}</h4>",
                            "E"
                        );
                        $bolProblema = true;
                        continue;
                    }
                }

                // Remover pedido da fila
                $this->removeOrderIntegration($idIntegration);                

                // volta pro formato de retorno JSON
                $this->formatReturn = 'json';

                $nfeDataEmissao = date('d/m/Y', strtotime($nfe_date));
                $this->log_integration("Pedido {$orderId} atualizado",
                    "<h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> 
                    <ul>
                        <li><strong>Chave:</strong> {$nfe_key}</li>
                        <li><strong>Número:</strong> {$nfe_number}</li>
                        <li><strong>Série:</strong> {$nfe_serie}</li>
                        <li><strong>Data de Emissão:</strong> {$nfeDataEmissao}</li>
                        <li><strong>Valor:</strong> {$nfe_valorNota}</li>
                    </ul>", "S");

                $msgOk = "Pedido {$orderId} atualizado <h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> ";
                $msgOk .= "<ul> ";
                $msgOk .= "<li><strong>Chave:</strong> {$nfe_key}</li> ";
                $msgOk .= "<li><strong>Número:</strong> {$nfe_number}</li> ";
                $msgOk .= "<li><strong>Série:</strong> {$nfe_serie}</li> ";
                $msgOk .= "<li><strong>Data de Emissão:</strong> {$nfeDataEmissao}</li> ";
                $msgOk .= "<li><strong>Valor:</strong> {$nfe_valorNota}</li> ";
                $msgOk .= "</ul> ";
                $this->log_data('batch', $log_name, $msgOk, "I");

                $this->model_orders->updatePaidStatus($orderId, OrderStatusConst::INVOICED_SEND_INVOICE_MKTPLACE);
                
                $status = $this->getStatusIntegration(OrderStatusConst::INVOICED_SEND_INVOICE_MKTPLACE);
                echo "Pedido {$orderId}, NfeOrder: {$nfe_number} atualizado com sucesso!\n";
                $bolProblema = false;                
                continue;
            }


            $statusRet     = $status["status"];
            $historicoRet  = $status["historico"];
            $loja = $this->model_stores->getStoresData($this->store);
            $logisticaProria = $this->getStoreOwnLogisticERP();
                        
            if($paidStatus == OrderStatusConst::DELIVERED) {
                echo "ENTREGUE!\n";
                //(Pedido entregue ao cliente)
                // Data entrega do pedido integrado no conectala
                if($logisticaProria == false){
                    $dataEntrega = $this->getDataEntregue($orderId);                

                    if(!empty($dataEntrega)){
                        $url          = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                        $updateOrder  = array();
                        $updateOrder['status']  = "delivered";                    
                        $updateOrder['shipments'][0]['date_delivered'] = $dataEntrega ?? "";
                        $updateOrder['shipments'][0]['status']  = "delivered";
                        
                        //verifica se existe o id do array shipment                
                        $url  = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                        $data = "";
                        $dataStatus   = json_decode(json_encode($this->sendREST($url, $data)));                
                            
                        if ($dataStatus->httpcode == 200) {
                            $getRetorno = json_decode($dataStatus->content);
                            $dadosPedido = $getRetorno->Order;
                            if(!empty($dadosPedido->shipments) && isset($dadosPedido->shipments)){
                                $updateOrder['shipments'][0]['id'] = $dadosPedido->shipments[0]->id;
                                if(isset($dadosPedido->shipments[0]->documents[0]->id)){
                                    $updateOrder['shipments'][0]['documents'][0]['id'] = $dadosPedido->shipments[0]->documents[0]->id;                                                              
                                }
                            }
                        }

                        $data         = json_encode($updateOrder);
                        $dataStatus   = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));
                        $bolProblema = false;                    
                        if($dataStatus->httpcode > 299){
                            $bolProblema = true;
                            $this->log_integration(
                                "Erro ao atualizar pedido {$orderId} para entregue",
                                "<h4>Ocorreu um erro ao atualizar o pedido {$orderId} para entregue na PluggTo</h4><ul><li>{$dataStatus->content}</li></ul>",
                                "E"
                            );
                        } else {
                            $this->log_integration(
                                "Pedido {$orderId} atualizado para entregue",
                                "<h4>O pedido {$orderId} foi atualizado para entregue na PluggTo</h4>",
                                "S"
                            );
                        }
                    }else{
                        $this->log_integration(
                            "Erro ao atualizar pedido {$orderId} para entregue",
                            "<h4>Ocorreu um erro ao atualizar o pedido {$orderId} para entregue na PluggTo</h4><ul><li>Pedido sem data de entrega.</li></ul>",
                            "E"
                        );
                        $bolProblema = true;
                        $dataStatus = '';
                    }
                } else {
                    $bolProblema = true;
                    $dataStatus = '';
                }

            } elseif($paidStatus == OrderStatusConst::WAITING_SHIPPING_TO_TRACKING) {
                echo "Aguardando Coleta/Envio 53!\n";
                //(Enviar dados de tracking)
                // Data previsao de entrega do pedido integrado no conectala
                if($logisticaProria == false){
                    // Data previsao de entrega do pedido integrado no conectala

                    $freights = $this->getDataPrevisaoEntregaOrderIdConectala($orderId);
                    
                    if(!empty($freights)){
                    $freights = $freights[0];                    
                        $codigoRastreio     = $freights['codigo_rastreio'] ?? "";
                        $shipping_company   = $freights['ship_company'] ?? "";
                        $shipping_method    = $freights['method'] ?? "";
                        $prazoprevisto      = $freights['prazoprevisto'] ?? "";
                        $urlRastreio        = $freights['url_tracking'] ?? "";
                    }

                    $updateOrder = array();
                    $updateOrder['status'] = "shipping_informed";

                    $updateOrder['shipments'][0]['shipping_company']        = $shipping_company ?? '';
                    $updateOrder['shipments'][0]['shipping_method']         = $shipping_method ?? '';
                    $updateOrder['shipments'][0]['date_shipped']            = "";
                    //$updateOrder['shipments'][0]['status']                  = "shipping_informed";
                    $updateOrder['shipments'][0]['estimate_delivery_date']  = $prazoprevisto ?? "";
                    $updateOrder['shipments'][0]['track_code']              = $codigoRastreio ?? "";
                    $updateOrder['shipments'][0]['track_url']               = $urlRastreio ?? "";


                    //verifica se existe o id do array shipment                
                    $url  = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                    $data = "";
                    $dataStatus   = json_decode(json_encode($this->sendREST($url, $data)));                
                        
                    if ($dataStatus->httpcode == 200) {
                        $getRetorno = json_decode($dataStatus->content);
                        $dadosPedido = $getRetorno->Order;
                        if(!empty($dadosPedido->shipments)){
                            $updateOrder['shipments'][0]['id'] = $dadosPedido->shipments[0]->id;
                        }
                    }

                    $data         = json_encode($updateOrder);
                    $dataStatus   = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));
                    $bolProblema  = false;
                    if($dataStatus->httpcode > 299){
                        $bolProblema = true;
                        $this->log_integration(
                            "Erro ao enviar dados de rastreamento do pedido {$orderId}",
                            "<h4>Ocorreu um erro ao enviar dados de rastreamento do pedido {$orderId} para entregue na PluggTo</h4><ul><li>{$dataStatus->content}</li></ul>",
                            "E"
                        );
                    } else {
                        $this->log_integration(
                            "Dados de rastreamento do pedido {$orderId} enviados",
                            "<h4>Os dados de rastreamento do pedido {$orderId} foram enviados com sucesso para PluggTo</h4>",
                            "S"
                        );
                    }
                    //$removeList   = false;
                } else {
                    $url  = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                    $data = "";
                    $dataOrder   = json_decode(json_encode($this->sendREST($url, $data)), true);
                    $bolProblema = true;
                    $dataStatus = '';
                    if ($dataOrder['httpcode'] == 200) {
                        $bolProblema = false;
                        $dataOrder = json_decode($dataOrder['content'], true) ?? [];
                        $dataOrder = $dataOrder['Order'] ?? [];
                        if ($dataOrder['status'] && in_array($dataOrder['status'], ['shipped', 'delivered'])) {
                            $shipment = is_array($dataOrder['shipments']) ? current($dataOrder['shipments']) : null;
                            $shipmentDate = $shipment['date_shipped'] ?? null;
                            if (date('Y', strtotime($shipmentDate)) > 2000) {
                                $shipmentDate = date('Y-m-d H:i:s', strtotime(substr($shipmentDate, 0, 20)));
                                $this->model_orders->updateDataEnvioStatus55($orderId, $shipmentDate, OrderStatusConst::SHIPPED_IN_TRANSPORT_NOTIFY_MKTPLACE);
                                if ($this->db->trans_status() === false) {
                                    $this->db->trans_rollback();
                                    return array('error' => true, 'data' => "Failure to communicate to the database");
                                }
                                $this->db->trans_commit();
                                echo "Pedido {$orderId} com status Aguardando Coleta/Envio 53 (pluggto: {$dataOrder['status']}) alterado para status 55 (Em Transporte (Avisar o marketplace que o pedido foi enviado))\n";
                            }
                        }
                    }
                }

            }  elseif($paidStatus == OrderStatusConst::SHIPPED_IN_TRANSPORT) {
                echo "Em Transporte  5!\n";
                if($logisticaProria == false){
                    $dataEnvio = $this->getDataEnvioOrderIdConectala($orderId);                

                    if(!empty($dataEnvio)){
                        $url          = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                        $updateOrder  = array();
                        $updateOrder['status']                                  = "shipped";                    
                        $updateOrder['shipments'][0]['status']                  = "shipped";
                        $updateOrder['shipments'][0]['date_shipped']            = $dataEnvio ?? "";
                        
                        //verifica se existe o id do array shipment                
                        $url  = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                        $data = "";
                        $dataStatus   = json_decode(json_encode($this->sendREST($url, $data)));                
                            
                        if ($dataStatus->httpcode == 200) {
                            $getRetorno = json_decode($dataStatus->content);
                            $dadosPedido = $getRetorno->Order;
                            if(!empty($dadosPedido->shipments) && isset($dadosPedido->shipments)){
                                $updateOrder['shipments'][0]['id'] = $dadosPedido->shipments[0]->id;
                                if(isset($dadosPedido->shipments[0]->documents[0]->id)){
                                    $updateOrder['shipments'][0]['documents'][0]['id'] = $dadosPedido->shipments[0]->documents[0]->id;                                                              
                                }
                            }
                        }

                        $data         = json_encode($updateOrder);
                        $dataStatus   = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));
                        $bolProblema  = false; 
                        $removeList   = false;                   
                        if($dataStatus->httpcode > 299){
                            $bolProblema = true;
                            $removeList   = true;
                            $this->log_integration(
                                "Erro ao atualizar status do pedido {$orderId} para despachado/enviado",
                                "<h4>Ocorreu um erro para atualizar o status do pedido {$orderId} para despachado/enviado na PluggTo</h4><ul><li>{$dataStatus->content}</li></ul>",
                                "E"
                            );
                        } else {
                            $this->log_integration(
                                "Status do pedido {$orderId} atualizado para despachado/enviado",
                                "<h4>O status do pedido {$orderId} foi atualizado para despachado/enviado com sucesso na PluggTo</h4>",
                                "S"
                            );
                        }
                    }else{
                        $bolProblema = true;
                        $dataStatus = '';
                        $this->log_integration(
                            "Erro ao atualizar status do pedido {$orderId} para despachado/enviado",
                            "<h4>Ocorreu um erro para atualizar o status do pedido {$orderId} para despachado/enviado na PluggTo</h4><ul><li>Data de envio não informada.</li></ul>",
                            "E"
                        );
                    }
                } else {
                    $url = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                    $data = "";
                    $dataOrder = json_decode(json_encode($this->sendREST($url, $data)), true);
                    $bolProblema = true;
                    $dataStatus = '';
                    if ($dataOrder['httpcode'] == 200) {
                        $bolProblema = false;
                        $dataOrder = json_decode($dataOrder['content'], true) ?? [];
                        $dataOrder = $dataOrder['Order'] ?? [];
                        if ($dataOrder['status'] && in_array($dataOrder['status'], ['delivered'])) {
                            $shipment = is_array($dataOrder['shipments']) ? current($dataOrder['shipments']) : null;
                            $deliveredDate = $shipment['date_delivered'] ?? $dataOrder['modified'] ?? null;
                            echo "Pedido {$orderId} ENTREGUE com data de entrega: {$deliveredDate}\n";
                            if (date('Y', strtotime($deliveredDate)) > 2000) {
                                $deliveredDate = date('Y-m-d H:i:s', strtotime(substr($deliveredDate, 0, 20)));
                                $this->model_orders->updateDataEntregaStatus60($orderId, $deliveredDate, OrderStatusConst::DELIVERED_NOTIFY_MKTPLACE);
                                $this->model_freights->updateDataEntrega($orderId, [
                                    'date_delivered' => $deliveredDate,
                                    'updated_date' => date('Y-m-d H:i:s')
                                ]);
                                if ($this->db->trans_status() === false) {
                                    $this->db->trans_rollback();
                                    return array('error' => true, 'data' => "Failure to communicate to the database");
                                }
                                $this->db->trans_commit();
                                echo "Pedido {$orderId} com status Em Transporte 5 (pluggto: {$dataOrder['status']}) alterado para status 60 (Entregue (Avisar o marketplace que o pedido foi entregue))\n";
                            }
                        }
                    }
                }
            }elseif($paidStatus == OrderStatusConst::WAITING_TRACKING) {
                echo "Aguardando rastreamento 40!\n";
                if($logisticaProria == true) {
                    // Obter dados do pedido
                    $url          = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                    $data         = "";                
                    $dataOrder    = json_decode(json_encode($this->sendREST($url, $data)));
                
                    if ($dataOrder->httpcode != 200) {
                        $msgError = "Não foi possível localizar o pedido {$orderId}, idPluggto: {$idPluggto}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration(
                            "Erro para atualizar o pedido {$orderId}",
                            "<h4>Não foi possível localizar dados do pedido {$orderId} na PluggTo</h4>",
                            "E"
                        );
                        continue;
                    }
                    
                    $registro = json_decode($dataOrder->content);
                    $registro = $registro->Order;                
                    $historico = $registro->status;                       
                        
                    $numeroPedidoPluggTo = $registro->id;
                    $numeroPedido        = $orderId;
                    $shipments           = $registro->shipments[0] ?? null;

                    if ($shipments === null) {
                        echo "O pedido {$orderId} ainda não possui rastreamento.\n";
                        $bolProblema = true;
                        continue;
                    }

                    $strUrlRastreio      = $shipments->track_url ?? '';    
                    if($strUrlRastreio !== '') {
                        $numNumeroColeta            = $registro->id;                            
                        $strTransportador           = $shipments->shipping_company ?? '';
                        $strRastreio                = $shipments->track_code ?? null;
                    }

                    // Pacote com rastreio, mas com dados faltando.
                    if (
                        (
                            empty($shipments->track_url) ||
                            empty($shipments->track_code) ||
                            empty($shipments->shipping_company) ||
                            empty($shipments->shipping_method)
                        ) &&
                        (
                            !empty($shipments->track_url) ||
                            !empty($shipments->track_code) ||
                            !empty($shipments->shipping_company) ||
                            !empty($shipments->shipping_method)
                        )
                    ) {
                        $messageError = array();

                        // Pacote sem código de rastreio
                        if (empty($shipments->track_code)) {
                            $messageError[] = 'Código de Rastreio';
                        }
                        // Pacote sem transportadora
                        if (empty($shipments->shipping_company)) {
                            $messageError[] = 'Transportadora';
                        }
                        // Pacote sem método de envio
                        if (empty($shipments->shipping_method)) {
                            $messageError[] = 'Método de Envio';
                        }
                        // Pacote sem URL de rastreio
                        if (empty($shipments->track_url)) {
                            $messageError[] = 'URL de Rastreamento';
                        }

                        $this->log_integration(
                            "Erro para atualizar o pedido $orderId",
                            "<h4>Não foi possível atualizar o pedido $orderId</h4><p>Rastreio com informações incompletas, reveja: " . implode(',', $messageError) ."</p>",
                            "E"
                        );
                        $bolProblema = true;
                        continue;
                    }

                    if($strUrlRastreio === '' || (!isset($strRastreio) && $strUrlRastreio == null)){
                        //Se não tiver informações de Rastreio não irá fazer nada e irá ficar no log, senão irá buscar dados e colocar em suas respectivas tabelas order e freights
                        echo "Não foi possível buscar o rastreamento por não haver informações. Pedido {$orderId}! \n";                            
                        $bolProblema = true;
                        continue;
                    }

                    $url_label_a4       = $registro->shipments[0]->documents[0]->url ?? '';
                    $url_label_thermic  = '';
                    $url_label_zpl      = '';
                    $url_plp            = '';

                    $dadosProdutoItemPedido = $this->consultaItemPedidoProduto($numeroPedido);

                    if($dadosProdutoItemPedido == false){
                        $msgError = "Não foi possível encontrar itens de Produto do Pedido {$orderId}, id PluggTo: {$numeroPedidoPluggTo}! 
                                        ORDER_INTEGRATION='Problema ao buscar informações do Pedido {$orderId}', 
                                        RETORNO='Problema ao buscar informações do Produto, do Pedido {$orderId}'";
                        
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para encontrar itens de Produto do pedido {$orderId}", 
                            "<h4>Não foi possível ao buscar informações itens do Produto do pedido {$orderId}</h4>", "E");
                        $bolProblema = true;
                        continue;
                    }

                    foreach ($dadosProdutoItemPedido as $itemPedido){
                        $product_id = $itemPedido['product_id'];                          
            
                        if (!$product_id){
                            $msgError = "Não foi possível encontrar informações do produto {$product_id} do Pedido {$orderId}, id PluggTo: {$numeroPedidoPluggTo}! 
                                ORDER_INTEGRATION='Problema ao buscar informações do Produto {$product_id}', 
                                RETORNO='Problema ao buscar informações do Produto, do Pedido {$orderId}'";
                            //echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $this->log_integration("Erro para ao buscar informações do Produto {$product_id} do pedido {$orderId}", 
                                "<h4>Não foi possível ao buscar informações do Produto {$product_id} do pedido {$orderId}</h4>", "E");
                            $bolProblema = true;
                            continue;
                        }
                    }
                    
                    $itemsPedido = $registro->items;
                    if(empty($itemsPedido)){ 
                        $this->log_integration(
                            "Erro para atualizar o pedido {$orderId}",
                            "<h4>Erro - Itens do pedido {$orderId}, estão incompletos.</h4>",
                            "E"
                        );
                        $bolProblema = true;
                        continue;
                    }

                    $transportadora     = $shipments->shipping_company;
                    $method             = $shipments->shipping_method;
                    $numNumeroColeta    = $shipments->shipping_method_id;
                    
                    $dataEnvioLogistica = $shipments->date_shipped;
                    $codigoRastreamento = $shipments->track_code ?? null;
                    $data_etiqueta      = date('Y-m-d H:i:s');                            
                    $frete              = number_format($registro->shipping, 2,".","") ?? 0;
                    $idSevico           = empty($numNumeroColeta) ? '' : $numNumeroColeta;
                    $status_ship        = $shipments->status ?? ''; //"shipped|delivered|issue"

                    if($shipments->estimate_delivery_date != '0000-00-00 00:00:00' && !empty($shipments->estimate_delivery_date)){
                        $dataPrevista = date('Y-m-d', strtotime($shipments->estimate_delivery_date));
                    } else {
                        $dataPrevista = '';                                
                    }
                        
                    $sgp = 0; //transportadora
                    $solicitou_plp = 0;
                    
                    
                        
                    $this->load->model('model_freights');
                    $this->db->trans_begin();   // Inicia transação

                    foreach($itemsPedido as $item)
                    {
                        $verifyProduct = $this->getProductForSku($item->sku);
                        if(empty($verifyProduct)){
                            $verifyProduct = $this->getProductVariationForSku($item->sku);
                            if(empty($verifyProduct)){
                                $this->log_integration(
                                    "Erro para atualizar o pedido {$orderId}",
                                    "<p>Item do pedido {$item->sku}, não cadastrado no sellercenter.</p>",
                                    "E"
                                );
                                $bolProblema = true;
                                $this->db->trans_rollback();
                                continue 2;
                            }
                        }                                

                        $this->model_freights->create(array(
                            'order_id'              => $numeroPedido,
                            'item_id'               => $product_id,
                            'company_id'            => $this->company,
                            'ship_company'          => $transportadora,
                            'status_ship'           => '',
                            'date_delivered'        => '',
                            'ship_value'            => $frete,
                            'prazoprevisto'         => $dataPrevista,
                            'idservico'             => $idSevico,  //$iten->service_id, verificar após informarem se tem em algum lugar
                            'codigo_rastreio'       => $codigoRastreamento,
                            'link_etiqueta_a4'      => !isset($url_label_a4) || empty($url_label_a4) ? null : $url_label_a4,
                            'link_etiqueta_termica' => !isset($url_label_thermic) || empty($url_label_thermic) ? null : $url_label_thermic,
                            'link_etiquetas_zpl'    => !isset($url_label_zpl) || empty($url_label_zpl) ? null : $url_label_zpl,
                            'link_plp'              => !isset($url_plp) || empty($url_plp) ? null : $url_plp,
                            'data_etiqueta'         => $data_etiqueta,
                            'CNPJ'                  => !isset($carrier_cnpj) || empty($carrier_cnpj) ? null : $carrier_cnpj,
                            'method'                => $method,
                            'solicitou_plp'         => $solicitou_plp,
                            'sgp'                   => $sgp,
                            'url_tracking'          => $strUrlRastreio,
                            'updated_date'          => date('Y-m-d H:i:s')
                            )
                        );
                    }                    

                    $statusRet = 51;
                    $historicoRet = 'Pedido enviado pela transportadora';
                    $data = $historicoRet;
                    $dataStatus = 200;

                    $this->model_orders->updatePaidStatus($numeroPedido, OrderStatusConst::PLP_SEND_TRACKING_MKTPLACE);

                    if ($this->db->trans_status() === FALSE){
                        $bolProblema = true;
                        $this->db->trans_rollback();
                        return array('error' => true, 'data' => "Failure to communicate to the database");
                    }
            
                    $this->db->trans_commit();
                    $bolProblema = false;                    
                     //fim do bloco só irá entrar se houver infomrações de dados de Rastreio do produto.
                } else {
                    $msgError = "Não foi possível por não ser logistica prória do Pedido: {$orderId}, idPluggto: {$idPluggto}! ";
                    echo "{$msgError}\n";
                    $bolProblema = true;
                    $dataStatus = '';
                    $this->log_integration(
                        "Erro ao importar rastreamento do pedido {$orderId}",
                        "<h4>O pedido {$orderId} não utiliza logistica própria.</h4><ul><li>Não é possível importar os dados de logística da PluggTo</li></ul>",
                        "E"
                    );
                    $this->removeOrderIntegration($idIntegration);
                    $this->log_data('batch', $log_name, "Remove da fila, não é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");                      
                }
            } elseif($paidStatus == OrderStatusConst::WITH_TRACKING_WAITING_SHIPPING) {
                echo "Com rastreamento, aguardando envio 43!\n";
                if($logisticaProria == true) {
                    // Obter dados do pedido
                    $url          = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                    $data         = "";                
                    $dataOrder    = json_decode(json_encode($this->sendREST($url, $data)));
                
                    if ($dataOrder->httpcode != 200) {
                        $msgError = "Não foi possível localizar o pedido {$orderId}, idPluggto: {$idPluggto}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
                        continue;
                    }
                    
                    $registro = json_decode($dataOrder->content);
                    $registro = $registro->Order;                
                    $historico = $registro->status;

                    $dataOrder = $registro ?? [];
                    if (!in_array($dataOrder->status, ['shipped'])) {
                        $msgError = "O pedido {$orderId} ainda não está na situação em transporte na PluggTo, idPluggto: {$idPluggto}! ORDER_INTEGRATION=" . json_encode($orderIntegration) . ", RETORNO=" . json_encode($dataOrder);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        continue;
                    }

                    $numeroPedidoPluggTo = $registro->id;
                    $numeroPedido        = $orderId;
                    $shipments           = $registro->shipments[0] ?? null; 
 
                    $dataEnvioLogistica  = $shipments->date_shipped ?? null;

                    if (empty($dataEnvioLogistica)
                        || (date('Y', strtotime($dataEnvioLogistica)) <= 2000)) {
                        echo "Não existe data de envio para o transporte \n";
                        continue;
                    }

                    // Inicia transação
                    $this->db->trans_begin();

                    $statusRet = 55;
                    $historicoRet = 'Pedido confirmado a data de envio pela transportadora';
                    $data = $historicoRet;
                    $dataStatus = 200;

                    $this->model_orders->updateDataEnvioStatus55($orderId, $dataEnvioLogistica, $statusRet);

                    if ($this->db->trans_status() === FALSE){
                        $this->db->trans_rollback();
                        $bolProblema = true;
                        return array('error' => true, 'data' => "Failure to communicate to the database");
                    }
            
                    $this->db->trans_commit();
                    $bolProblema = false;
                } else {
                    $msgError = "Não foi possível por não ser logistica prória do Pedido: {$orderId}, idPluggto: {$idPluggto}! ";
                    echo "{$msgError}\n";
                    $bolProblema = true;

                    $dataStatus = ''; 
                    $this->removeOrderIntegration($idIntegration);
                    $this->log_integration(
                        "Erro ao importar rastreamento do pedido {$orderId}",
                        "<h4>O pedido {$orderId} não utiliza logistica própria.</h4><ul><li>Não é possível importar os dados de logística da PluggTo</li></ul>",
                        "E"
                    );
                    $this->log_data('batch', $log_name, "Remove da fila, não é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");                      
                }
            } elseif($paidStatus == OrderStatusConst::SHIPPED_IN_TRANSPORT_45){
                echo "Em transporte 45!\n";
                if($logisticaProria == true) {
                    // Obter dados do pedido
                    $url          = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                    $data         = "";                
                    $dataOrder    = json_decode(json_encode($this->sendREST($url, $data)));
                
                    if ($dataOrder->httpcode != 200) {
                        $msgError = "Não foi possível localizar o pedido {$orderId}, idPluggto: {$idPluggto}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
                        continue;
                    }
                    
                    $registro = json_decode($dataOrder->content);
                    $registro = $registro->Order;                
                    $historico = $registro->status;

                    $numeroPedidoPluggTo = $registro->id;
                    $numeroPedido        = $orderId;
                    $shipments           = $registro->shipments[0] ?? null;

                    $dataEntrega = $shipments->date_delivered ?? null;
                    $dataOrder = $registro ?? [];
                    if (!in_array($dataOrder->status, ['delivered'])) {
                        $msgError = "O pedido {$orderId} ainda não está na situação entregue na PluggTo, idPluggto: {$idPluggto}! ORDER_INTEGRATION=" . json_encode($orderIntegration) . ", RETORNO=" . json_encode($dataOrder);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        continue;
                    } else {
                        if (empty($dataEntrega)
                            || (date('Y', strtotime($dataEntrega)) <= 2000)) {
                            $msgError = "Pedido {$orderId} ENTREGUE, mas sem data de entrega, pegando data modificação. idPluggto: {$idPluggto}! ORDER_INTEGRATION=" . json_encode($orderIntegration) . ", RETORNO=" . json_encode($dataOrder);
                            echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $dataEntrega = $dataOrder->modified ?? null;
                        }
                    }

                    if (empty($dataEntrega)
                        || (date('Y', strtotime($dataEntrega)) <= 2000)) {
                        $msgError = "O pedido {$orderId} ainda não possui data de entrega na PluggTo, idPluggto: {$idPluggto}! ORDER_INTEGRATION=" . json_encode($orderIntegration) . ", RETORNO=" . json_encode($dataOrder);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        continue;
                    }

                    $dataEntrega = date('Y-m-d H:i:s', strtotime(substr($dataEntrega, 0, 20)));
                    // Inicia transação
                    $this->db->trans_begin();
                    $statusRet = OrderStatusConst::DELIVERED_NOTIFY_MKTPLACE;
                    $historicoRet = 'Pedido confirmado a data de entrega pela transportadora';
                    $data = $historicoRet;
                    $dataStatus = 200;

                    $this->model_freights->updateDataEntrega($orderId, array('date_delivered' => $dataEntrega,
                                                                             'updated_date' => date('Y-m-d H:i:s')));
                    $this->model_orders->updateDataEntregaStatus60($orderId, $dataEntrega, $statusRet);

                    if ($this->db->trans_status() === FALSE){
                        $this->db->trans_rollback();
                        return array('error' => true, 'data' => "Failure to communicate to the database");
                    }
            
                    $this->db->trans_commit();
                    $bolProblema = false;
                } else {
                    $msgError = "Não foi possível por não ser logistica prória do Pedido: {$orderId}, idPluggto: {$idPluggto}! ";
                    echo "{$msgError}\n";
                    $bolProblema = true;
                    $dataStatus = ''; 
                    $this->removeOrderIntegration($idIntegration);
                    $this->log_integration(
                        "Erro ao importar rastreamento do pedido {$orderId}",
                        "<h4>O pedido {$orderId} não utiliza logistica própria.</h4><ul><li>Não é possível importar os dados de logística da PluggTo</li></ul>",
                        "E"
                    );
                    $this->log_data('batch', $log_name, "Remove da fila, não é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");                      
                }
            } else {
                if (in_array($paidStatus, [
                    OrderStatusConst::CANCELED_BY_SELLER,
                    OrderStatusConst::CANCELED_AFTER_PAYMENT
                ])) {
                    echo "Cancelado pelo seller (95) ou depois do pagamento (97)!\n";
                    //altera para cancelado,
                    $url          = "https://api.plugg.to/orders/$idPluggto?access_token={$access_token}";
                    $updateOrder  = array();
                    $updateOrder['status']  = 'canceled'; 
                    $data         = json_encode($updateOrder);
                    $dataStatus   = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));
                    if ($dataStatus->httpcode >= 300) {
                        $this->log_integration(
                            "Erro ao cancelar o pedido {$orderId}",
                            "<h4>Ocorreu um problema ao cancelar o pedido {$orderId}</h4><ul><li>{$dataStatus->content}</li></ul>",
                            "E"
                        );
                    } else {
                        $this->log_integration(
                            "Pedido {$orderId} cancelado com sucesso",
                            "<h4>O pedido {$orderId} foi cancelado com sucesso na PluggTo.</h4>",
                            "S"
                        );
                    }
                    echo "{$msgError}\n";                    
                }                
            }

            if ($bolProblema == true) {
                echo "DATA REQUEST: \n";
                print_r($dataStatus);
                echo "\n";
                if(isset($dataStatus->httpcode) &&  ($dataStatus->httpcode > 299)){                
                    $msgError = "Não foi possível atualizar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dataStatus);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    if ($removeList) { // deve ser removido da fila
                        // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                        if ($orderCancel)
                            $this->removeAllOrderIntegration($orderId);
                        else
                            $this->removeOrderIntegration($idIntegration);
                    }
                    // formatar mensagens de erro para log integration                
                    $getRetorno = json_decode($dataStatus->content);
                    $errors = $getRetorno->details; 
                    if(is_object($errors))
                        $errors = $getRetorno->details->message; 
                    
                    //$this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>$errors</li></ul>", "E");
                    continue;
                }
                
                $msgError = "Não foi possível atualizar o pedido {$orderId}!";
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                
                // formatar mensagens de erro para log integration
                //$this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4>", "E");
                continue;
            }


            if ($removeList) { // deve ser removido da fila
                // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                if ($orderCancel)
                    $this->removeAllOrderIntegration($orderId);
                else
                    $this->removeOrderIntegration($idIntegration);
            }

            if(isset($dataStatus->httpcode) &&  ($dataStatus->httpcode == 200 || $dataStatus->httpcode == 201)){
                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li>Pedido {$orderId} atualizado com sucesso, status atual = {$historicoRet}</li></ul>", "S");
                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                echo "Pedido {$orderId} atualizado com sucesso!\n";
                continue;
            }
            $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li>Pedido {$orderId} atualizado com sucesso, status atual = {$historicoRet}</li></ul>", "S");
            $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso!", "I");
            echo "Pedido {$orderId} atualizado com sucesso!\n";
            continue;
        }
    }

    /**
     * Recupera data do envio do pedido na Conectala
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna data de envio
     */
    private function getDataEnvioOrderIdConectala($orderId)
    {
        $order = $this->db
            ->get_where('orders',
                array(
                    'store_id'  => $this->store,
                    'id'        => $orderId,
                )
            )->row_array();

        if (!$order) return false;

        return $order['data_envio'];
    }

    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku($sku)
    {
        return $this->db->get_where('products use index (store_sku)',
            array(
                'store_id'  => $this->store,
                'sku'       => $sku
            )
        )->row_array();
    }

    /**
     * Recupera dados do produto variacao pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductVariationForSku($sku)
    {
          $prd_variant = $this->db
            ->get_where('prd_variants',
                array(
                    'sku'  => $sku
                    )
            )->row_array();

        if (!$prd_variant) return false;

        return $prd_variant;
    }


    /**
     * Recupera data do entrega do pedido na Conectala
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna data de entrega
     */
    private function getDataEntregaOrderIdConectala($orderId)
    {
        $order = $this->db
            ->get_where('orders',
                array(
                    'store_id'  => $this->store,
                    'id'        => $orderId,
                )
            )->row_array();

        if (!$order) return false;

        return $order['data_entrega'];
    }


    private function getDataEntregue($orderId)
    {
        $freights = $this->db
            ->get_where('freights',
                array(
                    'company_id'  => $this->company,
                    'order_id'    => $orderId,
                    )
            )->row_array();

        if (!$freights) return false;

        return $freights['date_delivered'];
    }

     /**
     * Recupera data previsao da entrega do pedido na Conectala
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna data de previsao da entrega
     */
    private function getDataPrevisaoEntregaOrderIdConectala($orderId)
    {
        $freights = $this->db
            ->select(array('prazoprevisto',
                           'ship_company',
                           'method',
                           'url_tracking',
                           'ship_value',
                           'codigo_rastreio'))
            ->from('freights')
            ->where(array(
                    'company_id'  => $this->company,
                    'order_id'    => $orderId,
            ))
            ->get()
            ->result_array();

        if (!$freights) return false;

        return $freights;
    }



    /**
     * Recupera os pedidos para integração
     *
     * @return array Retorno os pedidos na fila para integrar
     */
    private function getOrdersForUpdate()
    {
        
        return $this->db
            ->from('orders_to_integration')
            ->where(
                array(
                    'store_id'  => $this->store,
                    'new_order' => 0
                )
            )
            ->order_by('id', 'asc')
            ->group_by("order_id")
            ->get()
            ->result_array();
    }

     /**
     * Recupera os pedidos para integração
     *
     * @return array Retorna dados de um pedido
     */
    public function getOrder($order_id)
    {        
        return $this->db
        ->select(
            array(
                'orders.id as order_id',
                'orders.numero_marketplace as numero_marketplace',
                'orders.customer_name as name_order',
                'orders.customer_address as address_order',
                'orders.customer_address_num',
                'orders.customer_address_compl',
                'orders.customer_address_neigh',
                'orders.customer_address_city',
                'orders.customer_address_uf',
                'orders.customer_address_zip',
                'orders.data_entrega',
                'orders.data_envio',
                'orders.data_coleta',
                'orders.data_pago',
                'orders.company_id',
                'orders.order_id_integration',              
                'orders.customer_phone as phone_order',
                'orders.date_time as date_created',
                'orders.gross_amount as gross_amount',
                'orders.discount as discount_order',
                'orders.total_ship as total_ship',
                'orders.ship_company_preview as ship_company',
                'orders.ship_service_preview as ship_service',
                'orders.frete_real',
                'orders.paid_status',
                'orders.total_order',
                'orders.net_amount',
                'orders.origin as origin',
                'orders_payment.id as id_payment',
                'orders_payment.parcela as parcela_payment',
                'orders_payment.data_vencto as vencto_payment',
                'orders_payment.valor as valor_payment',
                'orders_payment.forma_desc as forma_payment',
                
                'nfes.nfe_num'
            )
        )
        ->from('orders')
        ->join('orders_payment', 'orders.id = orders_payment.order_id', 'left')        
        ->join('nfes', 'orders.id = nfes.order_id', 'left')
        ->where(
            array(
                'orders.store_id'   => $this->store,
                'orders.id'         => $order_id
            )
        )
        ->get()
        ->row_array();
    }
   
    /**
     * Recupera código do pedido na pluggto
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna código do pedido na pluggto
     */
    private function getOrderIdPluggto($orderId)
    {
        $order = $this->db
            ->get_where('orders',
                array(
                    'store_id'  => $this->store,
                    'id'        => $orderId,
                )
            )->row_array();

        if (!$order) return false;

        return $order['order_id_integration'];
    }

    /**
     * Remove o pedido da fila de integração
     *
     * @param   int     $id Código da integração
     * @return  bool        Retornar o status da exclusão
     */
    private function removeOrderIntegration($id)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'  => $this->store,
                'id'        => $id,
                'new_order' => 0
            )
        ) ? true : false;
    }

    /**
     * Remove todos os pedidos da fila de integração
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retornar o status da exclusão
     */
    private function removeAllOrderIntegration($orderId)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'  => $this->store,
                'order_id'  => $orderId
            )
        ) ? true : false;
    }

    /**
     * Cria dados de faturamento do pedido e atualiza o status do pedido para 52
     *
     * @param   array   $data   Dados da nfe para inserir
     * @return  bool            Retorna o status da criação
     */
    private function createNfe($data)
    {
        $sqlNfe     = $this->db->insert_string('nfes', $data);
        $insertNfe  = $this->db->query($sqlNfe) ? true : false;

        if (!$insertNfe) return false;	
		
        return $this->updateStatusForOrder($data['order_id'], 52, 3);
    }

    /**
     * Recupera se o pedido precisa ser cancelado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se existe cancelamento
     */
    private function getOrderCancel($orderId)
    {
        $orderCancel = $this->db
            ->get_where('orders_to_integration',
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store,
                    'paid_status'   => 97
                )
            )->row_array();

        if (!$orderCancel) return false;

        return true;
    }

    /**
     * Recupera se o pedido já foi integrado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido já foi integrado
     */
    private function getIntegratedOrder($orderId)
    {
        $orderCreate = $this->db
        ->get_where('orders',
            array(
                'id'        => $orderId,
                'store_id'  => $this->store                
            )
        )->row_array();

        return $orderCreate ? true : false;
    }

    /**
     * Recupera se o pedido já tem uma NF-e
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido tem NF-e
     */
    private function getOrderWithNfe($orderId)
    {
        return $this->db
            ->get_where('nfes',
                array(
                    'order_id'      => $orderId
                )
            )->num_rows() == 0 ? false : true;
    }

    /**
     * Atualiza status de um pedido
     *
     * @param   int     $orderId        Código do pedido
     * @param   int     $status         Código do status
     * @param   int     $verifyStatus   Código do status para verificação
     * @return  bool                    Retorna o status da atualização
     */    

    private function updateStatusForOrder($orderId, $status, $verifyStatus = null)
    {
        $where = array(
            'id'        => $orderId,
            'store_id'  => $this->store,
        );
        if ($verifyStatus) $where['paid_status'] = $verifyStatus;

        return $this->db->where($where)->update('orders', array('paid_status' => $status)) ? true : false;
    }

    /**
     * Salva um arquivo XML da NFe
     *     
     * @return  bool                Retorna o status da importação do xml
     */
    private function saveXML($nfe_key, $nfe_link, $nfe_number, $nfe_serie)
    { 
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;        

        $namePathStore = date('m-Y');

        $targetDir = 'assets/images/xml/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $this->store.'/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $namePathStore.'/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $arquivo = fopen($targetDir . $nfe_key . ".xml",'w');

        if ($arquivo == false) return false;

        fwrite($arquivo, $dataXml);

        fclose($arquivo);

        return true;
    }

    /**
     * Recupera dados do item do pedido, informações do produto para transportadora
     *
     * @param   int         $orderId    Código do pedido
     * @return  dados_pedido            Retorna dados do intem do pedido para o envio do produto, transportadora
     */
    private function consultaItemPedidoProduto($orderId)
    {
        $itemsPedido = $this->db
            ->select('oi.id, oi.order_id, oi.product_id, oi.sku')
            ->from('orders_item oi')
            ->where(array(
                    'store_id'  => $this->store,
                    'order_id'  => $orderId,
            ))
            ->get()
            ->result_array();

        if (!$itemsPedido) return false;

        return $itemsPedido;
    }

}
