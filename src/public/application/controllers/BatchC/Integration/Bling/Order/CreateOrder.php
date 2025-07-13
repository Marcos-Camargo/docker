<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration/Bling/Order/CreateOrder run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Bling/Main.php";

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

        $this->load->model('model_settings');

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

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "E");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $nameSellerCenter = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if (!$nameSellerCenter) $nameSellerCenter = 'Conecta Lá';

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
                $this->log_data('batch', $log_name, $msgError, "W");
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
                $this->log_data('batch', $log_name, $msgError, "E");
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

            // Recupera a forma de pagamento
            // Por enquanto buscanco apenas por dinheiro
            $url    = 'https://bling.com.br/Api/v2/formaspagamento';
            $data   = "&filters=codigoFiscal[1]";
            $dataPayments = $this->sendREST($url, $data);

            if ($dataPayments['httpcode'] != 200) {
                if ($dataPayments['httpcode'] != 504 && $dataPayments['httpcode'] != 401) continue;

                echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataPayments) . "\n";
                $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataPayments), "W");
                $this->log_integration("Erro para consultar as formas de pagamento", "Não foi possível consultar a listagem das formas de pagamento!", "E");
                continue;
            }
            $contentPayment = json_decode($dataPayments['content']);
            $formPayment = $contentPayment->retorno->formaspagamento[0]->formapagamento->id ?? '';

            // Inicia o pedido
//            $newOrder = array('pedido' => array());

            // Dados pedido
            $newOrder['data'] = date('d/m/Y', strtotime($orderMain['date_created']));
//            $newOrder['data_saida'] = date('d/m/Y', strtotime($orderMain['date_created']));
//            $newOrder['data_prevista'] = date('d/m/Y', strtotime($orderMain['date_created']));
            //$newOrder['numero'] = $orderMain['order_id'];
            $newOrder['numero_loja'] = $orderMain['order_id'];
            $newOrder['loja'] = $this->multiStore;
            $newOrder['vlr_frete'] = $orderMain['total_ship'];
            //            $newOrder['obs'] = "";
            $newOrder['obs_internas'] = "Pedido {$nameSellerCenter}: {$orderMain['order_id']}";
            //            $newOrder['numeroOrdemCompra'] = "";
            //            $newOrder['outrasDespesas'] = "";
            //            $newOrder['situacao'] = $situacao;
            // $newOrder['vlr_desconto'] = $orderMain['discount_order'];
            $newOrder['vlr_desconto'] = 0;
            $newOrder['idFormaPagamento'] = $formPayment;

            // Cliente
            $newOrder['cliente'] = array();
//            $newOrder['cliente']['id'] = 0; // id do cliente dentro do bling
            $newOrder['cliente']['nome'] = $orderMain['name_client'];
            $newOrder['cliente']['tipoPessoa'] = strlen(preg_replace('/\D/', '', $orderMain['cpf_cnpj_client'])) == 14 ? "J" : "F";
            $newOrder['cliente']['cpf_cnpj'] = preg_replace('/\D/', '', $orderMain['cpf_cnpj_client']);
            $newOrder['cliente']['ie'] = $orderMain['ie_client'];
            $newOrder['cliente']['rg'] = $orderMain['rg_client'];
//            $newOrder['cliente']['contribuinte'] = ""; // 1 - Contribuinte do ICMS, 2 - Contribuinte isento do ICMS ou 9 - Não contribuinte
            $newOrder['cliente']['endereco'] = $orderMain['address_order'];
            $newOrder['cliente']['numero'] = $orderMain['num_order'];
            $newOrder['cliente']['complemento'] = $orderMain['complement_order'];
            $newOrder['cliente']['bairro'] = $orderMain['neigh_order'];
            $newOrder['cliente']['cep'] = $orderMain['cep_order'];
            $newOrder['cliente']['cidade'] = $orderMain['city_order'];
            $newOrder['cliente']['uf'] = $orderMain['uf_order'];
            $newOrder['cliente']['fone'] = $orderMain['phone_order'];
            $newOrder['cliente']['celular'] = $orderMain['phone_client_1'];
            $newOrder['cliente']['email'] = $orderMain['email_client'];

            // Produtos
            $newOrder['itens']['item'] = array();
            $arrItem = array();
            $pesoTransporte = 0;
            foreach ($order as $iten) {
                if (in_array($iten['id_iten_product'], $arrItem)) continue;

                $sku = $this->getSkuProductVariationOrder($iten);

                // não encontrou o SKU
                if (!$sku) {
                    $msgError = "Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']} ID Produto ConectaLá={$iten['id_product']}.</li></ul>", "E");
                    continue;
                }

                // Estoque zerado
                /*$stockProduct = $this->getStockProduct($iten);
                if ($stockProduct <= 0) {
                    $msgError = "Produto sem estoque para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>O produto não tem estoque para integrar! SKU={$iten['sku_product']} ID Produto ConectaLá={$iten['id_product']}.</li></ul>", "E");
                    continue 2;
                }*/
                array_push($arrItem, $iten['id_iten_product']);
                $price_item=number_format((float)$iten['rate_product'] + (float)$iten['discount_product'], 2, '.', '');
                $discont_percent_to_item=floatval($iten['discount_product'])/floatval($price_item);
                $discont_percent_to_item=number_format($discont_percent_to_item*100,2,'.','');
                array_push($newOrder['itens']['item'],
                    array(
                        'codigo'        => trim($sku),
                        'descricao'     => trim($iten['name_product']),
                        'un'            => $iten['un_product'],
                        'qtde'          => $iten['qty_product'],
                        'vlr_unit'      => $price_item,
                        'vlr_desconto'  => $discont_percent_to_item,
                    )
                );
                $pesoTransporte += (float)$iten['peso_product'];
            }

            // Transporte
            $provider = $this->getProviderOrder($orderMain['ship_company'], $orderMain['ship_service'], $pesoTransporte);
