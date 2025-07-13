<?php

/**
 * Class CreateOrder
 *
 *  php index.php BatchC/Integration/Bseller/Order/CreateOrder run null 51
 *
 */
require APPPATH . "controllers/BatchC/Integration/Bseller/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bseller/FormartOrder.php";

class CreateOrder extends Main
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

        $this->setJob('CreateOrder');

        $this->load->model('model_products');
        $this->load->model('model_orders');
        $this->load->model('model_orders_item');
        $this->load->model('model_orders_to_integration');
        $this->formatOrder = new FormatOrder($this->model_products, $this->model_orders, $this->model_orders_item);
        $this->_this = $this;
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        $this->store = $store;

        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando pedidos para enviar... \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        // Recupera os pedidos
        $this->sendOrders();

        // Grava a última execução
        $this->saveLastRun();

        // encerra o job
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Confrmação do Pedido enviado para o Bseller
     */
    private function confirmarEnvioPedido()
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

        //NÃO PRECISA DO FOR, PQ INDEPENDENTE DA QUANTIDADE DE PEDIDOS SEMPRE RETONAR UM UNICO batchNumber
        $url = $BSELLER_URL . 'api/entregas/massivo?unidadeNegocio=8&maxRegistros=100';
        $data = "";

        $dataOrder = json_decode(json_encode($this->sendREST($url, $data)));
        $registro = json_decode($dataOrder->content);
        // Confirmações de pedidos

        $batchNumber = (isset($registro->batchNumber)) ? $registro->batchNumber : NULL;

        if ($dataOrder->httpcode != 200) {
            $msgError = "Não foi possível recuperar entrega massiva do pedido, \nRETORNO=" . json_encode($dataOrder);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "W");
            // $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
            // throw new Exception();
        } else {
            if ($batchNumber !== NULL) {

                $url = $BSELLER_URL . 'api/entregas/massivo/' . $batchNumber;
                $data = "";

                $dataPedidoConfirmado = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));

                if ($dataPedidoConfirmado->httpcode != 200) {
                    $msgError = "Não foi possível confirmar entrega do pedido, RETORNO=" . json_encode($dataPedidoConfirmado);
                    // $msgError = "Não foi possível localizar o pedido {$orderId}, idBseller: {$idBseller}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    // $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
                    // throw new Exception();
                }
            }
        }
        //  return  $numeroPedido;
    }
    public function sendOneOrder($orderIntegration, $log_name)
    {
        $pedidoID = $orderIntegration['order_id'];
        echo "Executando rotina para o pedido: {$pedidoID}";
        // $paidStatus = $orderIntegration['paid_status'];

        $this->setUniqueId($pedidoID); // define novo unique_id
        // verifica cancelado, para não integrar
        if ($this->model_orders_to_integration->isCanceled($pedidoID,$this->store)) {
            $this->model_orders_to_integration->removeAllOrderIntegration($pedidoID,$this->store);

            $msgError = "PEDIDO={$pedidoID} cancelado, não será integrado - ORDER_INTEGRATION=" . json_encode($orderIntegration);
            //$this->log_data('batch', $log_name, $msgError, "W");
            $this->log_integration("Pedido {$pedidoID} cancelado", "<h4>Pedido {$pedidoID} não será integrado</h4> <ul><li>Pedido cancelado antes de ser realizado o pagamento.</li></ul>", "S");
            throw new Exception($msgError);
        }

        // Ignoro o status pois ainda não foi pago e não será enviado pro erp
        if ($orderIntegration['paid_status'] != 3) {
            // Pedido chegou como não pago, mas já mudou de status
            $this->model_orders_to_integration->getOrderOtherThanUnpaid($pedidoID,$this->store);
            $msg = "Pedido $pedidoID chegou não pago, vou ignorar\n\n";
            throw new Exception($msg);
        }
        $pedido = $this->model_orders->getDataOrdersIntegrations($pedidoID);
        if (!$pedido) {
            $msgError = "Não foi encontrado o PEDIDO={$pedidoID} para ORDER_INTEGRATION=" . json_encode($orderIntegration);
            $this->log_data('batch', $log_name, $msgError, "W");
            $this->log_integration("Erro para integrar o pedido {$pedidoID}", "Não foi possível integrar o pedido {$pedidoID} <br> <ul><li>Não foi possível encontrar os dados do pedido para integrar o pedido.</li></ul>", "E");
            throw new Exception($msgError);
        }

        $arrayFinalPedido = $this->formatOrder->formatToBseller($pedido);
        $valor = str_replace(".", "", $arrayFinalPedido["clienteFaturamento"]['id']);
        $valor = str_replace("-", "", $valor);
        $cpfCnpj = $valor;
        $tipoPessoa = strlen($cpfCnpj) > 11 ? 1 : 2; //1=Pessoa Juridica, 2=Pessoa Física
        if (empty($pedidoParaIntegrar['ie_client']) && $tipoPessoa == 1) {
            $tipoPessoa = 5;
        }
        if ($tipoPessoa == 1) {
            $this->validarCampoObrigatorio('ie_client', $arrayFinalPedido, $log_name);
        }

        //chegou até aqui, hora de preparar os dados para integrar
        foreach ($arrayFinalPedido["itens"] as $key => $value) {
            $this->validarCampoObrigatorio('precoUnitario', $value, $log_name);
            $this->validarCampoObrigatorio('codigoItem', $value, $log_name);
            $this->validarCampoObrigatorio('quantidade', $value, $log_name);
            $this->validarCampoObrigatorio('sequencial', $value, $log_name);
        }

        $this->validarCampoObrigatorio('numeroPedido', $arrayFinalPedido, $log_name);
        $this->validarCampoObrigatorio('valorTotalProdutos', $arrayFinalPedido['valores'], $log_name);
        $this->validarCampoObrigatorio('dataEmissao', $arrayFinalPedido, $log_name);
        $this->validarCampoObrigatorio('dataInclusao', $arrayFinalPedido, $log_name);
        $this->validarCampoObrigatorio('customer_address_neigh', $pedido, $log_name);
        $this->validarCampoObrigatorio('customer_address_zip', $pedido, $log_name);
        $this->validarCampoObrigatorio('customer_address_city', $pedido, $log_name);
        $this->validarCampoObrigatorio('address_order', $pedido, $log_name);
        $this->validarCampoObrigatorio('name_order', $pedido, $log_name);
        $this->validarCampoObrigatorio('phone_order', $pedido, $log_name);
        $this->validarCampoObrigatorio('neigh_client', $pedido, $log_name);
        $this->validarCampoObrigatorio('cep_client', $pedido, $log_name);
        $this->validarCampoObrigatorio('city_client', $pedido, $log_name);
        $this->validarCampoObrigatorio('uf_client', $pedido, $log_name);
        $this->validarCampoObrigatorio('address_client', $pedido, $log_name);
        $this->validarCampoObrigatorio('num_client', $pedido, $log_name);
        $this->validarCampoObrigatorio('name_client', $pedido, $log_name);
        $this->validarCampoObrigatorio('phone_client_1', $pedido, $log_name);
        $this->validarCampoObrigatorio('name_client', $pedido, $log_name);


        $orderEncode = json_encode($arrayFinalPedido);
        $BSELLER_URL = $this->getUrlBseller($this->store);
        $url = $BSELLER_URL . 'api/pedidos';

        $dataOrder = $this->sendREST($url, $orderEncode, 'POST');

        if ($dataOrder['httpcode'] != 200) {
            $getRetorno = json_decode($dataOrder['content']);
            $msgError = "Não foi possível integrar o pedido {$orderIntegration['id']}! ";
            $this->log_data('batch', $log_name, $msgError, "W");

            // formatar mensagens de erro para log integration
            $arrErrors = array();

            if ($dataOrder['httpcode'] == 500) {
                $arrErrors = 'Problema ao integrar!';
            } else {
                $errors = (isset($getRetorno->message)) ? $getRetorno->message : array();

                if (!is_array($errors)) {
                    $errors = (array) $errors;
                }
                foreach ($errors as $error) {
                    $msgErrorIntegration = $error ?? "Erro desconhecido";
                    array_push($arrErrors, $msgErrorIntegration);
                }
            }
            $msgLog = "<h4>Não foi possível integrar o pedido {$pedidoID}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>\n";
            // $msg = "Não foi possível integrar o pedido {$pedidoID}\n" . implode('\n', $arrErrors) . "\n";
            $this->log_integration("Erro para integrar o pedido {$pedidoID}", $msgLog, "E");
            // echo "{$msg}\n";
            throw new Exception($msgLog, 2);
        }



        //pegando o ID gerado no Bseller
        //get pedidos massivo 
        $idBseller = 0;
        $url = $BSELLER_URL . 'api/entregas/massivo?unidadeNegocio=8&maxRegistros=100';
        $dataProducts = json_decode(json_encode($this->sendREST($url, '')));
        $pedidosBseller = json_decode($dataProducts->content);
        if (isset($pedidosBseller->content)) {
            foreach ($pedidosBseller->content as $pedidoBseller) {
                if ($pedidoBseller->numeroPedido == $pedidoID) {
                    $idBseller = $pedidoBseller->entregas[0]->idEntrega;
                    break;
                }
            }
        }
        if ($idBseller > 0) {
            $this->model_orders->saveOrderIdIntegrationByOrderIDAndStoreId($pedidoID,$this->store, $idBseller);
            if ($orderIntegration['paid_status'] != 3) {
                $this->model_orders_to_integration->removeOrderIntegration($pedidoID,$this->store);
            }else{
                $this->model_orders->updateOrderIdIntegrationByOrderID($pedidoID,$idBseller);
            }

            $this->model_orders->updateDataAndStatusByIdIntegrationAndStoreIdAndPaidStatus($orderIntegration['id'],$this->store,$orderIntegration['paid_status']);
            $this->log_integration("Pedido {$pedidoID} integrado", "<h4>Novo pedido integrado com sucesso</h4> <ul><li>O pedido {$pedidoID}, foi criado no Bseller com o \"id de entrega\" {$idBseller}</li></ul>", "S");
            echo "Pedido {$pedidoID} integrado com sucesso com o código {$idBseller}!\n";
            
        }
    }

    /**
     * Envia os pedidos para serem integrados
     */
    public function sendOrders()
    {

        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $orders = $this->model_orders->getOrdersIntegrations($this->store);
        foreach ($orders as $orderIntegration) {
            try {
                $this->sendOneOrder($orderIntegration, $log_name);
            } catch (Exception $e) {
                if ($e->getCode() != 2) {
                    echo "{$e->getMessage()}";
                    print_r($e->getTrace());
                } else {
                    echo "{$e->getMessage()}";
                }
            }

            //-----------------------------------------------------------------
        } //loop com todos os pedidos para integrar
        //confirmar pedido entrege ao Bseller

        if (count($orders) > 0) {
            $this->confirmarEnvioPedido();
        }
    }

    public function limpaCPFCNPJ($valor)
    {
        $valor = trim($valor);
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", "", $valor);
        $valor = str_replace("-", "", $valor);
        $valor = str_replace("/", "", $valor);
        return $valor;
    }

    public function validarCampoObrigatorio($campo, $array, $log_name)
    {
        if (empty($array[$campo])) {
            $msgError = "O campo '$campo' não foi encontrado, o pedido não será integrado";
            $this->log_data('batch', $log_name, $msgError, "W");
            $this->log_integration("Erro para integrar o pedido", "Não foi possível integrar o pedido <br> <ul><li>O campo '$campo' não foi encontrado, o pedido não será integrado</li></ul>", "E");
            throw new Exception("{$msgError}\n");
        } else {
            return true;
        }
    }

    

    /**
     * Recupera a quantidade em estoque do produto
     *
     * @param   array   $iten   Array com dados do produto
     * @return  int             Retorna a quantidade em estoque do produto/variação
     */
    public function getStockProduct($iten)
    {
        if ($iten['variant_product'] == "")
            return $iten['qty_product'];

        $var = $this->db
            ->get_where(
                'prd_variants',
                array(
                    'prd_id' => $iten['id_product'],
                    'variant' => $iten['variant_product']
                )
            )->row_array();

        return $var['qty'];
    }

    

    /**
     * Recupera o SKU do produto/variação vendido
     *
     * @param   array       $iten   Array com dados do produto vendido
     * @return  false|string        Retorna o sku do produto ou variação, em caso de erro retorna false
     */
    public function getSkuProductVariationOrder($iten)
    {
        if ($iten['variant_product'] == "")
            return $iten['sku_product'];

        $var = $this->db
            ->get_where(
                'prd_variants',
                array(
                    'prd_id' => $iten['id_product'],
                    'variant' => $iten['variant_product']
                )
            )->row_array();

        if (!$var)
            return false;

        return $var['sku'];
    }

    public function getUrlBseller($store_id)
    {
        $dataIntegrationStore = $this->_this->db
            ->from('api_integrations')
            ->where('store_id', $store_id)
            ->get()
            ->result_array();

        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore[0]['credentials']);
            return $credentials->url_bseller;
        }
        return null;
    }

    

    /**
     * Cria um novo registro de integração caso o status for 3, aguardar para faturar
     *
     * @param   array   $data   Dados da integração
     * @return  bool            Retorna o status da criação
     */
    

    /**
     * Lançar estoque do pedido inserido
     *
     * @param $codProduto
     * @param $orderId
     * @param $dataEstoque
     * @throws Exception
     */
    private function PostOrderStock($codProduto, $orderId, $dataEstoque)
    {
        $BSELLER_URL = $this->getUrlBseller($this->store);
        $url = $BSELLER_URL . 'api/estoques/' . $codProduto;
        $data = $dataEstoque;

        $dataOrder = $this->sendREST($url, $data, 'POST');
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($dataOrder['httpcode'] >= 400) {
            $msgError = "Não foi possível lançar estoque do pedido {$orderId}! codProduto={$codProduto}, RETORNO=" . json_encode($dataOrder);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "W");

            // formatar mensagens de erro para log integration
            $arrErrors = array();
            $getRetorno = json_decode($dataOrder['content']);

            if (isset($getRetorno->error)) {
                $errors = $getRetorno->error;

                if (!is_array($errors))
                    $errors = (array) $errors;
                foreach ($errors as $error) {
                    $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                    array_push($arrErrors, $msgErrorIntegration);
                }
            } else {
                array_push($arrErrors, $getRetorno);
            }

            if (isset($arrErrors[0]) && $arrErrors[0] != 'Estoque já lançado.')
                $this->log_integration("Erro para lançar estoque do pedido {$orderId}", "<h4>Não foi possível lançar o estoque do pedido {$orderId} no Eccosys</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");

            return false;
        } else {
            return true;
        }
    }

    public function buscarStatus($paidStatus)
    {
        // Status do pedido
        switch ($paidStatus) {
            case 1:
                $situacao = "Aguardando Pagamento(Não foi pago ainda)";
                $situacaoBseller = -1;
                break;
            case 3:
                $situacao = "Aguardando Faturamento"; //(Pedido foi pago, está aguardando ser faturado)
                $situacaoBseller = 0; //Em Aberto
                //$situacaoEccosys = 3; //pronto para picking
                break;
            case 4:
                $situacao = "Aguardando Coleta/Envio"; //(Aguardando o seller enviar ou transportadora coletar)
                $situacaoBseller = 3; //Pronto para picking
                break;
            case 5:
                $situacao = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoBseller = 1;
                break;
            case 6:
                $situacao = "Entregue"; //(Pedido entregue ao cliente)
                $situacaoBseller = 'D';
                break;
            case 40:
                $situacao = "Aguardando Rastreio"; //(Pedido faturado, aguardando envio de rastreio)
                $situacaoBseller = 3;
                break;
            case 43:
                $situacao = "Aguardando Coleta/Envio"; //(Pedido com rastreio, aguardando ser coletado/enviado)
                $situacaoBseller = 3;
                break;
            case 45:
                $situacao = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoBseller = 1;
                break;
            case 50:
                $situacao = "Aguardando Seller Emitir Etiqueta"; //(Pedido faturado, contratando frete)
                $situacaoBseller = 3;
                break;
            case 51:
                $situacao = "PLP gerada"; //(Enviar rastreio para o marketplace)
                $situacaoBseller = 1;
                break;
            case 52:
                $situacao = "Pedido faturado"; //(Enviar NF-e para o marketplace)
                $situacaoBseller = 1;
                break;
            case 53:
                $situacao = "Aguardando Coleta/Envio"; //(Aguardando pedido ser postado/coletado para rastrear)
                $situacaoBseller = 3;
                break;
            case 55:
                $situacao = "Em Transporte"; //(Avisaro marketplace que o pedido foi enviado)
                $situacaoBseller = 1;
                break;
            case 56:
                $situacao = "Processando Nota Fiscal"; //(Processando NF aguardando envio (módulo faturador))
                $situacaoBseller = 4;
                break;
            case 57:
                $situacao = "Nota Fiscal Com Erro"; //(Problema para faturar o pedido (módulo faturador))
                $situacaoBseller = 4;
                break;
            case 60:
                $situacao = "Entregue"; //(Avisar ao marketplace que foi entregue)
                $situacaoBseller = 'D';
                break;
            case 96:
                $situacao = "Cancelado"; //(Cancelado antes de realizar o pagamento)
                $situacaoBseller = 2;
                break;
            case 97:
                $situacao = "Cancelado"; //(Cancelado após o pagamento)
                $situacaoBseller = 2;
                break;
            case 98:
                $situacao = "Cancelar na Transportadora"; //(Cancelar rastreio na transportadora (não correios))
                $situacaoBseller = 2;
                break;
            case 99:
                $situacao = "Cancelar no Marketplace"; //(Avisar o cancelamento para o marketplace)
                $situacaoBseller = 2;
                break;
            case 101:
                $situacao = "Sem Cotação de Frete"; //(Deve fazer a contratação do frete manual (não correios))
                $situacaoBseller = 0;
                break;
            default:
                $situacao = 'Não foi encontrato o status';
                $situacaoBseller = -1;
        }

        $arrRetorno = array(
            'situacao' => $situacao,
            'situacaoBseller' => $situacaoBseller
        );

        return $arrRetorno;
    }
}
