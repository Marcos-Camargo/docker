<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration/Eccosys/Order/CreateOrder run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";

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
        $this->load->model('model_stores');

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
        $this->sendOrders();

        // Grava a última execução
        $this->saveLastRun();

        // encerra o job
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }


    /**
     * Envia os pedidos para serem integrados
     */
    public function sendOrders()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $orders = $this->getOrdersIntegrations();

        $loja = $this->model_stores->getStoresData($this->store);
        $logisticaProria = $loja['freight_seller'];

        foreach ($orders as $orderIntegration) {
            $orderId    = $orderIntegration['order_id'];
            $paidStatus = $orderIntegration['paid_status'];

            $this->setUniqueId($orderId); // define novo unique_id

            // verifica cancelado, para não integrar
            if ($this->getOrderCancel($orderId)) {
                $this->removeAllOrderIntegration($orderId);

                $msgError = "PEDIDO={$orderId} cancelado, não será integrado - ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Pedido {$orderId} cancelado", "<h4>Pedido {$orderId} não será integrado</h4> <ul><li>Pedido cancelado antes de ser realizado o pagamento.</li></ul>", "S");
                continue;
            }

            // Ignoro o status pois ainda não foi pago e não será enviado pro erp
            if ($paidStatus != 3) {
                // Pedido chegou como não pago, mas já mudou de status
                $this->getOrderOtherThanUnpaid($orderId);

                echo "Chegou não pago, vou ignorar\n";
                continue;
            }

            $order = $this->getDataOrdersIntegrations($orderId);
           
            if (!$order) {
                $msgError = "Não foi encontrado o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId} <br> <ul><li>Não foi possível encontrar os dados do pedido para integrar o pedido.</li></ul>", "E");
                continue;
            }
            
            $orderMain = $order[0];

            // não encontrou o pedido
            if (!$orderMain['order_id']) {
                $msgError = "Não foi encontrado o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId} <br> <ul><li>Não foi possível encontrar os dados do pedido para integrar o pedido.</li></ul>", "E");
                continue;
            }
       
            // não encontrou o cliente
            if (!$orderMain['name_client']) {
                $msgError = "Não foi encontrado o cliente para o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi possível encontrar os dados do cliente para faturar o pedido.</li></ul>", "E");
                continue;
            }

            // não encontrou o produto(s)
            if (!$orderMain['id_iten_product']) {
                $msgError = "Não foi encontrado o(s) produto(s) para o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi possível encontrar os dados do(s) produto(s) para faturar o pedido.</li></ul>", "E");
                continue;
            }           

            $arrRetornoSituacao = $this->buscarStatus($paidStatus);           
            $situacaoEccosys = $arrRetornoSituacao['situacaoEccosys'];
            $situacao = $arrRetornoSituacao['situacao'];

            $newOrder = array();

            $newOrder['data'] = date('Y-m-d', strtotime($orderMain['date_created']));
            $newOrder['situacao'] = $situacaoEccosys; //verificar informação ****
            $newOrder['situacaoSecundaria'] = 0;
            $newOrder['desconto'] = $orderMain['discount_order'];
            $newOrder['condicao'] = $orderMain['parcela_payment'] ? $orderMain['parcela_payment'].'x' : null;
            $newOrder['observacoes'] = "";
            $newOrder['totalProdutos'] = $orderMain['total_order'];
            $newOrder['totalVenda'] = $orderMain['gross_amount'];
            $newOrder['idOrigem'] = 0;
            $newOrder['idPedidoOrigem'] = null;  //Se houver infomação, vai deixar pedido como Parcial.
            $newOrder['numeroPedido'] = $orderMain['order_id'];
            $newOrder['dataPrevista'] = $this->somar_dias_uteis(date('Y-m-d', strtotime($orderMain['data_pago'])),$orderMain['ship_time'],'');
            $newOrder['idVendedor'] = $this->store;
            //$newOrder['idCategoria'] = null;
            $newOrder['frete'] = $orderMain['total_ship'];
            //$newOrder['primeiraCompra'] = 1;
            //$newOrder['observacaoInterna'] = "Flat Rate - Fixed";
            //$newOrder['dataFaturamento'] = null;
            $newOrder['numeroDaOrdemDeCompra'] = str_replace(' ', '', $orderMain['numero_marketplace']);
            $newOrder['transportador'] = $orderMain['ship_company'];
            $newOrder['fretePorConta'] = "T";  // = Por conta de Terceiros, confirmado por Eliza -> Pedro
            //$newOrder['valorIPI'] = "0.00"; //Informações de "Valor do IPI" e "Valor do ICMS ST" serão usados pelo erp que vai faturar o pedido, o conecta lá não tem essas informações.
            //$newOrder['valorST'] = "0.00"; //Informações de "Valor do IPI" e "Valor do ICMS ST" serão usados pelo erp que vai faturar o pedido, o conecta lá não tem essas informações.

            if($orderMain['address_order'] == $orderMain['address_client'] && 
                $orderMain['customer_address_num'] == $orderMain['num_client'] &&
                $orderMain['customer_address_compl'] == $orderMain['compl_client'] &&
                $orderMain['customer_address_neigh'] == $orderMain['neigh_client'] &&
                $orderMain['customer_address_city'] == $orderMain['city_client'] &&
                $orderMain['customer_address_uf'] == $orderMain['uf_client'] &&
                $orderMain['customer_address_zip'] == $orderMain['cep_client']) {
                $newOrder['opcEnderecoDiferente'] = "N";  //Determina se o endereço de entrega será diferente. S ou N
            } else {
                $newOrder['opcEnderecoDiferente'] = "S";
            }

            $newOrder['paymentOrderID'] = $orderMain['id_payment'];

            $newOrder['codigoRastreamento'] = '';
            $newOrder['dataCodigoRastreamento'] = null;

            if($orderMain['data_entrega']){
                $newOrder['rastreamentoConcluido'] = "S";   //status == 60, não sei qual é !
            } else {
                $newOrder['rastreamentoConcluido'] = "N";   //status == 60, não sei qual é !
            }

