<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration/Tiny/Order/CreateOrder run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";

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
        $this->sendOrders();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Envia os pedidos para serem integrados
     */
    public function sendOrders()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        //recuperar o developer_id para criar o pedido
        $setting_tiny = $this->model_settings->getSettingDatabyName('developer_id_tiny');
        if ($setting_tiny == false) {
            $this->log_data('batch',$log_name,'Falta o cadastro do parametro developer_id_tiny',"E");
            return false;
        }
        $developerIdTiny = $setting_tiny['value'];

        $orders = $this->getOrdersIntegrations();

        foreach ($orders as $orderIntegration) {

            $orderId    = $orderIntegration['order_id'];
            $paidStatus = $orderIntegration['paid_status'];
            $this->setUniqueId($orderId); // define novo unique_id

            // verifica cancelado, para não integrar
            if ($this->getOrderCancel($orderId)) {
                $this->removeAllOrderIntegration($orderId);

                $msgError = "PEDIDO={$orderId} cancelado, não será integrado - ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Pedido {$orderId} cancelado", "<h4>Pedido {$orderId} não será integrado</h4> <ul><li>Pedido cancelado antes de ser realizado o pagamento.</li></ul>", "S");
                continue;
            }

            // Igonoro o status pois ainda não foi pago e não será enviado pro erp
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

            // Status do pedido
            switch ($paidStatus) {
                case 1:
                    $situacao = "aberto";
                    break;
                case 2:
                case 3:
                    $situacao = "aprovado";
                    break;
                default:
                    $msgError = "Não foi encontrado um status válido para criação de pedido. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi possível encontrar uma situação válida para o pedido.</li></ul>", "E");
                    continue 2;
            }

            $newOrder = array('pedido' => array());

            // Dados pedido
            $newOrder['pedido']['data_pedido'] = date('d/m/Y', strtotime($orderMain['date_created']));
//            $newOrder['pedido']['data_prevista'] = date('d/m/Y', strtotime($orderMain['date_created'])); // ver qual data de previsão
            $newOrder['pedido']['id_lista_preco'] = $this->listPrice;
            $newOrder['pedido']['valor_desconto'] = $orderMain['discount_order'];
            $newOrder['pedido']['outras_despesas'] = 0;
            $newOrder['pedido']['numero_ordem_compra'] = $orderMain['order_id'];
//            $newOrder['pedido']['id_vendedor'] = "";
//            $newOrder['pedido']['nome_vendedor'] = "";
//            $newOrder['pedido']['obs'] = "";
            $newOrder['pedido']['obs_internas'] = "Pedido {$sellercenter_name}: {$orderMain['order_id']}";
            $newOrder['pedido']['situacao'] = $situacao;
            $newOrder['pedido']['numero_pedido_ecommerce'] = $orderMain['numero_marketplace'];
            $newOrder['pedido']['ecommerce'] = $sellercenter_name;