//            if (!$provider) {
//                $msgError = "Erro para encontrar a transportadora PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . ', TRANSPORTADORA='.json_encode($provider);
//                echo "{$msgError}\n";
//                $this->log_data('batch', $log_name, $msgError, "W");
//                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId} <br> <ul><li>{$provider['data']}</li></ul>", "E");
//                continue;
//            }
            if ($provider)
                $newOrder['transporte'] = $provider;
            // Transporte -> dados_etiqueta
            $newOrder['transporte']['dados_etiqueta'] = array(
                'nome'          => $orderMain['name_order'],
                'cep'           => $orderMain['cep_order'],
                'endereco'      => $orderMain['address_order'],
                'numero'        => $orderMain['num_order'],
                'complemento'   => $orderMain['complement_order'],
                'bairro'        => $orderMain['neigh_order'],
                'municipio'     => $orderMain['city_order'],
                'uf'            => $orderMain['uf_order'],
            );

            $newOrder['parcelas']['parcela'] = array();
            if ($orderMain['id_payment']) {
                $arrPayment = array();
                foreach ($order as $payment) {
                    if (in_array($payment['id_payment'], $arrPayment)) continue;

                    array_push($arrPayment, $payment['id_payment']);
                    array_push($newOrder['parcelas']['parcela'],
                        array(
//                          'dias' => "",
                            'data' => date('d/m/Y', strtotime($payment['vencto_payment'])),
                            'vlr' => $payment['valor_payment'],
//                          'obs' => "",
                            'forma_pagamento' => array(
                                'id' => $formPayment
                            )
                        )
                    );
                }
            } else {
                $newOrder['parcelas']['parcela'] = array(
//                  'dias' => 0,
                    'data' => date('d/m/Y'),
                    'vlr' => $orderMain['gross_amount'],
//                  'obs' => "",
//                  'destino' => "",
                    'forma_pagamento' => array(
                        'id' => $formPayment
                    )
                );
            }

            $orderXml = $this->arrayToXml($newOrder);
            // remove o caracter &
            $orderXml = str_replace('&amp;', '', $orderXml);

            $url        = 'https://bling.com.br/Api/v2/pedido';
            $data       = "&gerarnfe=false&xml=".$orderXml;
            $dataOrder  = $this->sendREST($url, $data, 'POST');
            $contentPayment = json_decode($dataOrder['content']);

            if ($dataOrder['httpcode'] != 201 && $dataOrder['httpcode'] != 200) {
                if ($dataOrder['httpcode'] != 504 && $dataOrder['httpcode'] != 401) {
                    echo "Não foi possível integrar o pedido {$orderMain['order_id']}\n
                            HTTP={$dataOrder['httpcode']} \n
                            RESPONSE_ORDER=".json_encode($contentPayment)."\n
                            ORDER=" . json_encode($order) . " \n
                            RETORNO=".json_encode($dataOrder);

                    $messageError = $contentPayment->retorno->erros->erro->msg ?? json_encode($contentPayment);

                    if (!is_string($messageError)) {
                        $messageError = json_encode($messageError);
                    }

                    $this->log_integration("Erro para integrar o pedido $orderId", "<h4>Não foi possível integrar o pedido $orderId</h4> <p>$messageError</p>", "E");
                    continue;
                }

                $msgError = "Não foi possível integrar o pedido {$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <p>Ocorreu um problema inesperado, em breve tentaremos integrar novamente.</p> <br><br>" . json_encode($contentPayment), "E");
                continue;
            }

            if ($dataOrder['httpcode'] == 200) {
                $msgError = "Não foi possível integrar o pedido {$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");

                // formatar mensagens de erro para log integration
                $arrErrors = array('Erro desconhecido'.(isset($dataOrder["content"])?$dataOrder["content"]:''));
                $errors = $contentPayment->retorno->erros ?? null;
                if ($errors) {
                    if (!is_array($errors)) $errors = (array)$errors;
                    foreach ($errors as $error) {
                        $msgErrorIntegration = $error->erro->msg ?? "Erro desconhecido. ".(isset($dataOrder["content"])?$dataOrder["content"]:'');
                        array_push($arrErrors, $msgErrorIntegration);
                    }
                }
                if (is_string($contentPayment)) $arrErrors = [$contentPayment];
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                continue;
            }

            $idBling = $contentPayment->retorno->pedidos[0]->pedido->idPedido;
            if ($idBling == null) {
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>Houve um conflito de pedidos no bling.</li></ul>", "E");
                continue;
            }
            $numeroBling = $contentPayment->retorno->pedidos[0]->pedido->numero;

            // salva id da integração do bling
            $this->saveOrderIdIntegration($orderMain['order_id'], $numeroBling);
            // remove da fila de integração
            if ($paidStatus != 3)
                $this->removeOrderIntegration($orderMain['order_id']);

            // controlador da lista para pedidos que chegaram como aguardando faturamento ou cancelado
            $this->controlRegisterIntegration($orderIntegration);
            // atualiza situação do pedido para Em aberto
            $this->updateStatusOrder($numeroBling, $orderMain['order_id']);
            // atualizar pedido para atendido
            $this->log_integration("Pedido {$orderId} integrado", "<h4>Novo pedido integrado com sucesso</h4> <ul><li>O pedido {$orderMain['order_id']}, foi criado na Bling com o código {$numeroBling} e ID {$idBling}</li></ul>", "S");

            $this->log_data('batch', $log_name, "Pedido {$orderMain['order_id']} integrado com sucesso! enviado=" . json_encode($newOrder) . ' retorno_bling='.json_encode($dataOrder), "I");
            echo "Pedido {$orderMain['order_id']} integrado com sucesso com o código {$idBling}!\n";
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
                    'orders.customer_address_num as num_order',
                    'orders.customer_address_compl as complement_order',
                    'orders.customer_address_neigh as neigh_order',
                    'orders.customer_address_city as city_order',
                    'orders.customer_address_uf as uf_order',
                    'orders.customer_address_zip as cep_order',
                    'orders.customer_phone as phone_order',
                    'orders.date_time as date_created',
                    'orders.gross_amount as gross_amount',
                    'orders.discount as discount_order',
                    'orders.total_ship as total_ship',
                    'orders.ship_company_preview as ship_company',
                    'orders.ship_service_preview as ship_service',
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
                    'orders_item.pesobruto as peso_product',
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
     * @param int $orderId  Código do pedido na Conecta Lá
     * @param int $idBling  Código do pedido na Bling
     */
    public function saveOrderIdIntegration($orderId, $idBling)
    {
        $this->db->where(
            array(
                'id'        => $orderId,
                'store_id'  => $this->store,
            )
        )->update('orders', array('order_id_integration' => $idBling));
    }

    /**
     * Cria um novo registro de integração caso o status for 3, aguardar para faturar
     *
     * @param   array   $data   Dados da integração
     * @return  bool            Retorna o status da criação
     */
    public function controlRegisterIntegration($data)
    {
        $response = false;

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

            $response = $update ? true : false;
        }

        return $response;
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
                    'order_id'   => $orderId,
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
     * @param   int     $status     Status do item na lista para não remover
     * @return  bool                Retornar o status da exclusão
     */
    public function removeAllOrderIntegration($orderId, $status = null)
    {
        $where = array(
            'store_id'  => $this->store,
            'order_id'  => $orderId
        );

        if ($status) $where['paid_status !='] = $status;

        return $this->db->delete(
            'orders_to_integration',
            $where
        ) ? true : false;
    }

    /**
     * Recupera a transportador que fdará o envio do produto
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

        return $data;
    }

    /**
     * Atualiza pedido para o status correto após a criação
     *
     * @param   int $id         Código do pedido no Bling
     * @param   int $order_id   Código do pedido na Conecta Lá
     */
    public function updateStatusOrder($id, $order_id)
    {
        $updateOrder['idSituacao'] = 6;
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $orderXml = $this->arrayToXml($updateOrder);

        $url        = 'https://bling.com.br/Api/v2/pedido/' . $id;
        $data       = "&xml={$orderXml}";
        $dataOrder  = $this->sendREST($url, $data, 'PUT');
        $contentUpdate = json_decode($dataOrder['content']);

        if ($dataOrder['httpcode'] != 200) {
            $msgError = "Não foi possível atualizar o status do pedido {$order_id}! RETORNO=".json_encode($dataOrder);
            echo $msgError . "\n";
            $this->log_data('batch', $log_name, $msgError, "E");
        } elseif (isset($contentUpdate->retorno->erros)) {
            $msgError = "Não foi possível atualizar o status do pedido {$order_id}! RETORNO=".json_encode($dataOrder);
            echo $msgError . "\n";
            $this->log_data('batch', $log_name, $msgError, "E");
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
