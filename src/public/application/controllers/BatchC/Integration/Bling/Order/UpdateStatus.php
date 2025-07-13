<?php

/**
 * Class UpdateStatus
 *
 * php index.php BatchC/Integration/Bling/Order/UpdateStatus run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Bling/Main.php";

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

            $idIntegration  = $orderIntegration['id'];
            $orderId        = $orderIntegration['order_id'];
            $paidStatus     = $orderIntegration['paid_status'];
            $removeList     = true;
            $this->setUniqueId($orderId); // define novo unique_id

            // Código do pedido integrado na Bling
            $idBling = $this->getOrderIdBling($orderId);

            // Não encontrou o código do pedido integrado
            if (!$idBling) {
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
                //$this->log_data('batch', $log_name, $msgError, "E");
                continue;
            }

            // Pedido para cancelar
            $orderCancel = $this->getOrderCancel($orderId);
            if ($orderCancel) {
                //cancelar na bling
                $msgError = "Pedido deve ser cancelado. PEDIDO_CONECTA={$orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "I");
                $paidStatus = 97;
            }

            // Faz um de-prara com os status
            $status = $this->getStatusIntegration($paidStatus);
            $statusId = $status['code'];
            $statusName = $status['name'];

            // Ignorar status, não deve ser alterado na Bling
            if ($status === null) {
                $msgError = "Chegou status={$paidStatus}, não deve ser integrado, apenas remover da fila! ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "E");
                $this->removeOrderIntegration($idIntegration);
                continue;
            }

            // Aguardar pedido ser faturado na Bling
            if ($status === false) {
                // consulta nfe Bling
                echo "Aguardar pedido ser faturado na Bling\n";

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
                $url        = 'https://bling.com.br/Api/v2/pedido/'.$idBling;
                $dataOrder  = $this->sendREST($url);
                $contentOrder = json_decode($dataOrder['content']);
                // $contentOrder = json_decode('{"retorno":{"pedidos":[{"pedido":{"desconto":"0,00","observacoes":"","observacaointerna":"Pedido Conecta L\u00e1: 1","data":"2020-08-25","numero":"1","numeroOrdemCompra":"","vendedor":"","valorfrete":"20.00","totalprodutos":"108.99","totalvenda":"128.99","situacao":"Em aberto","loja":"203473957","numeroPedidoLoja":"1","tipoIntegracao":"Api","cliente":{"id":"9220396246","nome":"Pedro Henrique Ambrosio","cnpj":"102.677.459-40","ie":"","rg":"3456789","endereco":"Servid\u00e3o \u00e1gua viva","numero":"44","complemento":"Casa","cidade":"Florian\u00f3polis","bairro":"Tapera","cep":"83.601-630","uf":"SC","email":"","celular":"","fone":"(48) 99667-7961"},"nota":{"serie":"1","numero":"12346","dataEmissao":"2020-08-26 10:44:52","situacao":"1","chaveAcesso":"35200936372770000102550010000016751359339730","valorNota":"147.48"},"transporte":{"transportadora":"Empresa Brasileira de Correios e Telegrafos","cnpj":"","tipo_frete":"D","qtde_volumes":"0"},"itens":[{"item":{"codigo":"001","descricao":"Produto Teste 01","quantidade":"1.0000","valorunidade":"49.9900000000","precocusto":null,"descontoItem":"0.00","un":"UN","pesoBruto":"0.65000","largura":"18","altura":"20","profundidade":"20","descricaoDetalhada":"","unidadeMedida":"cm","gtin":"7891530558078"}},{"item":{"codigo":"003-2","descricao":"Produto Teste 03 Cor:preto, tamanho:40","quantidade":"1.0000","valorunidade":"59.0000000000","precocusto":null,"descontoItem":"0.00","un":"UN","pesoBruto":"1.10000","largura":"16","altura":"17","profundidade":"18","descricaoDetalhada":"","unidadeMedida":"cm","gtin":""}}],"parcelas":[{"parcela":{"idLancamento":0,"valor":"128.99","dataVencimento":"2020-08-27 00:00:00","obs":"Dinheiro","destino":3,"forma_pagamento":{"id":1155191,"descricao":"Dinheiro","codigoFiscal":1}}}]}}]}}');

                if ($dataOrder['httpcode'] != 200 || !isset($contentOrder->retorno->pedidos[0]->pedido)) {
                    if ($dataOrder['httpcode'] != 504 && $dataOrder['httpcode'] != 401) continue;

                    $msgError = "Não foi possível localizar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "E");
                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
                    continue;
                }

                $contentOrder = $contentOrder->retorno->pedidos[0]->pedido;

                // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
                if (!isset($contentOrder->nota->chaveAcesso)) {
                    echo "Pedido ainda não faturado\n";
                    continue;
                }

                // Dados da NF-e
                $nfe = $contentOrder->nota;


                // Dados para inserir a NF-e
                $arrNfe = array(
                    'order_id' => $orderId,
                    'company_id' => $this->company,
                    'store_id' => $this->store,
                    'date_emission' => date('d/m/Y H:i:s', strtotime($nfe->dataEmissao)),
                    'nfe_value' => $nfe->valorNota,
                    'nfe_serie' => $nfe->serie,
                    'nfe_num' => $nfe->numero,
                    'chave' => $nfe->chaveAcesso
                );

                // Inserir NF-e
                $insertNfe = $this->createNfe($arrNfe);

                // Erro para iserir a NF-e
                if (!$insertNfe) {
                    $msgError = "Não foi possível inserir dados de faturamento do pedido {$orderId}! DATA_NFE_INSERT=" . json_encode($arrNfe) . ", DATA_NFE_BLING=" . json_encode($contentOrder) . " RETORNO=" . json_encode($insertNfe);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "E");
                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar dados de faturamento do pedido {$orderId}</h4>", "E");
                    continue;
                }

                // Remover pedido da fila
                $this->removeOrderIntegration($idIntegration);

                // Salvar XML
                try {
                    $this->saveXML($nfe->chaveAcesso, $orderId);
                } catch (\Exception $e) {
                    $this->log_data('batch', $log_name, "Erro para salvar o XML: {$e->getMessage()}", "E");
                }

                $this->log_integration("Pedido {$orderId} atualizado",
                    "<h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> 
                          <ul>
                            <li><strong>Chave:</strong> {$nfe->chaveAcesso}</li>
                            <li><strong>Número:</strong> {$nfe->numero}</li>
                            <li><strong>Série:</strong> {$nfe->serie}</li>
                            <li><strong>Data de Emissão:</strong> ".date('d/m/Y H:i:s', strtotime($nfe->dataEmissao))."</li>
                            <li><strong>Valor:</strong> " . number_format($nfe->valorNota, 2, ',', '.') . "</li>
                          </ul>", "S");

                echo "Pedido {$orderId} atualizado com sucesso!\n";
                // ir para o próximo pedido
                continue;
            }

            // status do pedido
            $updateOrder['idSituacao'] = $statusId;
            $orderXml       = $this->arrayToXml($updateOrder);
            $url            = 'https://bling.com.br/Api/v2/pedido/' . $idBling;
            $data           = "&xml={$orderXml}";
            $dataStatus     = $this->sendREST($url, $data, 'PUT');
            $contentUpdate  = json_decode($dataStatus['content']);

            if ($dataStatus['httpcode'] != 200) {
                $msgError = "Não foi possível atualizar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER_INTEGRATION=".json_encode($orderIntegration)." ENVIADO=".json_encode($data).", RETORNO=".json_encode($dataStatus);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar o pedido {$orderId}</h4> <p>Ocorreu um problema inesperado, em breve tentaremos atualizar novamente.</p> <br>" . json_encode($contentUpdate), "E");
                continue;
            } elseif (isset($contentUpdate->retorno->erros)) {
                // formatar mensagens de erro para log integration
                $arrErrors = array();
                $errors = $contentUpdate->retorno->erros;
                if (!is_array($errors)) $errors = (array)$errors;
                foreach ($errors as $error) {

                    if (is_string($error)) {
                        array_push($arrErrors, $error);
                    } else {

                        foreach ($error as $e) {
                            $msgErrorIntegration = $e ?? "Erro desconhecido";
                            $msgErrorIntegration = $msgErrorIntegration == "Erro desconhecido" && isset($e->erro->msg) ? $e->erro->msg : $msgErrorIntegration;

                            if ($statusId == 12 && $this->likeText("%Não há transições definidas para esta entidade%", $msgErrorIntegration)) {
                                $this->removeAllOrderIntegration($orderId);
                                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li>{$e}</li></ul>", "S");
                                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=" . json_encode($updateOrder) . ', recebido=' . json_encode($dataStatus), "I");
                                echo "Pedido {$orderId} atualizado com sucesso!\n";
                                continue 3;
                            }

                            array_push($arrErrors, $msgErrorIntegration);
                        }
                    }
                }
                $msgError = "Não foi possível atualizar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dataStatus);
                echo "{$msgError}\n" ;
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                continue;
            }

            // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
            if ($removeList) { // deve ser romivido da fila
                if ($orderCancel)
                    $this->removeAllOrderIntegration($orderId);
                else
                    $this->removeOrderIntegration($idIntegration);
            }

            $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li>O status do pedido {$orderId}, foi atualizado para {$statusName}</li></ul>", "S");

            $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($updateOrder).', recebido='.json_encode($dataStatus), "I");
            echo "Pedido {$orderId} atualizado com sucesso!\n";
        }
    }

    /**
     * Recupera os pedidos para integração
     *
     * @return array Retorno os pedidos na fila para integrar
     */
    public function getOrdersForUpdate()
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
    public function getStatusIntegration($paid_status)
    {
        switch ($paid_status) {
//            case 1:
//                $status = 'aberto';
//                break;
            case 2:
                $status = array(
                    'code' => 6,
                    'name' => 'Em aberto'
                );
                break;
            case 3: // programa deve aguardar o lojista faturar o pedido
                $status = false;
                break;
            case 95:
            case 97:
                $status = array(
                    'code' => 12,
                    'name' => 'Cancelado'
                );
                break;
            default:
                $status = null;
                break;
        }

        return $status;
    }

    /**
     * Recupera código do pedido na Bling
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna código do pedido na Bling
     */
    public function getOrderIdBling($orderId)
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
    public function removeOrderIntegration($id)
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
    public function removeAllOrderIntegration($orderId)
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
    public function createNfe($data)
    {
        $sqlNfe     = $this->db->insert_string('nfes', $data);
        $insertNfe  = $this->db->query($sqlNfe) ? true : false;

        if (!$insertNfe) return false;
		
		//return $this->updateStatusForOrder($data['order_id'], 50, 3);  // FLUXO ANTIGO
        return $this->updateStatusForOrder($data['order_id'], 52, 3);

    }

    /**
     * Recupera se o pedido precisa ser cancelado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se existe cancelamento
     */
    public function getOrderCancel($orderId)
    {
        $orderCancel = $this->db
            ->from('orders_to_integration')
            ->where(array(
                'order_id'      => $orderId,
                'store_id'      => $this->store
            ))
            ->where_in('paid_status', array(95, 97))
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
    public function getIntegratedOrder($orderId)
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
    public function getOrderWithNfe($orderId)
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
    public function updateStatusForOrder($orderId, $status, $verifyStatus = null)
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
     * @param   int     $chave      Chave da NFe
     * @param   int     $orderId    Código do pedido na Conecta Lá
     * @return  bool                Retorna o status da importação do xml
     */
    private function saveXML($chave, $orderId)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $url = 'https://www.bling.com.br/relatorios/nfe.xml.php';
        $data = "&chaveAcesso={$chave}";
        $dataXml = $this->sendREST($url, $data);

        if ($dataXml['httpcode'] != 200) {
            $msgError = "Não foi possível localizar o xml do pedido {$orderId}! RETORNO=" . json_encode($dataXml);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "E");
            $this->log_integration("Erro para obter o XML do pedido {$orderId}", "<h4>Não foi possível obter o XML do pedido {$orderId}</h4>", "E");
            return false;
        }

        $dataXml = $dataXml['content'];

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

        $arquivo = fopen($targetDir . $chave . ".xml",'w');

        if ($arquivo == false) return false;

        fwrite($arquivo, $dataXml);

        fclose($arquivo);

        return true;
    }

    /**
     * Consulta string em uma parte de outra string
     *
     * @param   string  $needle     Valor a ser procurado
     * @param   string  $haystack   Valor real para comparação
     * @return  bool                Retorna o status da consulta
     */
    public function likeText($needle, $haystack)
    {
        $regex = '/' . str_replace('%', '.*?', $needle) . '/';

        return preg_match($regex, $haystack) > 0;
    }
}