//            if ($this->idEcommerce != null && $this->idEcommerce != "")
//                $newOrder['pedido']['id_ecommerce'] = $this->idEcommerce;

            // Cliente
            $newOrder['pedido']['cliente'] = array();
            $newOrder['pedido']['cliente']['codigo'] = $orderMain['id_client'];
            $newOrder['pedido']['cliente']['nome'] = $orderMain['name_client'];
            $newOrder['pedido']['cliente']['nome_fantasia'] = $orderMain['name_client'];
            $newOrder['pedido']['cliente']['tipo_pessoa'] = strlen(preg_replace('/\D/', '', $orderMain['cpf_cnpj_client'])) == 14 ? "J" : "F";
            $newOrder['pedido']['cliente']['cpf_cnpj'] = preg_replace('/\D/', '', $orderMain['cpf_cnpj_client']);
            $newOrder['pedido']['cliente']['ie'] = $orderMain['ie_client'];
            $newOrder['pedido']['cliente']['rg'] = $orderMain['rg_client'];
            $newOrder['pedido']['cliente']['endereco'] = $orderMain['address_client'];
            $newOrder['pedido']['cliente']['numero'] = $orderMain['num_client'];
            $newOrder['pedido']['cliente']['complemento'] = $orderMain['compl_client'];
            $newOrder['pedido']['cliente']['bairro'] = $orderMain['neigh_client'];
            $newOrder['pedido']['cliente']['cep'] = $orderMain['cep_client'];
            $newOrder['pedido']['cliente']['cidade'] = $orderMain['city_client'];
            $newOrder['pedido']['cliente']['uf'] = $orderMain['uf_client'];
            $newOrder['pedido']['cliente']['pais'] = "Brasil";
            $newOrder['pedido']['cliente']['fone'] = $orderMain['phone_client'];
            $newOrder['pedido']['cliente']['email'] = $orderMain['email_client'];
            $newOrder['pedido']['cliente']['atualizar_cliente'] = "S";

            // Entrega
            $newOrder['pedido']['endereco_entrega'] = array();
            $newOrder['pedido']['endereco_entrega']['tipo_pessoa'] = strlen(preg_replace('/\D/', '', $orderMain['cpf_cnpj_client'])) == 14 ? "J" : "F";
            $newOrder['pedido']['endereco_entrega']['cpf_cnpj'] = preg_replace('/\D/', '', $orderMain['cpf_cnpj_client']);
            $newOrder['pedido']['endereco_entrega']['endereco'] = $orderMain['address_order'];
            $newOrder['pedido']['endereco_entrega']['numero'] = $orderMain['num_order'];
            $newOrder['pedido']['endereco_entrega']['complemento'] = $orderMain['compl_order'];
            $newOrder['pedido']['endereco_entrega']['bairro'] = $orderMain['neigh_order'];
            $newOrder['pedido']['endereco_entrega']['cep'] = $orderMain['cep_order'];
            $newOrder['pedido']['endereco_entrega']['cidade'] = $orderMain['city_order'];
            $newOrder['pedido']['endereco_entrega']['uf'] = $orderMain['uf_order'];
            $newOrder['pedido']['endereco_entrega']['fone'] = $orderMain['phone_order'];
            $newOrder['pedido']['endereco_entrega']['nome_destinatario'] = $orderMain['name_order'];
//            $newOrder['pedido']['endereco_entrega']['ie'] = $orderMain['ie_client'];

            // Produtos
            $newOrder['pedido']['itens'] = array();
            $arrItem = array();
            foreach ($order as $iten) {
                if (in_array($iten['id_iten_product'], $arrItem)) continue;

                $sku = $this->getSkuProductVariationOrder($iten);

                // não encontrou o SKU
                if (!$sku) {
                    $msgError = "Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']} ID Produto {$sellercenter_name}={$iten['id_product']}.</li></ul>", "E");
                    continue 2;
                }

                // Estoque zerado
                /*$stockProduct = $this->getStockProduct($iten);
                if ($stockProduct <= 0) {
                    $msgError = "Produto sem estoque para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>O produto não tem estoque para integrar! SKU={$iten['sku_product']} ID Produto {$sellercenter_name}={$iten['id_product']}.</li></ul>", "E");
                    continue 2;
                }*/

                array_push($arrItem, $iten['id_iten_product']);
                array_push($newOrder['pedido']['itens'],
                    array(
                        'item' => array(
                            'codigo'                => trim($sku),
                            'descricao'             => trim($iten['name_product']),
                            'unidade'               => $iten['un_product'],
                            'quantidade'            => $iten['qty_product'],
                            'valor_unitario'        => $iten['rate_product'] + $iten['discount_product'],
//                            'aliquota_comissao'     => "",
//                            'informacao_adicional'  => ""
                        )
                    )
                );
            }

            // Marcadores
            $newOrder['pedido']['marcadores'] = array();
            array_push( $newOrder['pedido']['marcadores'],
                array(
                    'marcador' => array(
//                        'id'        => "",
                        'descricao' => $sellercenter_name,
                    )
                )
            );

            // Pagamento
            $newOrder['pedido']['forma_pagamento'] = "dinheiro";
            $newOrder['pedido']['meio_pagamento'] = "MarketPlace";

            $newOrder['pedido']['parcelas'] = array();
            /*if ($orderMain['id_payment']) {
                $arrPayment = array();
                foreach ($order as $payment) {
                    if (in_array($payment['id_payment'], $arrPayment)) continue;

                    array_push($arrPayment, $payment['id_payment']);
                    array_push($newOrder['pedido']['parcelas'],
                        array(
                            'parcela' => array(
//                                'dias' => "",
                                'data' => date('d/m/Y', strtotime($payment['vencto_payment'])),
                                'valor' => $payment['valor_payment'],
//                            'obs' => "",
//                            'destino' => "",
                                'forma_pagamento' => "dinheiro",
//                            'meio_pagamento' => ""
                            )
                        )
                    );
                }
            } else {*/
                array_push($newOrder['pedido']['parcelas'],
                    array(
                        'parcela' => array(
//                            'dias' => 0,
                            'data' => date('d/m/Y'),
                            'valor' => $orderMain['gross_amount'],
//                            'obs' => "",
//                            'destino' => "",
                            'forma_pagamento' => "dinheiro",
//                            'meio_pagamento' => ""
                        )
                    )
                );
            //}

            // Transporte
            $newOrder['pedido']['nome_transportador'] = "";
            $newOrder['pedido']['frete_por_conta'] = "D";
            $newOrder['pedido']['valor_frete'] = $orderMain['total_ship'];
            $newOrder['pedido']['forma_envio'] = $orderMain['ship_company_preview'] == "CORREIOS" ? "C" : "T";
            $newOrder['pedido']['forma_frete'] = $orderMain['ship_company_preview'] == "CORREIOS" ? $orderMain['ship_service_preview'] : "";;

            $orderEncode = json_encode($newOrder);

            $url        = 'https://api.tiny.com.br/api2/pedido.incluir.php';