/*            if($orderMain['data_coleta']){
                $newOrder['pickingRealizado'] = "S";
            } else {
                $newOrder['pickingRealizado'] = "N";
            } */
            $newOrder['pickingRealizado'] = "N"; //coloca como N, dai na realização do picking, o sistema vai setar como S.
            
            $newOrder['situacaoDescricao'] = $situacao; //"Importado automaticamente - Origem [Tarefa Agendada]";
            $newOrder['dataPagamento'] = date('Y-m-d', strtotime($orderMain['data_pago']));
            $newOrder['formaFrete'] = $orderMain['ship_service'];
            $newOrder['avisoRecebimento'] = null;
            $newOrder['maoPropria'] = null;
            $newOrder['possuiValorDeclarado'] = null;
            $newOrder['valorDeclarado'] = null;
            $newOrder['statusAlterado'] = "X";
            $newOrder['syncLogistica'] = null;
            $newOrder['confirmacaoLogistica'] = null;
            $newOrder['dtCriacaoVenda'] = date('Y-m-d H:i:s', strtotime($orderMain['date_created']));
            $newOrder['tipoObjeto'] = null;
            $newOrder['dimensaoDiametro'] = null;
            $newOrder['dimensaoAltura'] = null;
            $newOrder['dimensaoLargura'] = null;
            $newOrder['dimensaoComprimento'] = null;
            //$newOrder['idLojaVirtual'] = 0;
            $newOrder['servicePlatformOrigin'] = "Conecta Lá";
            $newOrder['pedidoDeServico'] = $orderMain['order_id'];
            $newOrder['idMarketplaceIntegrado'] = '127376116';
            $newOrder['dataEntrega'] = $orderMain['data_entrega'];
            $newOrder['numeroNotaFiscal'] = $orderMain['nfe_num'];
            $newOrder['canalDeVenda'] = 'CNL'; //$orderMain['origin']; Conforme Pedro será sempre Conectalá.

            // Outro endereço - //Endereço utilizado para a entrega, caso for diferente do endereço de cobrança
            if($newOrder['opcEnderecoDiferente'] == "S"){
                $newOrder['_OutroEndereco'] = array();
                $newOrder['_OutroEndereco']['nome'] = $orderMain['name_client'];
                $newOrder['_OutroEndereco']['endereco'] = $orderMain['address_order'];
                $newOrder['_OutroEndereco']['enderecoNro'] = $orderMain['customer_address_num'];
                $newOrder['_OutroEndereco']['bairro'] = $orderMain['customer_address_neigh'];
                $newOrder['_OutroEndereco']['complemento'] = $orderMain['customer_address_compl'];
                $newOrder['_OutroEndereco']['cep'] = $orderMain['customer_address_zip'];
                $newOrder['_OutroEndereco']['cidade'] = $orderMain['customer_address_city'];
                //$newOrder['_OutroEndereco']['idMunicipio'] = "4302105";
                $newOrder['_OutroEndereco']['uf'] = $orderMain['customer_address_uf'];
                //$newOrder['_OutroEndereco']['idPais'] = 0;
                //$newOrder['_OutroEndereco']['nomePais'] = "";
            }

            // Parcelas - Dados da parcela da venda
            $newOrder['_Parcelas'] = array();
            $codParcelaAnterior = null;
            
            foreach ($order as $parcelas){
                if($parcelas['id_payment'] != $codParcelaAnterior){
                    //Forma de pagamento
                    switch ($parcelas['forma_payment']) {
                        case 'Dinheiro':
                            $formaPagamento = 1;
                            break;
                        case 'Cheque':
                            $formaPagamento = 2;
                            break;
                        case 'credit_card':
                            $formaPagamento = 3;  //Cartão de Crédito
                            break;
                        case 'Cartão de débito':
                            $formaPagamento = 4;
                            break;
                        case 'Crédito loja':
                            $formaPagamento = 5;
                            break;
                        case 'Vale alimentação':
                            $formaPagamento = 10;
                            break;
                        case 'Vale refeição':
                            $formaPagamento = 11;
                            break;                            
                        case 'Vale presente':
                            $formaPagamento = 12;
                            break;
                        case 'Vale combustível':
                            $formaPagamento = 13;
                            break;
                        case 'Boleto Bancário':
                            $formaPagamento = 15;
                            break;
                        case 'Sem pagamento':
                            $formaPagamento = 90;
                            break;
                        default:
                            $formaPagamento = 99;    //Outros
                            break;
                    }

                    array_push($newOrder['_Parcelas'],
                        array(
                            'obs' => $parcelas['forma_payment'],
                            'valor' => $parcelas['valor_payment'],
                            'nroDias' => $parcelas['parcela_payment'],
                            'dataVencimento' => $parcelas['vencto_payment'],
                            'codigoFormaPagamento' => $formaPagamento
                        )
                    );
                }

                $codParcelaAnterior = $parcelas['id_payment'];
            }            
            
            $idProductAnterior = null;
            $pesoTransporte = 0;
            $newOrder['_Itens'] = array();

            foreach ($order as $iten) {                
                if($iten['id_product'] != $idProductAnterior) {
                    $sku = $this->getSkuProductVariationOrder($iten);

                    // não encontrou o SKU
                    if (!$sku) {
                        $msgError = "Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']} ID Produto ConectaLá={$iten['id_product']}.</li></ul>", "E");
                        continue 2;
                    }

                    // Estoque zerado
                    $stockProduct = $this->getStockProduct($iten);
                    if ($stockProduct <= 0) {
                        $msgError = "Produto sem estoque para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>O produto não tem estoque para integrar! SKU={$iten['sku_product']} ID Produto ConectaLá={$iten['id_product']}.</li></ul>", "E");
                        continue 2;
                    }                    

                    array_push($newOrder['_Itens'],
                        array('descricao' => $iten['name_product'],
                        'valor' => $iten['price_product'],
                        'quantidade' => $iten['qty_product'],
                        'itemBonificacao' => 'N',
                        '_Produto' => array(
                            'nome' => $iten['name_product'],
                            'unidade' => strtolower($iten['un_product']), // Padrão minúsculo
                            'idProduto' => $iten['id_product'],
                            'preco' => $iten['price_product'],
                            'precoLista' => null,
                            'codigo' =>  $iten['sku_product']
                        )
                    ));
                    
                    $pesoTransporte += (float)$iten['peso_product'];
                }
                
                $idProductAnterior = $iten['id_product'];
            }

            $newOrder['pesoTransportadora'] = $pesoTransporte;
            $newOrder['qtdVolumes'] = "1";
            $newOrder['pesoLiquido'] = $pesoTransporte;
            $newOrder['pesoBruto'] = $pesoTransporte;
            $newOrder['especieVolume'] = null;

            // Transporte
            $arrDadosTransporte = $this->getProviderOrder($orderMain['ship_company'], $orderMain['ship_service'], $pesoTransporte);

            if ($logisticaProria == 0 && $arrDadosTransporte) {
                $newOrder['_Transportadora'] = array(
                    'nome'          => $arrDadosTransporte['transportadora'],
                    'cnpj'          => $arrDadosTransporte['cnpj'],
                    'cep'           => $orderMain['customer_address_zip'],
                    'endereco'      => $orderMain['address_order'],
                    'numero'        => $orderMain['customer_address_num'],
                    'complemento'   => $orderMain['customer_address_compl'],
                    'bairro'        => $orderMain['customer_address_neigh'],
                    'cidade'        => $orderMain['customer_address_city'],
                    'uf'            => $orderMain['customer_address_uf'],
                    'situacao'      => 'A',
                    'nomePais'      => 'Brasil'
                );
            } else
                $newOrder['_Transportadora'] = array(
                    'nome' => $orderMain['ship_company']
                );
            
            // Cliente
            $newOrder['_Cliente'] = array();
            $newOrder['_Cliente']['id'] = $orderMain['cod_client'];
            $newOrder['_Cliente']['codigo'] = (isset($orderMain['cod_client'])) ? $orderMain['cod_client'] : null;
            $newOrder['_Cliente']['nome'] = $orderMain['name_client'];
            $newOrder['_Cliente']['fantasia'] = null; // Ainda não usam nome fantasia
            $newOrder['_Cliente']['endereco'] = $orderMain['address_client'];
            $newOrder['_Cliente']['bairro'] = $orderMain['neigh_client'];
            $newOrder['_Cliente']['cep'] = $orderMain['cep_client'];
            $newOrder['_Cliente']['cidade'] = $orderMain['city_client'];
            $newOrder['_Cliente']['uf'] = $orderMain['uf_client'];
            $newOrder['_Cliente']['fone'] = $orderMain['phone_client_1'];
            $newOrder['_Cliente']['fax'] = null;
            $newOrder['_Cliente']['celular'] = $orderMain['phone_client_2'];
            $newOrder['_Cliente']['email'] = $orderMain['email_client'];
            $newOrder['_Cliente']['email_nfe'] = "cliente@teste.com.br";
            $newOrder['_Cliente']['site'] = null;
            $newOrder['_Cliente']['obs'] = null;
            $newOrder['_Cliente']['cnpj'] = $orderMain['cpf_cnpj_client'];
            $newOrder['_Cliente']['identificadorEstrangeiro'] = null;
            $newOrder['_Cliente']['ie'] = $orderMain['ie_client'];
            $newOrder['_Cliente']['rg'] = $orderMain['rg_client'];
            $newOrder['_Cliente']['tipo'] = "F"; // F - Pessoa Física / J - Pessoa Jurídica / E - Estrangeiro

            $newOrder['_Cliente']['identificadorIE'] = 9; // Determina se o cliente é contibuinte do ICMS ou não (
            //1 - Contribuinte ICMS  
            //2 - Contribuinte isento de IE  
            //9 - Não Contribuinte (com ou sem IE))

            $newOrder['_Cliente']['enderecoNro'] = $orderMain['customer_address_num'];
            $newOrder['_Cliente']['complemento'] = $orderMain['customer_address_compl'];
            $newOrder['_Cliente']['situacao'] = "A"; // Tirar dúvida
            $newOrder['_Cliente']['nomePais'] = ($orderMain['country_client'] == 'BR') ? 'Brasil' : $orderMain['country_client'];
            $newOrder['_Cliente']['registroMA'] = null; // Não está sendo mais utilizado, segundo o Eccosys
            $newOrder['_Cliente']['codigoNoFornecedor'] = null;
            $newOrder['_Cliente']['tipoNegocio'] = null; // Não está sendo mais utilizado, segundo o Eccosys
            $newOrder['_Cliente']['dataUltimaCompra'] = null;
            $newOrder['_Cliente']['limiteCredito'] = null;
            $newOrder['_Cliente']['dadosBancariosPO'] = null;
            $newOrder['_Cliente']['descricaoDetalhada'] = null;
            $newOrder['_Cliente']['ecommerceStatus'] = null;
            $newOrder['_Cliente']['ecommerceClientID'] = null;
            $newOrder['_Cliente']['prazoPagamento'] = null;
            $newOrder['_Cliente']['classificacaoContato'] = null;
            $newOrder['_Cliente']['areaAtuacao'] = null;
            $newOrder['_Cliente']['emitirBoleto'] = null;
            $newOrder['_Cliente']['dtCriacao'] = null;
            $newOrder['_Cliente']['potencial_venda'] = null;
            $newOrder['_Cliente']['key_account'] = null;            


            $orderEncode = json_encode($newOrder);
                
            $ECCOSYS_URL = $this->getUrlEccosys($this->store);
            $url = $ECCOSYS_URL.'/api/pedidos';

            $dataOrder = $this->sendREST($url, $orderEncode, 'POST');
            
            if ($dataOrder['httpcode'] != 200) {
                $msgError = "Não foi possível integrar o pedido {$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");

                // formatar mensagens de erro para log integration
                $arrErrors = array();
                $getRetorno = json_decode($dataOrder['content']);

                if($dataOrder['httpcode'] == 500) {
                    $arrErrors[] = 'Problema ao integrar!';
                } else {
                    $errors = $getRetorno->error;

                    if (!is_array($errors)) $errors = (array)$errors;
                    foreach ($errors as $error) {
                        $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                        array_push($arrErrors, $msgErrorIntegration);
                    }
                }

                //$this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                $this->log_integration(
                    "Erro para integrar o pedido {$orderId}",
                    "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>"
                    . implode('</li><li>', $arrErrors) .
                    "</li></ul></br></br>" . json_encode($dataOrder['content']),
                    "E");
                continue;
            }

            $getRetorno = json_decode($dataOrder['content']);
            $getErros = $getRetorno->error;

            if (!isset($getRetorno->success[0]->id)) {
                $msgError = "Não foi possível obter o código do pedido integrado. PEDIDO={$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>Não foi possível recuperar o código do pedido integrado para gravar. Contate o suporte!</li></ul>", "E");
                continue;
            }

            $idEccosys = $getRetorno->success[0]->id;
            $numeroEccosys = $getRetorno->success[0]->codigo;


            $this->saveOrderIdIntegration($orderMain['order_id'], $idEccosys);
            if ($paidStatus != 3) {
                $this->removeOrderIntegration($orderMain['order_id']);
            }

            $this->controlRegisterIntegration($orderIntegration);
            $this->log_integration("Pedido {$orderId} integrado", "<h4>Novo pedido integrado com sucesso</h4> <ul><li>O pedido {$orderMain['order_id']}, foi criado no Eccosys com o código {$numeroEccosys} e ID {$idEccosys}</li></ul>", "S");            
            echo "Pedido {$orderMain['order_id']} integrado com sucesso com o código {$idEccosys}!\n";
        }
    }


    /**
     * Recupera os pedidos para integração
     *
     * @return array Retorno os pedidos na fila para integrar
     */
    public function getOrdersIntegrations()
    {
        return $this->db
            ->from('orders_to_integration')
            ->where(array(
                'store_id'  => $this->store,
                'new_order' => 1
            ))
            ->where_in('paid_status', array(1, 2, 3, 96))
            ->get()
            ->result_array();
    }

    /**
     * Recupera a quantidade em estoque do produto
     *
     * @param   array   $iten   Array com dados do produto
     * @return  int             Retorna a quantidade em estoque do produto/variação
     */
    public function getStockProduct($iten)
    {
        if ($iten['variant_product'] == "") return $iten['qty_product'];

        $var = $this->db
            ->get_where('prd_variants',
                array(
                    'prd_id'    => $iten['id_product'],
                    'variant'   => $iten['variant_product']
                )
            )->row_array();

        return $var['qty'];
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
                    'paid_status'   => 96
                )
            )->row_array();

        if (!$orderCancel) return false;

        return true;
    }

    /**
     * Recupera dados de um pedido
     *
     * @param   int     $order_id   Código do pedido
     * @return  array   Retorna dados do pedido
     */
    // consulta
    public function getDataOrdersIntegrations($order_id)
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
                    'orders.ship_time_preview as ship_time',

                    'clients.id as cod_client',
                    'clients.customer_name as name_client',
                    'clients.customer_address as address_client',
                    'clients.addr_num as num_client',
                    'clients.addr_compl as compl_client',
                    'clients.addr_neigh as neigh_client',
                    'clients.addr_city as city_client',
                    'clients.addr_uf as uf_client',
                    'clients.zipcode as cep_client',
                    'clients.phone_1 as phone_client_1',
                    'clients.phone_2 as phone_client_2',
                    'clients.email as email_client',
                    'clients.cpf_cnpj as cpf_cnpj_client',
                    'clients.ie as ie_client',
                    'clients.rg as rg_client',
                    'clients.country as country_client',

                    'orders_item.id as id_iten_product',
                    'orders_item.product_id as id_product',
                    'orders_item.sku as sku_product',
                    'orders_item.name as name_product',
                    'orders_item.un as un_product',
                    'orders_item.qty as qty_product',
                    'orders_item.rate as rate_product',
                    'orders_item.discount as discount_product',
                    'orders_item.variant as variant_product',
                    'orders_item.pesobruto as peso_product',

                    'products.price as price_product',                    

                    'orders_payment.id as id_payment',
                    'orders_payment.parcela as parcela_payment',
                    'orders_payment.data_vencto as vencto_payment',
                    'orders_payment.valor as valor_payment',
                    'orders_payment.forma_desc as forma_payment',
                    
                    'nfes.nfe_num'
                )
            )
            ->from('orders')
            ->join('orders_item', 'orders.id = orders_item.order_id')
            ->join('products', 'products.id = orders_item.product_id')
            ->join('orders_payment', 'orders.id = orders_payment.order_id', 'left')
            ->join('clients', 'orders.customer_id = clients.id', 'left')
            ->join('nfes', 'orders.id = nfes.order_id', 'left')
            ->where(
                array(
                    'orders.store_id'   => $this->store,
                    'orders.id'         => $order_id
                )
            )
            ->get()
            ->result_array();
    }

    /**
     * Recupera o SKU do produto/variação vendido
     *
     * @param   array       $iten   Array com dados do produto vendido
     * @return  false|string        Retorna o sku do produto ou variação, em caso de erro retorna false
     */
    public function getSkuProductVariationOrder($iten)
    {
        if ($iten['variant_product'] == "") return $iten['sku_product'];

        $var = $this->db
            ->get_where('prd_variants',
                array(
                    'prd_id'    => $iten['id_product'],
                    'variant'   => $iten['variant_product']
                )
            )->row_array();

        if (!$var) return false;

        return $var['sku'];
    }

    public function getUrlEccosys($store_id)
    {        
        $dataIntegrationStore = $this->_this->db
                                ->from('api_integrations')
                                ->where('store_id', $store_id)
                                ->get()
                                ->result_array();
        
        if($dataIntegrationStore){
            $credentials = json_decode($dataIntegrationStore[0]['credentials']);
            return $credentials->url_eccosys;
        }
        return null;
    }

    /**
     * Verifica se existe um status como pago, aguardando faturamento ou cancelado para criar o pedido
     *
     * @param   int $orderId    Código do pedido
     * @return  bool            Retorna o status para criação
     */
    public function getOrderOtherThanUnpaid($orderId)
    {
        $query = $this->db
            ->from('orders_to_integration')
            ->where(array(
                'store_id' => $this->store,
                'order_id' => $orderId
            ))
            ->where_in('paid_status', array(3))
            ->get();

        if($query->num_rows() == 0) return false;

        // Remover da fila do não pago
        $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'   => $this->store,
                'order_id'   => $orderId,
                'paid_status'=> 1
            ), 1
        );

        // coloca o próximo status como new_order = 1
        $orderUpdated = $this->db
            ->from('orders_to_integration')
            ->where(
                array(
                    'store_id'  => $this->store,
                    'order_id'  => $orderId,
                    'new_order' => 0
                )
            )
            ->where_in('paid_status', array(3))
            ->order_by('id', 'asc')
            ->get()
            ->row_array();

        return $this->db->where('id', $orderUpdated['id'])->update('orders_to_integration', array('new_order' => 1)) ? true : false;
    }


    /**
     * Cria um novo registro de integração caso o status for 3, aguardar para faturar
     *
     * @param   array   $data   Dados da integração
     * @return  bool            Retorna o status da criação
     */
    public function controlRegisterIntegration($data)
    {
        if ($data['paid_status'] == 3) {
            $idIntegration = $data['id'];

            $arrUpdate = array(
                'new_order' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            );

            $update = $this->db->where(
                array(
                    'id'        => $idIntegration,
                    'store_id'  => $this->store,
                )
            )->update('orders_to_integration', $arrUpdate);

            return $update ? true : false;
        }

        return false;
    }


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
        $ECCOSYS_URL = $this->getUrlEccosys($this->store);
        $url = $ECCOSYS_URL.'/api/estoques/'.$codProduto;
        $data = $dataEstoque;

        $dataOrder  = $this->sendREST($url, $data, 'POST');
        $log_name   = $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($dataOrder['httpcode'] >= 400) {
            $msgError = "Não foi possível lançar estoque do pedido {$orderId}! codProduto={$codProduto}, RETORNO=".json_encode($dataOrder);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "W");

            // formatar mensagens de erro para log integration
            $arrErrors = array();
            $getRetorno = json_decode($dataOrder['content']);

            if(isset($getRetorno->error)){
                $errors = $getRetorno->error;

                if (!is_array($errors)) $errors = (array)$errors;
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

    /**
     * Salvar ID do pedido gerado pelo integrador
     *
     * @param int $order_id Código do pedido na Conecta Lá
     * @param int $id_eccosys  Código do pedido no Eccosys
     */
    public function saveOrderIdIntegration($order_id, $id_eccosys)
    {
        $this->db->where(
            array(
                'id'        => $order_id,
                'store_id'  => $this->store,
            )
        )->update('orders', array('order_id_integration' => $id_eccosys));
    }

    /**
     * Remove o pedido da fila de integração
     *
     * @param   int     $order_id   Código do pedido
     * @return  bool                Retornar o status da exclusão
     */
    public function removeOrderIntegration($order_id)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'  => $this->store,
                'order_id'  => $order_id,
                'new_order' => 1
            ), 1
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
     * Recupera a transportadora que fará o envio do produto
     *
     * @param   string  $company    Empresa para transporte
     * @param   string  $service    Tipo de serviço do transporte
     * @return  array               Retorno com status da transportadora e dados de transporte
     */
    public function getProviderOrder($company, $service, $peso)
    {
        $cnpjTransportadora = null;

        if ($company == "CORREIOS")  // correios
            $cnpjTransportadora = "34028316000103";

        elseif ($company == "Transportadora" && $service == "Conecta Lá") { // romoaldo
            $dataStore = $this->model_stores->getStoresData($this->store);

            switch ($dataStore['addr_uf']) {
                case "SC":
                    $cnpjTransportadora = "05813363000160";
                    break;
                case "SP":
                    $cnpjTransportadora = "21341720000190";
                    break;
                case "MG":
                    $cnpjTransportadora = "86479268000254";
                    break;
                case "RJ":
                case "ES":
                    $cnpjTransportadora = "24566736000351";
                    break;
                default:
                    return null;
            }

        } elseif ($company == "Conecta Lá" && $service == "Jamef") // jamef
            $cnpjTransportadora = "20147617000656";
        elseif ($company == "Bradex" && $service == "Transportadora") // Bradex
            $cnpjTransportadora = "24566736000351";

        $dataProvider = $this->model_providers->getProviderDataForCnpj($cnpjTransportadora);

        if($cnpjTransportadora == null || $dataProvider == null) return null;

        $data['transportadora'] = $dataProvider['razao_social'];
        $data['tipo_frete'] = 'D';
        if ($company == "CORREIOS")
            $data['servico_correios'] = $service;

        $data['peso_bruto'] = number_format($peso, 3, '.', '');
        $data['cnpj'] = $cnpjTransportadora;

        return $data;
    }

    public function buscarStatus($paidStatus){
        // Status do pedido
        switch ($paidStatus) {
            case 1:
                $situacao = "Aguardando Pagamento(Não foi pago ainda)";
                $situacaoEccosys = -1;
                break;
            case 3:
                $situacao = "Aguardando Faturamento"; //(Pedido foi pago, está aguardando ser faturado)
                $situacaoEccosys = 0; //Em Aberto
                break;
/*            case 4:
                $situacao = "Aguardando Coleta/Envio"; //(Aguardando o seller enviar ou transportadora coletar)
                $situacaoEccosys = 3; //Pronto para picking
                break;
            case 5:
                $situacao = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoEccosys = 1;
                break;
            case 6:
                $situacao = "Entregue"; //(Pedido entregue ao cliente)
                $situacaoEccosys = 'D';
                break;
            case 40:
                $situacao = "Aguardando Rastreio"; //(Pedido faturado, aguardando envio de rastreio)
                $situacaoEccosys = 3;
                break;
            case 43:
                $situacao = "Aguardando Coleta/Envio"; //(Pedido com rastreio, aguardando ser coletado/enviado)
                $situacaoEccosys = 3;
                break;
            case 45:
                $situacao = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoEccosys = 1;
                break;
            case 50:
                $situacao = "Aguardando Seller Emitir Etiqueta"; //(Pedido faturado, contratando frete)
                $situacaoEccosys = 3;
                break;
            case 51:
                $situacao = "PLP gerada"; //(Enviar rastreio para o marketplace)
                $situacaoEccosys = 1;
                break;
            case 52:
                $situacao = "Pedido faturado"; //(Enviar NF-e para o marketplace)
                $situacaoEccosys = 1;
                break;
            case 53:
                $situacao = "Aguardando Coleta/Envio"; //(Aguardando pedido ser postado/coletado para rastrear)
                $situacaoEccosys = 3;
                break;
            case 55:
                $situacao = "Em Transporte"; //(Avisaro marketplace que o pedido foi enviado)
                $situacaoEccosys = 1;
                break;
            case 56:
                $situacao = "Processando Nota Fiscal"; //(Processando NF aguardando envio (módulo faturador))
                $situacaoEccosys = 4;
                break;
            case 57:
                $situacao = "Nota Fiscal Com Erro"; //(Problema para faturar o pedido (módulo faturador))
                $situacaoEccosys = 4;
                break;
            case 60:
                $situacao = "Entregue"; //(Avisar ao marketplace que foi entregue)
                $situacaoEccosys = 'D';
                break;
*/
            case 96:
                $situacao = "Cancelado"; //(Cancelado antes de realizar o pagamento)
                $situacaoEccosys = 2;
                break;
            case 95:
            case 97:
                $situacao = "Cancelado"; //(Cancelado após o pagamento)
                $situacaoEccosys = 2;
                break;
/*            case 98:
                $situacao = "Cancelar na Transportadora"; //(Cancelar rastreio na transportadora (não correios))
                $situacaoEccosys = 2;
                break;
            case 99:
                $situacao = "Cancelar no Marketplace"; //(Avisar o cancelamento para o marketplace)
                $situacaoEccosys = 2;
                break;
            case 101:
                $situacao = "Sem Cotação de Frete"; //(Deve fazer a contratação do frete manual (não correios))
                $situacaoEccosys = 0;
                break; */
            default:
                $situacao = 'Não foi encontrato o status';
                $situacaoEccosys = -1;
        }

        $arrRetorno = array('situacao' => $situacao,
                            'situacaoEccosys' => $situacaoEccosys);

        return $arrRetorno;
    }
}