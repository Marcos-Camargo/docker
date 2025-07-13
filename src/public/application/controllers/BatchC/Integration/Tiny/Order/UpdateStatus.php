<?php

/**
 * Class UpdateStatus
 *
 * php index.php BatchC/Tiny/Order/UpdateStatus run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";

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
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setJob('UpdateStatus');
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

        foreach ($orders as $orderIntegration) {

            $idIntegration  = $orderIntegration['id'];
            $orderId        = $orderIntegration['order_id'];
            $paidStatus     = $orderIntegration['paid_status'];
            $removeList     = true;
            $this->setUniqueId($orderId); // define novo unique_id

            // Código do pedido integrado na Tiny
            $idTiny = $this->getOrderIdTiny($orderId);

            // Não encontrou o código do pedido integrado
            if (!$idTiny) {
                $msgError = "Não foi possível localizar o código do pedido integrador. PEDIDO_CONECTA={$orderId} para atualizar! ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "E");
                //$this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar o pedido {$orderId}</h4> <ul><li>Não foi possível localizar o pedido para integrar.</li></ul>", "E");
                continue;
            }

            // Pedido ainda não foi integrado
            $integratedOrder = $this->getIntegratedOrder($orderId);
            if (!$integratedOrder) {
                $msgError = "Pedido ainda não integrado. PEDIDO_CONECTA={$orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "W");
                continue;
            }

            // Pedido para cancelar
            $orderCancel = $this->getOrderCancel($orderId);
            if ($orderCancel) {
                //cancelar na tiny
                $msgError = "Pedido deve ser cancelado. PEDIDO_CONECTA={$orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "I");
                $paidStatus = 97;
            }

            $status = $this->getStatusIntegration($paidStatus);

            // Ignorar status, não deve ser alterado na Tiny
            if ($status === null) {
                $msgError = "Chegou status={$paidStatus}, não deve ser integrado, apenas remover da fila! ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "E");
                $this->removeOrderIntegration($idIntegration);
                continue;
            }

            // Aguardar pedido ser faturado na Tiny
            if ($status === false) {
                // consulta nfe tiny
                echo "Aguardar pedido ser faturado na Tiny\n";

                // Pedido já tem uma NF-e, atualizar o status
                $orderWithNfe = $this->getOrderWithNfe($orderId);
                if ($orderWithNfe) {
                    $msgError = "Pedido já tem uma NF-e. Será atualizado apenas seu status para 52. PEDIDO_CONECTA={$orderId} para atualizar! ORDER_INTEGRATION=".json_encode($orderIntegration);
                    echo "{$msgError}\n";
                    //$this->log_data('batch', $log_name, $msgError, "W");
                    // passar pedido para status 50
                    // $this->updateStatusForOrder($orderId, 50, 3); // FLUXO ANTIGO
					$this->updateStatusForOrder($orderId, 52, 3);
                    $this->removeOrderIntegration($idIntegration);
                    continue;
                }

                // Obter dados do pedido
                $url        = 'https://api.tiny.com.br/api2/pedido.obter.php';
                $data       = "&id={$idTiny}";
                $dataOrder = json_decode($this->sendREST($url, $data));

                if ($dataOrder->retorno->status != "OK") {
                    $msgError = "Não foi possível localizar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar ou atualizar os dados do pedido {$orderId}</h4> Retorno Tiny=" . json_encode($dataOrder), "E");
                    continue;
                }

                // Verifica situação do pedido, para atualizar situação para aprovado, antes de criar a nfe
                if ($dataOrder->retorno->pedido->situacao == "Em aberto") {
                    $status = "aprovado";
                    $removeList = false;
                } else {

                    // Recupera código da nfe na Tiny
                    $idNfeOrder = $dataOrder->retorno->pedido->id_nota_fiscal;

                    // Se for zero, não existe nfe ainda
                    if ($idIntegration == 0) {
                        echo "Pedido ainda não faturado\n";
                        continue;
                    }

                    // Obter dados NF-e
                    $url = 'https://api.tiny.com.br/api2/nota.fiscal.obter.php';
                    $data = "&id={$idNfeOrder}";
                    $dataNfe = json_decode($this->sendREST($url, $data));

                    if ($dataNfe->retorno->status != "OK") {
                        if ($dataNfe->retorno->codigo_erro == 32) {
                            // Ignora e continua, aguardando nfe ser gerada
                            $msgError = "Pedido ainda não faturado. PEDIDO={$orderId}, RETORNO=" . json_encode($dataNfe);
                            echo "{$msgError}\n";
                            //$this->log_data('batch', $log_name, $msgError, "W");
                            continue;
                        }
                        $msgError = "Não foi possível localizar o pedido {$orderId}! ORDER_INTEGRATION=" . json_encode($orderIntegration) . ", RETORNO=" . json_encode($dataNfe);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados de faturamento do pedido {$orderId}</h4>", "E");
                        continue;
                    }

                    // Dados da NF-e
                    $nfe = $dataNfe->retorno->nota_fiscal;

                    // Se for diferente de 6,7 e 8, ainda nao foi realmente
                    // faturado, essa verificaçao existe, pois pode existir
                    // uma nfe, mas ainda não foi autorizada na sefaz
                    // 1 - Pendente
                    // 2 - Emitida
                    // 3 - Cancelada
                    // 4 - Enviada - Aguardando recibo
                    // 5 - Rejeitada
                    // 6 - Autorizada
                    // 7 - Emitida DANFE
                    // 8 - Registrada
                    // 9 - Enviada - Aguardando protocolo
                    // 10 - Denegada
                    if ($nfe->situacao != 6 && $nfe->situacao != 7) {
                        // Ignora e continua, aguardando nfe ser autorizada
                        $msgError = "Pedido ainda não faturado. NF-e criada mas não autorizada. PEDIDO={$orderId}, RETORNO=" . json_encode($nfe);
                        echo "{$msgError}\n";
                        //$this->log_data('batch', $log_name, $msgError, "W");
                        continue;
                    }

                    // Dados para inserir a NF-e
                    $arrNfe = array(
                        'order_id' => $orderId,
                        'company_id' => $this->company,
                        'store_id' => $this->store,
                        'date_emission' => "{$nfe->data_emissao} {$nfe->hora_saida}:00",
                        'nfe_value' => $nfe->valor_nota,
                        'nfe_serie' => $nfe->serie,
                        'nfe_num' => $nfe->numero,
                        'chave' => $nfe->chave_acesso
                    );

                    // Inserir NF-e
                    $insertNfe = $this->createNfe($arrNfe);

                    // Erro para iserir a NF-e
                    if (!$insertNfe) {
                        $msgError = "Não foi possível inserir dados de faturamento do pedido {$orderId}! DATA_NFE_INSERT=" . json_encode($arrNfe) . ", DATA_NFE_TINY=" . json_encode($dataNfe) . " RETORNO=" . json_encode($insertNfe);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar dados de faturamento do pedido {$orderId}</h4>", "E");
                        continue;
                    }

                    // Remover pedido da fila
                    $this->removeOrderIntegration($idIntegration);

                    // Salvar XML
                    try {
                        $this->saveXML($idNfeOrder, $orderId);
                    } catch (\Exception $e) {
                        $this->log_data('batch', $log_name, "Erro para salvar o XML: {$e->getMessage()}", "W");
                    }

                    // volta pro formato de retorno JSON
                    $this->formatReturn = 'json';

                    $this->log_integration("Pedido {$orderId} atualizado",
                        "<h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> 
                              <ul>
                                <li><strong>Chave:</strong> {$nfe->chave_acesso}</li>
                                <li><strong>Número:</strong> {$nfe->numero}</li>
                                <li><strong>Série:</strong> {$nfe->serie}</li>
                                <li><strong>Data de Emissão:</strong> {$nfe->data_emissao} {$nfe->hora_saida}:00</li>
                                <li><strong>Valor:</strong> " . number_format($nfe->valor_nota, 2, ',', '.') . "</li>
                              </ul>", "S");

                    echo "Pedido {$orderId} atualizado com sucesso!\n";
                    // ir para o próximo pedido
                    continue;
                }
            }

            $url        = 'https://api.tiny.com.br/api2/pedido.alterar.situacao';
            $data       = "&id={$idTiny}&situacao={$status}";
            $dataStatus = json_decode($this->sendREST($url, $data));

            if ($dataStatus->retorno->status != "OK") {
                $msgError = "Não foi possível integrar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dataStatus);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");

                // formatar mensagens de erro para log integration
                $arrErrors = array();
                $errors = $dataStatus->retorno->erros;
                if (!is_array($errors)) $errors = (array)$errors;
                foreach ($errors as $error) {
                    $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                    array_push($arrErrors, $msgErrorIntegration);
                }
                $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                continue;
            }

            if ($removeList) { // deve ser romivido da fila
                // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                if ($orderCancel)
                    $this->removeAllOrderIntegration($orderId);
                else
                    $this->removeOrderIntegration($idIntegration);
            }

            $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li>O status do pedido {$orderId}, foi atualizado para {$status}</li></ul>", "S");

            $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
            echo "Pedido {$orderId} atualizado com sucesso!\n";
        }
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
     * Recupera dados de um pedido
     *
     * @param   int     $paid_status    Código do status do pedido
     * @return  string                  Retorna a situação para integrar
     */
    private function getStatusIntegration($paid_status)
    {
        switch ($paid_status) {
//            case 1:
//                $status = 'aberto';
//                break;
            case 2:
                $status = 'aprovado';
                break;
            case 3: // programa deve aguardar o lojista faturar o pedido
                $status = false;
                break;
            case 53:
                $status = 'pronto_envio';
                break;
            case 55:
                $status = 'enviado';
                break;
            case 60:
                $status = 'entregue';
                break;
            case 95:
            case 97:
                $status = 'cancelado';
                break;
            default:
                $status = null;
                break;
        }

        return $status;
    }

    /**
     * Recupera código do pedido na Tiny
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna código do pedido na Tiny
     */
    private function getOrderIdTiny($orderId)
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
		
		//return $this->updateStatusForOrder($data['order_id'], 50, 3);    //FLUXO ANTIGO
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
    private function getIntegratedOrder($orderId)
    {
        $orderCreate = $this->db
            ->get_where('orders_to_integration',
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store,
                    'new_order'     => 1
                )
            )->row_array();

        return $orderCreate ? false : true;
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
     * @param   int     $idTiny     Código da NFe na Tiny
     * @param   int     $orderId    Código do pedido na Conecta Lá
     * @return  bool                Retorna o status da importação do xml
     */
    private function saveXML($idTiny, $orderId)
    {
        $this->formatReturn = 'xml';
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $url = 'https://api.tiny.com.br/api2/nota.fiscal.obter.xml.php';
        $data = "&id={$idTiny}";
        $dataXml = $this->sendREST($url, $data);

        $xmlNfe     = simplexml_load_string($dataXml);
        $jsonEncode = json_encode($xmlNfe);
        $arrayXml   = json_decode($jsonEncode,TRUE);

        if ($arrayXml['status'] != "OK") {
            $msgError = "Não foi possível localizar o xml do pedido {$orderId}! RETORNO=" . json_encode($dataXml);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "E");
            $this->log_integration("Erro para obter o XML do pedido {$orderId}", "<h4>Não foi possível obter o XML do pedido {$orderId}</h4>", "E");
            return false;
        }

        $dataXml = str_replace('<retorno><status_processamento>3</status_processamento><status>OK</status><xml_nfe>', '', $dataXml);
        $dataXml = str_replace('</xml_nfe></retorno>', '', $dataXml);

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

        $arquivo = fopen($targetDir . $arrayXml['xml_nfe']['nfeProc']['protNFe']['infProt']['chNFe'] . ".xml",'w');

        if ($arquivo == false) return false;

        fwrite($arquivo, $dataXml);

        fclose($arquivo);

        return true;
    }
}