//            $data       = "&pedido={$orderEncode}&developer_id={$developerIdTiny}";
            $data       = "&pedido={$orderEncode}&Developer-Id={$developerIdTiny}";
            $dataOrder  = json_decode($this->sendREST($url, $data));

            if ($dataOrder->retorno->status != "OK") {
                $msgError = "Não foi possível integrar o pedido {$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");

                // formatar mensagens de erro para log integration
                $arrErrors = array();
                $errors = $dataOrder->retorno->registros->registro->erros ?? $dataOrder->retorno->erros ?? array();
                if (!is_array($errors)) {
                    $errors = (array)$errors;
                }
                foreach ($errors as $error) {
                    $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                    array_push($arrErrors, $msgErrorIntegration);
                }
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                continue;
            }

            if (!isset($dataOrder->retorno->registros->registro->id)) {

                $msgError = "Não foi possível obter o código do pedido integrado. PEDIDO={$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>Não foi possível recuperar o código do pedido integrado para gravar. Contate o suporte!</li></ul>", "E");
                continue;
            }
            $idTiny = $dataOrder->retorno->registros->registro->id;
            $numeroTiny = $dataOrder->retorno->registros->registro->numero;

            // Lançar estoque do pedido
            $this->PostOrderStock($idTiny, $orderId);

            $this->saveOrderIdIntegration($orderMain['order_id'], $idTiny);
            if ($paidStatus != 3)
                $this->removeOrderIntegration($orderMain['order_id']);
            $this->controlRegisterIntegration($orderIntegration);
            $this->log_integration("Pedido {$orderId} integrado", "<h4>Novo pedido integrado com sucesso</h4> <ul><li>O pedido {$orderMain['order_id']}, foi criado na Tiny com o código {$numeroTiny} e ID {$idTiny}</li></ul>", "S");

            $this->log_data('batch', $log_name, "Pedido {$orderMain['order_id']} integrado com sucesso! enviado=" . json_encode($newOrder) . ' retorno_tiny='.json_encode($dataOrder), "I");
            echo "Pedido {$orderMain['order_id']} integrado com sucesso com o código {$idTiny}!\n";
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
            ->where_in('paid_status', array(1, 2, 3))
            ->get()
            ->result_array();
    }

    /**
     * Recupera dados de um pedido
     *
     * @param   int     $order_id   Código do pedido
     * @return  array               Retorna dados do pedido
     */
    public function getDataOrdersIntegrations($order_id)
    {
        return $this->db
            ->select(
                array(
                    'orders.id as order_id',
                    'orders.numero_marketplace as numero_marketplace',
                    'orders.customer_name as name_order',
                    'orders.customer_address as address_order',
                    'orders.customer_phone as phone_order',
                    'orders.date_time as date_created',
                    'orders.gross_amount as gross_amount',
                    'orders.discount as discount_order',
                    'orders.customer_address_num as num_order',
                    'orders.customer_address_compl as compl_order',
                    'orders.customer_address_neigh as neigh_order',
                    'orders.customer_address_city as city_order',
                    'orders.customer_address_uf as uf_order',
                    'orders.customer_address_zip as cep_order',
                    'orders.customer_reference as reference_order',
                    'orders.customer_id as id_client',
                    'orders.total_ship as total_ship',
                    'orders.ship_company_preview as ship_company_preview',
                    'orders.ship_service_preview as ship_service_preview',
                    'clients.customer_name as name_client',
                    'clients.customer_address as address_client',
                    'clients.addr_num as num_client',
                    'clients.addr_compl as compl_client',
                    'clients.addr_neigh as neigh_client',
                    'clients.addr_city as city_client',
                    'clients.addr_uf as uf_client',
                    'clients.zipcode as cep_client',
                    'clients.phone_1 as phone_client',
                    'clients.email as email_client',
                    'clients.origin as origin_client',
                    'clients.cpf_cnpj as cpf_cnpj_client',
                    'clients.ie as ie_client',
                    'clients.rg as rg_client',
                    'orders_item.id as id_iten_product',
                    'orders_item.product_id as id_product',
                    'orders_item.sku as sku_product',
                    'orders_item.name as name_product',
                    'orders_item.un as un_product',
                    'orders_item.qty as qty_product',
                    'orders_item.rate as rate_product',
                    'orders_item.discount as discount_product',
                    'orders_item.variant as variant_product',
                    'orders_payment.id as id_payment',
                    'orders_payment.parcela as parcela_payment',
                    'orders_payment.data_vencto as vencto_payment',
                    'orders_payment.valor as valor_payment',
                    'orders_payment.forma_desc as forma_payment'
                )
            )
            ->from('orders')
            ->join('orders_item', 'orders.id = orders_item.order_id')
            ->join('orders_payment', 'orders.id = orders_payment.order_id', 'left')
            ->join('clients', 'orders.customer_id = clients.id', 'left')
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

    /**
     * Salvar ID do pedido gerado pelo integrador
     *
     * @param int $order_id Código do pedido na Conecta Lá
     * @param int $id_tiny  Código do pedido na Tiny
     */
    public function saveOrderIdIntegration($order_id, $id_tiny)
    {
        $this->db->where(
            array(
                'id'        => $order_id,
                'store_id'  => $this->store,
            )
        )->update('orders', array('order_id_integration' => $id_tiny));
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

        // coloca o próximo status como new_order=1
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
     * Recupera dados de um produto
     *
     * @param int $idProducts ID do produto
     * @return mixed
     */
    public function getDataProduct($idProducts)
    {
        return $this->db
                ->get_where('products',
                    array(
                        'store_id' => $this->store,
                        'id' => $idProducts
                    )
                )
                ->row_array();
    }

    /**
     * Lançar estoque do pedido inserido
     *
     * @param $idTiny
     * @param $orderId
     * @throws Exception
     */
    private function PostOrderStock($idTiny, $orderId)
    {
        $url        = 'https://api.tiny.com.br/api2/pedido.lancar.estoque.php';
        $data       = "&id={$idTiny}";
        $dataOrder  = json_decode($this->sendREST($url, $data));
        $log_name   = $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($dataOrder->retorno->status != "OK") {
            $msgError = "Não foi possível lançar estoque do pedido {$orderId}! idTiny={$idTiny}, RETORNO=".json_encode($dataOrder);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "W");

            // formatar mensagens de erro para log integration
            $arrErrors = array();
            $errors = $dataOrder->retorno->erros;
            if (!is_array($errors)) $errors = (array)$errors;
            foreach ($errors as $error) {
                $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                array_push($arrErrors, $msgErrorIntegration);
            }
            if (isset($arrErrors[0]) && $arrErrors[0] != 'Estoque já lançado.')
                $this->log_integration("Erro para lançar estoque do pedido {$orderId}", "<h4>Não foi possível lançar o estoque do pedido {$orderId} na Tiny</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
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
}
