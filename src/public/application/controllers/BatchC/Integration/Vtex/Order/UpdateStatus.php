<?php

/**
 * Class UpdateStatus
 *
 * php index.php BatchC/Integration/Vtex/Order/UpdateStatus run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Vtex/Main.php";

class UpdateStatus extends Main
{
    private $orderId;
    private $idVtex;
    private $idIntegration;
    private $paidStatus;
    private $dataOrderLog = null;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setJob('UpdateStatus');

        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_freights');
        $this->load->model('model_stores');
        $this->load->model('model_frete_ocorrencias');

        $this->load->library('calculoFrete');
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
        echo "Pegando pedidos para enviar... \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        // Recupera os pedidos
        $this->updateOrders();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Atualizaçao dos pedidos já integrados
     * @throws Exception
     */
    public function updateOrders()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "E");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $orders = $this->getOrdersForUpdate();

        foreach ($orders as $orderIntegration) {

            // Define globais
            $this->setOrderId($orderIntegration['order_id']);

            // Código do pedido integrado na Vtex
            $idVtex = $this->getOrderIdVtex($this->orderId);

            $this->setVtexId($idVtex);
            $this->setIntegrationId($orderIntegration['id']);
            $this->setPaidStatus($orderIntegration['paid_status']);
            $this->setUniqueId($this->orderId); // define novo unique_id

            // Não encontrou o código do pedido integrado
            if (!$idVtex) {
                echo "Não foi possível localizar o código do pedido integrador. PEDIDO_CONECTA={$this->orderId} para atualizar! ORDER_INTEGRATION=".json_encode($orderIntegration)."\n";
                continue;
            }

            // Pedido ainda não foi integrado
            $integratedOrder = $this->getIntegratedOrder($this->orderId);
            if (!$integratedOrder) {
                echo "Pedido ainda não integrado. PEDIDO_CONECTA={$this->orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration)."\n";
                continue;
            }

            // Pedido para cancelar
            $orderCancel = $this->getOrderCancel($this->orderId);
            if ($orderCancel) {
                //cancelar na vtex
                $msgError = "Pedido deve ser cancelado. PEDIDO_CONECTA={$this->orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                $this->setPaidStatus(97);
            }

            // Faz um de-prara com os status
            $status = $this->getStatusIntegration($this->paidStatus);
            $statusId = $status['code'] ?? null;
            $statusName = $status['name'] ?? null;

            // Ignorar status, não deve ser alterado no ERP
            if ($status === null) {
                $msgError = "Chegou status={$this->paidStatus}, não deve ser integrado, apenas remover da fila! ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "E");
                $this->removeOrderIntegration($this->idIntegration);
                continue;
            }

            // Aguardar pedido ser faturado na Vtex
            elseif ($statusId === 'invoice') {

                $this->getAndSaveNFeERP();
                // Fim - ir para o próximo pedido
                continue;

            }

            // cancelar pedido
            // cancelar somente se não foi faturado
            // caso já tenha faturado, enviar uma nota de devolução
            elseif ($statusId === 'cancel') {

                $cancelERP = $this->getAndSendCancelERP();
                if (!$cancelERP) continue;

            }

            // Enviar/receber dados de rastreamento
            elseif ($statusId === 'tracking') {

                $saveSendTracking = $this->getSaveAndSendTracking();
                if (!$saveSendTracking) continue;

            }

            // Enviar/receber pedido enviado
            elseif ($statusId === 'shipped') {

                $sendShipped = $this->getAndSendOrderShipped();
                if (!$sendShipped) continue;

            }

            // Enviar/receber pedido em trânsito
            elseif ($statusId === 'in_transit') {

                $daveAndSendOccurrence = $this->getSaveAndSendOccurrence();
                if (!$daveAndSendOccurrence) continue;

            }

            // Enviar/receber dados de pedido entregue
            elseif ($statusId === 'delivered') {

                $sendDelivered = $this->getSendDelivered();
                if (!$sendDelivered) continue;

            }

            if ($orderCancel) // cancelamento remove todos os status da fila
                $this->removeAllOrderIntegration($this->orderId);
            else
                $this->removeOrderIntegration($this->idIntegration);

            $this->log_integration("Pedido {$this->orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li>O status do pedido {$this->orderId}, foi atualizado para {$statusName}</li></ul>", "S");

            $this->log_data('batch', $log_name, "Pedido {$this->orderId} atualizado com sucesso!\n\n".json_encode($this->getDataOrderLog()));
            echo "Pedido {$this->orderId} atualizado com sucesso para o status {$statusName}!\n";
        }

        return true;
    }

    /**
     * Define o código do pedido no Seller Center
     *
     * @param $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Define o código do pedido no ERP
     *
     * @param $idVtex
     */
    public function setVtexId($idVtex)
    {
        $this->idVtex = $idVtex;
    }

    /**
     * Define o código da fila (coluna id na tabela integration)
     *
     * @param $idIntegration
     */
    public function setIntegrationId($idIntegration)
    {
        $this->idIntegration = $idIntegration;
    }

    /**
     * Define o status do pedido na fila
     *
     * @param $paidStatus
     */
    public function setPaidStatus($paidStatus)
    {
        $this->paidStatus = $paidStatus;
    }

    /**
     * Recupera dados do pedido no ERP
     *
     * @return array
     * @throws Exception
     */
    public function getOrderERP(): array
    {
        $order = (array)$this->sendREST("api/oms/pvt/orders/{$this->idVtex}");
        $this->setDataOrderLog($order);
        return $order;
    }

    /**
     * Recupera e salva NFe do pedido
     *
     * @return bool
     * @throws Exception
     */
    public function getAndSaveNFeERP(): bool
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;

        // consulta nfe Vtex
        echo "Aguardar pedido ser faturado na Vtex\n";

        // Pedido já tem uma NF-e, atualizar o status
        $orderWithNfe = $this->getOrderWithNfe($this->orderId);
        if ($orderWithNfe) {
            $msgError = "Pedido já tem uma NF-e. Será atualizado apenas seu status para 52. PEDIDO_CONECTA=$this->orderId para atualizar.";
            echo "$msgError\n";
            // passar pedido para status 52
            $this->updateStatusForOrder($this->orderId, 52, 3);
            $this->removeOrderIntegration($this->idIntegration);
            return false;
        }

        // Obter dados do pedido
        try {
            $dataOrder = $this->getOrderERP();
            $contentOrder = json_decode($dataOrder['content']);
        } catch (Exception | Error $exception) {
            $msgError = "Não foi possível localizar o pedido $this->orderId! RETORNO={$exception->getMessage()}";
            echo "$msgError\n";
            return false;
        }

        if ($dataOrder['httpcode'] != 200) {
            $msgError = "Não foi possível localizar o pedido $this->orderId! RETORNO=" . json_encode($dataOrder);
            echo "$msgError\n";
            $this->log_data('batch', $log_name, $msgError, "E");
            $this->log_integration("Erro para atualizar o pedido $this->orderId", "<h4>Não foi possível localizar dados do pedido $this->orderId</h4>", "E");
            return false;
        }

        $contentOrder = $contentOrder->packageAttachment->packages;

        foreach ($contentOrder as $invoice) {
            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (!isset($invoice->invoiceNumber)) {
                continue;
            }

            if (
                empty($invoice->invoiceKey) ||
                empty($invoice->invoiceNumber) ||
                empty($invoice->issuanceDate)
            ) {
                $msgError = "Erro para atualizar o pedido ($this->orderId). Os dados de nota fiscal estão incompletos, reveja: Chave, Número, Série e Data de emissão.";
                echo "$msgError\n";
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido ($this->orderId)", 'Os dados de nota fiscal estão incompletos, reveja: Chave, Número, Série e Data de emissão.', "E");
                return false;
            }

            // Dados para inserir a NF-e
            $arrNfe = array(
                'order_id'      => $this->orderId,
                'company_id'    => $this->company,
                'store_id'      => $this->store,
                'date_emission' => dateFormat($invoice->issuanceDate, DATETIME_BRAZIL),
                'nfe_value'     => moneyVtexToFloat($invoice->invoiceValue),
                'nfe_serie'     => substr(clearBlanks($invoice->invoiceKey), 22, 3),
                'nfe_num'       => (int)clearBlanks($invoice->invoiceNumber),
                'chave'         => clearBlanks($invoice->invoiceKey)
            );

            // Inserir NF-e
            $insertNfe = $this->createNfe($arrNfe);

            // Erro para iserir a NF-e
            if (!$insertNfe) {
                $msgError = "Não foi possível inserir dados de faturamento do pedido $this->orderId! DATA_NFE_INSERT=" . json_encode($arrNfe) . ", DATA_NFE_VTEX=" . json_encode($contentOrder);
                echo "$msgError\n";
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido $this->orderId", "<h4>Não foi possível atualizar dados de faturamento do pedido $this->orderId</h4>", "E");
                return false;
            }

            // Remover pedido da fila
            $this->removeOrderIntegration($this->idIntegration);

            $this->log_integration("Pedido $this->orderId atualizado",
                "<h4>Foi atualizado dados de faturamento do pedido $this->orderId</h4> 
                          <ul>
                            <li><strong>Chave:</strong> {$arrNfe['chave']}</li>
                            <li><strong>Número:</strong> {$arrNfe['nfe_num']}</li>
                            <li><strong>Série:</strong> {$arrNfe['nfe_serie']}</li>
                            <li><strong>Data de Emissão:</strong> {$arrNfe['date_emission']}</li>
                            <li><strong>Valor:</strong> {$arrNfe['nfe_value']}</li>
                          </ul>", "S");

            echo "Pedido $this->orderId atualizado com sucesso!\n";
            return true;
        }

        echo "Pedido ainda não faturado\n";
        return false;
    }

    /**
     * Envia pedido cancelado para o ERP
     *
     * @return bool
     * @throws Exception
     */
    public function getAndSendCancelERP(): bool
    {
        $log_name               = __CLASS__.'/'.__FUNCTION__;
        $sellercenter           = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $itens                  = $this->model_orders->getOrdersItemData($this->orderId);
        $nfes                   = $this->model_orders->getOrdersNfes($this->orderId);
        $dataOrderIntegartion   = $this->model_orders->getOrdersData(0, $this->orderId);

        if (!$sellercenter) $sellercenter = 'conectala';

        echo "Cancelando pedido ={$this->orderId}\n";

        $arrItems = array();
        foreach($itens as $item) { // Tempo de crossdocking

            $skuERP = $this->getSkuERP($item);

            if ($skuERP === null) {
                $this->log_integration("Erro para atualizar o pedido {$this->orderId}", 'Não possível localizar um dos SKUs do pedido.', "E");
                return false;
            }

            array_push($arrItems, array(
                "id"        => $skuERP,
                "quantity"  => (int)$item['qty'],
                "price"     => (int)str_replace('.', '', number_format($item['rate'], 2, '.', ''))
            ));
        }

        // Existe NFe, não pode cancelar, deve enviar uma nota de devolução
        if (count($nfes) != 0) {

            echo "Pedido {$this->orderId} tem NFe, não deverá cancelar, precisa enviar nfe de estorno\n";

            $nfe = $nfes[0];

            $issuanceDate = DateTime::createFromFormat('d/m/Y H:i:s', $nfe['date_emission']);
            $invoiced = array(
                "type"              => "Input",
                "invoiceNumber"     => (int)$nfe['nfe_num'] + 1,
                "courier"           => "",
                "trackingNumber"    => "",
                "trackingUrl"       => "",
                "items"             => $arrItems,
                "issuanceDate"      => $issuanceDate->format('Y-m-d H:i:s'),
                "invoiceValue"      => (int)str_replace('.', '', number_format($sellercenter == 'conectala' ? $dataOrderIntegartion['gross_amount'] : $dataOrderIntegartion['net_amount'], 2, '.', ''))
            );

            $url = "api/oms/pvt/orders/{$this->idVtex}/invoice";
            $resp = $this->sendREST($url, $invoiced, 'POST');

            if ($resp['httpcode'] != 200) {
                echo "Erro na respota httpcode=" . $resp['httpcode'] . " RESPOSTA CAR: " . print_r($resp['content'], true) . " \n";
                echo "http:" . $url . "\n";
                echo "Dados enviados=" . print_r($invoiced, true) . "\n";
                $this->log_data('batch', $log_name, 'ERRO no cancelamento da NFE pedido ' . $this->orderId . ' ' . $this->idVtex . ' http:' . $url . ' - httpcode: ' . $resp['httpcode'] . ' RESPOSTA: ' . print_r($resp['content'], true) . ' DADOS ENVIADOS:' . print_r($invoiced, true), "E");
                return false;
            }
            echo "Estornou da Nfe do pedido {$this->orderId}\n";
            $this->log_data('batch',$log_name, "Pedido {$this->orderId} cancelado(estonou NFe) \n\nURL={$url} \nENVIADO=".json_encode($invoiced)." \nRESPOSTA=".json_encode($resp));

            echo "Pedido {$this->orderId} cancelado por aqui, pois não é feito requisição de cancelamento.\n";
        } else {
            // É preciso enviar duas requisições para cancelar o pedido na vtex
            // A primeira requisição o pedido vai para "Aguardando decisão do seller"
            // Na segunda requisição fica como "Cancelado".
            $error = false;
            for ($cancelCount = 1; $cancelCount <= 2; $cancelCount++) {

                // esperar 15s para enviar a proxima requisição.
                // Para não enviar uma requisição antes da VTEX vir aqui.
                if ($cancelCount == 2) {
                    echo "Esperar 15s para a próxima requisição\n";
                    sleep(15);
                }

                $url = "api/oms/pvt/orders/{$this->idVtex}/cancel";
                $resp = $this->sendREST($url, array(),'POST');

                if ($resp['httpcode'] != 200) {
                    echo "Erro na respota do httpcode={$resp['httpcode']} RESPOSTA : " . print_r($resp['content'], true) . " \n";
                    echo "http:" . $url . "\n";
                    $this->log_data('batch', $log_name, "ERRO para cancelar o pedido {$this->orderId} - {$this->idVtex} no http:{$url} - httpcode: {$resp['httpcode']} RESPOSTA : " . print_r($resp['content'], true), "E");
                    $error = true;
                }

                if ($error) return false;

                echo "Cancelou o pedido {$this->orderId}. {$cancelCount}ªvez\n";
                $this->log_data('batch', $log_name, "Pedido {$this->orderId} cancelado(status de cancelado) {$cancelCount}ªvez \n\nURL={$url} \nRESPOSTA=" . json_encode($resp), "I");
            }
        }

        // adicionar log
        $motivos    = $this->model_orders->getPedidosCanceladosByOrderId($this->orderId);
        $motivo     = $motivos['motivo_cancelamento'] ?? 'Falta de produto';
        $dataCancel = array("source" => "Cancelamento", "message" => $motivo);
        $url        = "api/oms/pvt/orders/{$this->idVtex}/interactions";

        $this->sendREST($url, $dataCancel, 'POST');

        return true;
    }

    /**
     * Recupera ou envia dados de tracking
     *
     * @return bool
     * @throws Exception
     */
    public function getSaveAndSendTracking(): bool
    {
        $log_name             = __CLASS__.'/'.__FUNCTION__;
        $frete                = $this->model_freights->getFreightsDataByOrderId($this->orderId, 'id');
        $itens                = $this->model_orders->getOrdersItemData($this->orderId);
        $dataOrderIntegartion = $this->model_orders->getOrdersData(0, $this->orderId);
        $nfes                 = $this->model_orders->getOrdersNfes($this->orderId);

        if ($this->getStoreOwnLogistic()) {

            if ($this->paidStatus != 40) {
                $msgError = "Chegou status={$this->paidStatus}, não deve ser integrado, apenas remover da fila!";
                echo "{$msgError}\n";
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }

            if (count($frete) != 0) {
                $msgError = "Pedido já tem uma rastreio. Será atualizado apenas seu status para 51. PEDIDO_CONECTA={$this->orderId} para atualizar!";
                echo "{$msgError}\n";
                // passar pedido para status 52
                $this->updateStatusForOrder($this->orderId, 51, 40);
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }

            // Obter dados do pedido
            $dataOrder = $this->getOrderERP();
            $contentOrder = json_decode($dataOrder['content']);

            if ($dataOrder['httpcode'] != 200) {
                $msgError = "Não foi possível localizar o pedido {$this->orderId}! RETORNO=" . json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido {$this->orderId}", "<h4>Não foi possível localizar dados do pedido {$this->orderId}</h4>", "E");
                return false;
            }

            $contentOrder = $contentOrder->packageAttachment->packages;

            // Dados de tracking
            $tracking = $contentOrder[0] ?? array();

            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (!count($contentOrder) || !isset($tracking->trackingNumber) || empty($tracking->trackingNumber)) {
                echo "Pedido ainda não existe rastreio\n";
                return false;
            }

            $correios = false;
            if ($this->likeText("%correios%", strtolower($tracking->courier)))
                $correios = true;

            $itenInUse = array();

            foreach ($itens as $iten) {

                if (in_array($iten['product_id'], $itenInUse)) continue;
                array_push($itenInUse, $iten['product_id']);

                $arrFreight = array(
                    'order_id'              => $this->orderId,
                    'item_id'               => $iten['product_id'],
                    'company_id'            => $this->company,
                    'ship_company'          => $tracking->courier,
                    'status_ship'           => 0,
                    'date_delivered'        => '',
                    'ship_value'            => $dataOrderIntegartion['total_ship'],
                    'prazoprevisto'         => '',
                    'idservico'             => 0,
                    'codigo_rastreio'       => $tracking->trackingNumber,
                    'link_etiqueta_a4'      => null,
                    'link_etiqueta_termica' => null,
                    'link_etiquetas_zpl'    => null,
                    'link_plp'              => null,
                    'data_etiqueta'         => date('Y-m-d H:i:s'),
                    'CNPJ'                  => null,
                    'method'                => 'VTEX',
                    'solicitou_plp'         => 0,
                    'sgp'                   => 0,
                    'url_tracking'          => $tracking->trackingUrl
                );

                // Inserir tracking
                $insertTracking = $this->model_freights->create($arrFreight);
            }

            // Erro para iserir a tracking
            if (!$insertTracking) {
                $msgError = "Não foi possível inserir dados de faturamento do pedido {$this->orderId}! DATA_TRACKING_INSERT=" . json_encode($arrFreight) . ", DATA_TRACKING_VTEX=" . json_encode($contentOrder) . " RETORNO=" . json_encode($insertTracking);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido {$this->orderId}", "<h4>Não foi possível atualizar dados de rastreamento do pedido {$this->orderId}</h4>", "E");
                return false;
            }

            $this->updateStatusForOrder($this->orderId, 51, 40);
            echo "Pedido {$this->orderId} recebeu informação de rastreamento. BODY=".json_encode($tracking)."\n";
        }
        else {

            if ($this->paidStatus != 53) {
                $msgError = "Chegou status={$this->paidStatus}, não deve ser integrado, apenas remover da fila!";
                echo "{$msgError}\n";
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }

            if (count($frete) == 0) {
                echo "Sem frete/rastreio \n";
                // Não tem frete, não deveria aconter
                $this->log_data('batch', $log_name, "ERRO: Sem frete para a ordem {$this->orderId}", "E");
                $this->log_integration("Pedido {$this->orderId} não será atualizado", "<h4>Status do pedido não será atualizado</h4> <ul><li>O status do pedido {$this->orderId}, não foi atualizado porque não foi encontrado rastreio no pedido.</li></ul>", "E");
                return false;
            }
            $frete = $frete[0];

            if (count($nfes) == 0) {
                echo "ERRO: pedido {$this->orderId} não tem nota fiscal\n";
                $this->log_data('batch', $log_name, "ERRO: pedido {$this->orderId} não tem nota fiscal", "E");
                // ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50
                return false;
            }
            $nfe = $nfes[0];

            $carrier_url = null;
            $transportadora = $this->model_providers->getProviderDataForCnpj($frete['CNPJ']);
            if ($transportadora && !is_null($transportadora['tracking_web_site']))
                $carrier_url = $transportadora['tracking_web_site'];

            if (!empty($frete['url_tracking']))
                $carrier_url = $frete['url_tracking'];
            else if (empty($carrier_url))
                $carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/';

            $tracking = array(
                'courier' => $frete['ship_company'],
                'trackingUrl' => $carrier_url,
                'trackingNumber' => $frete['codigo_rastreio'],
                "dispatchedDate" => null
            );

            $url = "api/oms/pvt/orders/{$this->idVtex}/invoice/{$nfe['nfe_num']}";
            $resp = $this->sendREST($url, $tracking, 'PATCH');

            if ($resp['httpcode'] != 200) {
                echo "Erro na respota do httpcode={$resp['httpcode']} RESPOSTA: " . print_r($resp['content'], true) . " \n";
                echo "http:" . $url . "\n";
                echo "Dados enviados=" . print_r($tracking, true) . "\n";
                $this->log_data('batch', $log_name, 'ERRO na gravação da tracking pedido ' . $this->orderId . ' ' . $this->idVtex . ' no http:' . $url . ' - httpcode: ' . $resp['httpcode'] . ' RESPOSTA: ' . print_r($resp['content'], true) . ' DADOS ENVIADOS:' . print_r($tracking, true), "E");
                return false;
            }

            $this->log_data('batch', $log_name, "Enviou Tracking \nPEDIDO={$this->orderId}\nENVIADO=" . json_encode($tracking) . "\nRETORNO=" . json_encode($resp));

            echo "Pedido {$this->orderId} enviou informação de rastreamento. BODY=".json_encode($tracking)."\n";
        }
        return true;
    }

    /**
     * Recupera ou envia dados do pedido enviado
     *
     * @return bool
     */
    public function getAndSendOrderShipped(): bool
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;

        if ($this->getStoreOwnLogistic()) {

            if ($this->paidStatus != 43) {
                $msgError = "Chegou status={$this->paidStatus}, não deve ser integrado, apenas remover da fila!";
                echo "{$msgError}\n";
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }

            // Obter dados do pedido
            $dataOrder = $this->getOrderERP();
            $contentOrder = json_decode($dataOrder['content']);

            if ($dataOrder['httpcode'] != 200) {
                $msgError = "Não foi possível localizar o pedido {$this->orderId}! RETORNO=" . json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido {$this->orderId}", "<h4>Não foi possível localizar dados do pedido {$this->orderId}</h4>", "E");
                return false;
            }

            $contentOrder = $contentOrder->packageAttachment->packages;

            // Dados de tracking
            $tracking = $contentOrder[0] ?? array();

            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (!count($contentOrder) || !isset($tracking->trackingNumber) || empty($tracking->trackingNumber)) {
                echo "Pedido ainda não existe rastreio e não poderá ser movido para despachado\n";
                return false;
            }

            $dateShipped = date('Y-m-d H:i:s');

            // atualiza pedido com a data do envio
            $this->model_orders->updateByOrigin($this->orderId, array('data_envio' => $dateShipped));
            $this->updateStatusForOrder($this->orderId, 55, 43);
            echo "Pedido {$this->orderId} recebeu informação de pedido coletado/despachado\n";
        } else
            echo "Pedido {$this->orderId} não usa logística própria não precisa enviar como coletado/despachado\n";

        return true;
    }

    /**
     * Recupea ou salva ocorrência de frete
     * Caso seja logistica propria, também mudará o status do pedido para entregue quando for entregue
     *
     * @return bool
     * @throws Exception
     */
    public function getSaveAndSendOccurrence(): bool
    {
        $log_name               = __CLASS__.'/'.__FUNCTION__;
        $frete                  = $this->model_freights->getFreightsDataByOrderId($this->orderId, 'id');
        $dataOrderIntegartion   = $this->model_orders->getOrdersData(0, $this->orderId);
        $nfes                   = $this->model_orders->getOrdersNfes($this->orderId);

        if ($this->getStoreOwnLogistic()) {

            if ($this->paidStatus != 45) {
                $msgError = "Chegou status={$this->paidStatus}, não deve ser integrado, apenas remover da fila!";
                echo "{$msgError}\n";
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }

            // Obter dados do pedido
            $dataOrder = $this->getOrderERP();
            $contentOrder = json_decode($dataOrder['content']);

            if ($dataOrder['httpcode'] != 200) {
                $msgError = "Não foi possível localizar o pedido {$this->orderId}!".", RETORNO=" . json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido {$this->orderId}", "<h4>Não foi possível localizar dados do pedido {$this->orderId}</h4>", "E");
                return false;
            }

            $contentOrder = $contentOrder->packageAttachment->packages;

            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (!count($contentOrder) || (isset($nfe->trackingNumber))) {
                echo "Pedido ainda não existe rastreio\n";
                return false;
            }

            // Dados da ocorrência
            $ocorrencias = $contentOrder[0]->courierStatus->data ?? array();
            $frete = $frete[0];

            if (count($ocorrencias)) {
                $ocorrencias = array_reverse($ocorrencias);

                foreach ($ocorrencias as $ocorrencia) {

                    $existOcorrencia = $this->model_frete_ocorrencias->getOcorrenciasByFreightIdName($frete['id'], $ocorrencia->description);
                    if (!isset($existOcorrencia)) {

                        $this->model_freights->replace($frete);

                        $dateOcorrencia = $ocorrencia->lastChange ?? $ocorrencia->createDate;

                        $frete_ocorrencia = array(
                            'freights_id' => $frete['id'],
                            'codigo' => '',
                            'tipo' => '',
                            'nome' => $ocorrencia->description,
                            'data_ocorrencia' => $dateOcorrencia,
                            'data_atualizacao' => $dateOcorrencia,
                            'data_reentrega' => NULL,
                            'prazo_devolucao' => NULL,
                            'mensagem' => '',
                            'avisado_marketplace' => 0,
                            'addr_place' => null,
                            'addr_name' => null,
                            'addr_num' => null,
                            'addr_cep' => null,
                            'addr_neigh' => null,
                            'addr_city' => $ocorrencia->city,
                            'addr_state' => $ocorrencia->state
                        );

                        $this->model_frete_ocorrencias->create($frete_ocorrencia);
                    }
                }
            }

            if (!isset($contentOrder[0]->courierStatus->finished) || !$contentOrder[0]->courierStatus->finished) return false;
            else {
                $dateDelivered = $contentOrder->packageAttachment->packages[0]->courierStatus->deliveredDate ?? date('Y-m-d H:i:s');

                $this->model_freights->updateFreightsOrderId($this->orderId, array('date_delivered' => $dateDelivered));
                $this->model_orders->updateByOrigin($this->orderId, array('data_entrega' => $dateDelivered));
                $this->updateStatusForOrder($this->orderId, 60, 45);
            }
        }
        else {

            if ($this->paidStatus != 5) {
                $msgError = "Chegou status={$this->paidStatus}, não deve ser integrado, apenas remover da fila!";
                echo "{$msgError}\n";
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }
            if ($dataOrderIntegartion['paid_status'] == 6 || $dataOrderIntegartion['paid_status'] == 60) {
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }

            $ocorrencias = $this->model_frete_ocorrencias->getOcorrenciaByOrderId($this->orderId, false, true);

            // não encontrou ocorrências para enviar
            if (!count($ocorrencias)) {
                echo "Não tem novas ocorrencias para enviar\n";
                return false;
            }

            if (count($nfes) == 0) {
                echo 'ERRO: pedido '.$this->orderId.' não tem nota fiscal'."\n";
                $this->log_data('batch',$log_name, 'ERRO: pedido '.$this->orderId.' não tem nota fiscal para enviar as ocorrencias',"E");
                return false;
            }
            $nfe = $nfes[0];

            $arrOcorrencia = array();
            $arrIdOcorrencias = array();
            foreach ($ocorrencias as $ocorrencia) {
                array_push($arrOcorrencia,
                    array(
                        "city"          => $ocorrencia['city'] ?? '',
                        "state"         => $ocorrencia['state'] ?? '',
                        "description"   => $ocorrencia['nome'],
                        "date"          => $ocorrencia['data_ocorrencia']
                    )
                );

                array_push($arrIdOcorrencias, $ocorrencia['id']);
            }

            $dataFreight = Array (
                "isDelivered"   => false,
                "events"        => $arrOcorrencia
            );

            $url = "api/oms/pvt/orders/{$this->idVtex}/invoice/{$nfe['nfe_num']}/tracking";
            $resp = $this->sendREST($url, $dataFreight, 'PUT');

            if ($resp['httpcode'] != 200) {
                echo "Erro na respota - httpcode={$resp['httpcode']} RESPOSTA: ".print_r($resp['content'],true)." \n";
                echo "http:".$url."\n";
                $this->log_data('batch',$log_name, 'ERRO para enviar ocorrencias do pedido '.$this->orderId.' '.$this->idVtex.' no http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA: '.print_r($resp['content'],true),"E");
                return false;
            }

            $this->log_data('batch',$log_name,"Enviou Ocorrencia \nPEDIDO={$this->orderId}\nENVIADO=".json_encode($dataFreight)."\nRETORNO=".json_encode($resp));

            foreach ($arrIdOcorrencias as $idOcorrencia)
                $this->model_frete_ocorrencias->updateFreightsOcorrenciaAvisoErp($idOcorrencia, 1);

            if ($dataOrderIntegartion['paid_status'] == 5) return false;
        }

        return true;
    }

    /**
     * Quando for logistica do seller center envia para o ERP que o pedido foi entregue
     *
     * @return bool
     * @throws Exception
     */
    public function getSendDelivered(): bool
    {

        $log_name               = __CLASS__.'/'.__FUNCTION__;
        $nfes                   = $this->model_orders->getOrdersNfes($this->orderId);

        if (!$this->getStoreOwnLogistic()) {

            if ($this->paidStatus != 6) {
                $msgError = "Chegou status={$this->paidStatus}, não deve ser integrado, apenas remover da fila!";
                echo "{$msgError}\n";
                $this->removeOrderIntegration($this->idIntegration);
                return false;
            }

            $ocorrencias = $this->model_frete_ocorrencias->getOcorrenciaByOrderId($this->orderId, true);

            if (!count($ocorrencias))
                array_push($ocorrencias, ['nome' => 'Objeto entregue ao destinatário', 'data_ocorrencia' => date('Y-m-d')]);

            $ocorrencias = end($ocorrencias);

            $dataFreight = Array (
                "isDelivered" => true,
                "events" => array(
                    array(
                        "city"          => $ocorrencias['city'] ?? '',
                        "state"         => $ocorrencias['state'] ?? '',
                        "description"   => $ocorrencias['nome'],
                        "date"          => $ocorrencias['data_ocorrencia']
                    )
                )
            );

            $nfe = $nfes[0];

            $url = "api/oms/pvt/orders/{$this->idVtex}/invoice/{$nfe['nfe_num']}/tracking";
            $resp = $this->sendREST($url, $dataFreight, 'PUT');

            if ($resp['httpcode'] != 200) {
                echo "Erro na respota - httpcode={$resp['httpcode']} RESPOSTA: ".print_r($resp['content'],true)." \n";
                echo "http:".$url."\n";
                $this->log_data('batch',$log_name, 'ERRO para enviar ocorrencias do pedido '.$this->orderId.' '.$this->idVtex.' no http:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA: '.print_r($resp['content'],true),"E");
                return false;
            }

            $this->log_data('batch',$log_name,"Enviou Ocorrencia \nPEDIDO={$this->orderId}\nENVIADO=".json_encode($dataFreight)."\nRETORNO=".json_encode($resp));

            // atualiza ocorrencias para ja enviado ao marketplace
            $this->model_frete_ocorrencias->updateFreightsOcorrenciaAvisoErp($ocorrencias['id'], 1);
        }

        return true;
    }

    /**
     * Recupera se o seller usa logística própria
     *
     * @return bool
     */
    public function getStoreOwnLogistic(): bool
    {
        $store = $this->model_stores->getStoresData($this->store);

        $logistic = $this->calculofrete->getLogisticStore(array(
            'freight_seller' 		=> $store['freight_seller'],
            'freight_seller_type'   => $store['freight_seller_type'],
            'store_id'				=> $store['id']
        ));

        return $logistic['seller'];
    }

    /**
     * Recupera os pedidos para integração
     *
     * @return array Retorno os pedidos na fila para integrar
     */
    public function getOrdersForUpdate(): array
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
     * Recupera dados de um pedido
     *
     * @param   int         $paidStatus Código do status do pedido
     * @return  null|array              Retorna a situação para integrar
     */
    public function getStatusIntegration($paidStatus): ?array
    {
        switch ($paidStatus) {
            case 3: // programa deve aguardar o lojista faturar o pedido
                $status = array(
                    'code' => 'invoice',
                    'name' => 'Faturado'
                );
                break;
            case 95:
            case 97:
                $status = array(
                    'code' => 'cancel',
                    'name' => 'Cancelado'
                );
                break;
            case 53:
            case 40:
                $status = array(
                    'code' => 'tracking',
                    'name' => 'Envio de Rastreamento'
                );
                break;
            case 43:
                $status = array(
                    'code' => 'shipped',
                    'name' => 'Enviado ao cliente'
                );
                break;
            case 5:
            case 45:
                $status = array(
                    'code' => 'in_transit',
                    'name' => 'Ocorrência atualizada'
                );
                break;
            case 6:
                $status = array(
                    'code' => 'delivered',
                    'name' => 'Entregue ao cliente'
                );
                break;
            default:
                $status = null;
                break;
        }

        return $status;
    }

    /**
     * Recupera código do pedido na Vtex
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna código do pedido na Vtex
     */
    public function getOrderIdVtex($orderId)
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
    public function removeOrderIntegration(int $id): bool
    {
        return (bool)$this->db->delete(
            'orders_to_integration',
            array(
                'store_id' => $this->store,
                'id' => $id,
                'new_order' => 0
            )
        );
    }

    /**
     * Remove todos os pedidos da fila de integração
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retornar o status da exclusão
     */
    public function removeAllOrderIntegration(int $orderId): bool
    {
        return (bool)$this->db->delete(
            'orders_to_integration',
            array(
                'store_id' => $this->store,
                'order_id' => $orderId
            )
        );
    }

    /**
     * Cria dados de faturamento do pedido e atualiza o status do pedido para 52
     *
     * @param   array   $data   Dados da nfe para inserir
     * @return  bool            Retorna o status da criação
     */
    public function createNfe(array $data): bool
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
    public function getOrderCancel(int $orderId): bool
    {
        $orderCancel = $this->db
            ->from('orders_to_integration')
            ->where(
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store
                )
            )->where_in('paid_status', array(95, 97))
            ->get()->row_array();

        if (!$orderCancel) return false;

        return true;
    }

    /**
     * Recupera se o pedido já foi integrado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido já foi integrado
     */
    public function getIntegratedOrder(int $orderId): bool
    {
        $orderCreate = $this->db
            ->get_where('orders_to_integration',
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store,
                    'new_order'     => 1
                )
            )->row_array();

        return !$orderCreate;
    }

    /**
     * Recupera se o pedido já tem uma NF-e
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido tem NF-e
     */
    public function getOrderWithNfe(int $orderId): bool
    {
        return !($this->db
                ->get_where('nfes',
                    array(
                        'order_id' => $orderId
                    )
                )->num_rows() == 0);

    }

    /**
     * Atualiza status de um pedido
     *
     * @param   int      $orderId       Código do pedido
     * @param   int      $status        Código do status
     * @param   int|null $verifyStatus  Código do status para verificação
     * @return  bool                    Retorna o status da atualização
     */
    public function updateStatusForOrder(int $orderId, int $status, int $verifyStatus = null): bool
    {
        $where = array(
            'id'        => $orderId,
            'store_id'  => $this->store,
        );
        if ($verifyStatus) $where['paid_status'] = $verifyStatus;

        return (bool)$this->db->where($where)->update('orders', array('paid_status' => $status));
    }

    /**
     * Recupera o SKU do produto/variação vendido
     *
     * @param   array       $iten   Array com dados do produto vendido
     * @return  null|string         Retorna o sku do produto ou variação, em caso de erro retorna false
     */
    public function getSkuERP(array $iten): ?string
    {
        if ($iten['variant'] == "") return $iten['sku'];

        $var = $this->db
            ->get_where('prd_variants',
                array(
                    'prd_id'    => $iten['product_id'],
                    'variant'   => $iten['variant']
                )
            )->row_array();

        if (!$var) return null;

        return $var['variant_id_erp'];

    }

    /**
     * Consulta string em uma parte de outra string
     *
     * @param   string  $needle     Valor a ser procurado
     * @param   string  $haystack   Valor real para comparação
     * @return  bool                Retorna o status da consulta
     */
    public function likeText(string $needle, string $haystack): bool
    {
        $regex = '/' . str_replace('%', '.*?', $needle) . '/';

        return preg_match($regex, $haystack) > 0;
    }

    /**
     * Define os dados do pedido para gravar logs
     *
     * @param array $order
     */
    private function setDataOrderLog(array $order)
    {
        $this->dataOrderLog = $order;
    }

    /**
     * Retorna os dados do pedido para gravar logs se fez a consulta
     *
     * @return array|null
     */
    private function getDataOrderLog(): ?array
    {
        return $this->dataOrderLog;
    }
}