<?php

/**
 * Class UpdateStatus
 *
 * php index.php BatchC/Integration/Bseller/Order/UpdateStatus run null 51
 *
 */
require APPPATH . "controllers/BatchC/Integration/Bseller/Main.php";

class UpdateStatus extends Main
{
    private $IN_DEV = true;
    private $ORDERS_STATUS_BSELLER = [
        'canceled' => 'CAN',
        'delivered_transport' => 'ETR',
        'invoice_issued' => 'NFS'
    ];
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
        $this->load->model('model_products');
        $this->load->model('model_freights');
        $this->load->model('model_orders');
        $this->setJob('UpdateStatus');
        $this->PAID_STATUS_INNER_ORDER = $this->model_orders->getArrayPaidStatus();
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
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando pedidos para atualizar... \n";

        // Define a loja, para recuperar os dados para integração

        $this->setDataIntegration($store);

        // Recupera os pedidos
        $this->updateOrders();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");

        $this->gravaFimJob();
        // } 
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
        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $BSELLER_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $BSELLER_URL = $credentials->url_bseller;
        }

        //=====================================================================
        //Ler e confirmar a ENTREGA massiva
        //=====================================================================
        $url = $BSELLER_URL . 'api/entregas/massivo?unidadeNegocio=8&maxRegistros=100';
        $dataLerEntregaMassiva = json_decode(json_encode($this->sendREST($url, '')));
        if ($dataLerEntregaMassiva->httpcode != 200) {
            echo "Erro para ler a entrega massiva" . "\n";
            if ($dataLerEntregaMassiva->httpcode != 99) {
                $this->log_data('batch', $log_name, "Erro para ler a entrega massiva, retorno=" . json_encode($dataLerEntregaMassiva), "W");
            }
            return false;
        }
        $entregaMassiva = json_decode($dataLerEntregaMassiva->content);
        $batchNumberEntregaMassiva = $entregaMassiva->batchNumber;
        if ($batchNumberEntregaMassiva > 0) {
            $url = $BSELLER_URL . 'api/entregas/massivo/' . $batchNumberEntregaMassiva;
            $dataProducts = json_decode(json_encode($this->sendREST($url, '', 'PUT')));

            if ($dataProducts->httpcode != 200) {
                echo "Erro ao confirmar recebimento massivo Bseller para o batchNumber = {$batchNumberEntregaMassiva}" . "\n";
                if ($dataProducts->httpcode != 99) {
                    $this->log_data('batch', $log_name, "Erro ao confirmar recebimento massivo Bseller para o batchNumber = {$dataProducts->batchNumber}, retorno=" . json_encode($dataProducts), "W");
                }
                return false;
            }
        }

        //=====================================================================
        //Ler e confirmar o TRACKING massiva do pedido
        //=====================================================================
        $url = $BSELLER_URL . 'api/pedidos/tracking/massivo?unidadeNegocio=8&maxRegistros=100';
        $dataProducts = json_decode(json_encode($this->sendREST($url, '')));

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->httpcode != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
            }
            return false;
        }

        $pedidosBseller = json_decode($dataProducts->content);
        $batchNumber = $pedidosBseller->batchNumber;

        $arrayPedidos = array();
        if (isset($pedidosBseller->content)) {
            foreach ($pedidosBseller->content as $pedidoBseller) {
                $arrayPedidos[$pedidoBseller->pedido->numeroPedido]['id'] = $pedidoBseller->pedido->numeroPedido;
                $arrayPedidos[$pedidoBseller->pedido->numeroPedido]['idEntrega'] = $pedidoBseller->idEntrega;
                $arrayPedidos[$pedidoBseller->pedido->numeroPedido]['ponto'][] = $pedidoBseller->ponto;
                $arrayPedidos[$pedidoBseller->pedido->numeroPedido]['transportadora'] = $pedidoBseller->transportadora;
                $arrayPedidos[$pedidoBseller->pedido->numeroPedido]['notaFiscal'] = $pedidoBseller->notaFiscal;
                $arrayPedidos[$pedidoBseller->pedido->numeroPedido]['codigosRastreio'] = $pedidoBseller->codigosRastreio;
            }
        }

        foreach ($arrayPedidos as $pedidoID => $pedidos) {
            foreach ($pedidos['ponto'] as $ponto) {
                // transação do banco
                $this->db->trans_begin();
                switch ($ponto->id) {
                    case $this->ORDERS_STATUS_BSELLER['canceled']: //cancelado
                        $status = $this->model_orders->updateStatusForOrder($pedidoID, $this->store, 
                        $this->PAID_STATUS_INNER_ORDER["canceled_by_seller"], null, true);
                        if ($status) {
                            $this->db->trans_commit();
                        } else {
                            $this->db->trans_rollback();
                        }
                        break;
                    case $this->ORDERS_STATUS_BSELLER['invoice_issued']: //Nota fiscal emitida
                        //------------------------------------------------------
                        //verifica se existe a chave de acesso
                        if (isset($arrayPedidos[$pedidoID]['notaFiscal']->chaveAcesso) && $arrayPedidos[$pedidoID]['notaFiscal']->chaveAcesso != null) {

                            //insere a nota fiscal
                            $arrNfe = array(
                                'order_id' => $pedidoID,
                                'company_id' => $this->company,
                                // 'store_id' => $this->store,
                                'date_emission' => $arrayPedidos[$pedidoID]['notaFiscal']->dataEmissao,
                                'nfe_serie' => $arrayPedidos[$pedidoID]['notaFiscal']->serie,
                                'nfe_num' => $arrayPedidos[$pedidoID]['notaFiscal']->id, // numero da nf de acordo com o link https://bseller.zendesk.com/hc/pt-br/articles/235593907-Response-das-APIs-de-Tracking
                                'chave' => $arrayPedidos[$pedidoID]['notaFiscal']->chaveAcesso
                            );

                            // Inserir NF-e
                            $insertNfe = $this->model_orders->createNfe($arrNfe, $this->store);

                            // Erro para iserir a NF-e
                            if (!$insertNfe) {
                                $msgError = "Não foi possível inserir dados de faturamento do pedido {$pedidoID}! DATA_NFE_INSERT=" . json_encode($arrNfe);
                                echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
                                $this->log_integration("Erro para atualizar o pedido {$pedidoID}", "<h4>Não foi possível atualizar dados de faturamento do pedido {$pedidoID}</h4>", "E");
                            }

                            // Salvar XML
                            $salvouNF = false;
                            try {
                                $salvouNF = $this->saveXML($arrayPedidos[$pedidoID]['notaFiscal']->id, $pedidoID, $arrayPedidos[$pedidoID]['notaFiscal']->chaveAcesso);
                            } catch (\Exception $e) {
                                $this->log_data('batch', $log_name, "Erro para salvar o XML: {$e->getMessage()}", "W");
                            }

                            if ($salvouNF && $insertNfe) {
                                $this->db->trans_commit();
                                // volta pro formato de retorno JSON
                                $this->formatReturn = 'json';

                                $this->log_integration(
                                    "Pedido {$pedidoID} atualizado",
                                    "<h4>Foi atualizado dados de faturamento do pedido {$pedidoID}</h4> 
                                <ul>
                                    <li><strong>Chave:</strong> {$arrayPedidos[$pedidoID]['notaFiscal']->chaveAcesso}</li>
                                    <li><strong>Número:</strong> {$arrayPedidos[$pedidoID]['notaFiscal']->id}</li>
                                    <li><strong>Série:</strong> {$arrayPedidos[$pedidoID]['notaFiscal']->serie}</li>
                                    <li><strong>Data de Emissão:</strong> {$arrayPedidos[$pedidoID]['notaFiscal']->dataEmissao}</li>
                                </ul>",
                                    "S"
                                );

                                echo "Pedido {$pedidoID}, idNfeOrder: {$arrayPedidos[$pedidoID]['notaFiscal']->id} atualizado com sucesso!\n";
                            } else {
                                $this->db->trans_rollback();
                            }
                        }
                        break;
                    case $this->ORDERS_STATUS_BSELLER['delivered_transport']: //Entregue a transportadora
                        $url = $BSELLER_URL . 'api/nf/faturamento/' . $arrayPedidos[$pedidoID]['notaFiscal']->id . '/' . $arrayPedidos[$pedidoID]['notaFiscal']->serie;
                        $dataProducts = json_decode(json_encode($this->sendREST($url, '')));
                        $dadosNF = json_decode($dataProducts->content);
                        $frete = (isset($dadosNF->detalhes[0]->valores->valorFrete)) ? $dadosNF->detalhes[0]->valores->valorFrete : null;
                        $transportadora = (isset($dadosNF->transportadora->nome)) ? $dadosNF->transportadora->nome : null;
                        $codigoTerceiro = (isset($dadosNF->detalhes[0]->codigoTerceiro)) ? $dadosNF->detalhes[0]->codigoTerceiro : null;
                        $dadosProduto = $this->model_products->getByProductIdErp($codigoTerceiro);
                        $product_id = (isset($dadosProduto['id'])) ? $dadosProduto['id'] : null;
                        $codigoRastreamento = (isset($arrayPedidos[$pedidoID]['codigosRastreio'][0])) ? $arrayPedidos[$pedidoID]['codigosRastreio'][0] : null;

                        $insertRastreamento = $this->model_freights->create(
                            array(
                                'order_id' => $pedidoID,
                                'item_id' => $product_id,
                                'company_id' => $this->company,
                                'ship_company' => $transportadora,
                                'status_ship' => 0,
                                'date_delivered' => '',
                                'ship_value' => (float) $frete,
                                'prazoprevisto' => date('Y-m-d', strtotime("+2 days")),
                                'idservico' => '',
                                'codigo_rastreio' => $codigoRastreamento,
                                'link_etiqueta_a4' => null,
                                'link_etiqueta_termica' => null,
                                'link_etiquetas_zpl' => null,
                                'link_plp' => null,
                                'data_etiqueta' => null,
                                'CNPJ' => null,
                                'method' => null,
                                'solicitou_plp' => 0,
                                'sgp' => 0, // transportadora
                                'updated_date' => date('Y-m-d H:i:s')
                            )
                        );

                        if (!$insertRastreamento) {
                            $msgError = "Não foi possível inserir dados de rastreamento do pedido {$pedidoID}!";
                            echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $this->log_integration("Erro para atualizar o pedido {$pedidoID}", "<h4>Não foi possível atualizar dados de rastreamento do pedido {$pedidoID}</h4>", "E");
                        }

                        $statusRet = 51;
                        $historicoRet = 'Pedido enviado pela transportadora';
                        $data = $ponto->data;
                        $dataStatus = 200;

                        $status = $this->model_orders->updatePaidStatus($pedidoID, $this->PAID_STATUS_INNER_ORDER['sent_trace_to_marketplace']);

                        if ($status && $insertRastreamento) {
                            $this->db->trans_commit();
                        } else {
                            $this->db->trans_rollback();
                        }
                        break;
                    default:
                        $this->db->trans_rollback();
                }
            }
        }
        // dd($this->CI_ENVIRONMENT);
        //limpa a lista
        // $this->limparFila($batchNumber, $BSELLER_URL, $log_name);
    }

    public function limparFila($batchNumber, $BSELLER_URL, $log_name)
    {
        if ($this->IN_DEV)
            return;
        if ($batchNumber > 0) {
            $url = $BSELLER_URL . 'api/pedidos/tracking/massivo/' . $batchNumber;
            $data = '';
            $dataProducts = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));

            if ($dataProducts->httpcode != 200) {
                echo "Erro ao confirmar recebimento massivo Bseller para o batchNumber = {$batchNumber}" . "\n";
                if ($dataProducts->httpcode != 99) {
                    $this->log_data('batch', $log_name, "Erro ao confirmar recebimento massivo Bseller para o batchNumber = {$batchNumber}, retorno=" . json_encode($dataProducts), "W");
                }
                return false;
            }
        }
    }

    /**
     * Salva um arquivo XML da NFe
     *
     * @param   int     $idBseller     Código da NFe na Bseller
     * @param   int     $orderId    Código do pedido na Conecta Lá
     * @return  bool                Retorna o status da importação do xml
     */
    private function saveXML($idBseller, $orderId, $chaveAcesso)
    {
        $this->formatReturn = 'xml';
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $BSELLER_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $BSELLER_URL = $credentials->url_bseller;
        }
        // $url = $BSELLER_URL.'api/pedidos/tracking/massivo?batchNumber=' .$registro->batchNumber;
        $url = $BSELLER_URL . 'api/nf/' . $chaveAcesso . '/xml';
        $data = "";
        $dataXml = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataXml->httpcode != 200) {
            $msgError = "XML da Nota Fiscal {$idBseller}, do pedido {$orderId}, não foi encontrado ou não está autorizado.";
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "E");
            $this->log_integration("Erro para obter o XML do pedido {$orderId}", "<h4>Não foi possível obter o XML do pedido  {$orderId}</h4>", "E");
            return false;
        }

        $dadosXml = $dataXml->content;
        $xmlNfe = simplexml_load_string($dadosXml);
        $jsonEncode = json_encode($xmlNfe);
        $arrayXml = json_decode($jsonEncode, TRUE);
        $chNFe = $arrayXml['infNFe']['@attributes']['Id'];

        $namePathStore = date('m-Y');

        $targetDir = 'assets/images/xml/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $this->store . '/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $namePathStore . '/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $arquivo = fopen($targetDir . $chNFe . ".xml", 'w');
        //        $arquivo = fopen($targetDir . 'nfeTeste_01'. ".xml",'w');

        if ($arquivo == false) {
            return false;
        }

        fwrite($arquivo, $dadosXml);

        fclose($arquivo);

        return true;
    }
}
