<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration/Bling/Order/CreateOrder run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Vtex/Main.php";

/**
 * @property Model_stores $model_stores
 * @property Model_settings $model_settings
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_promotions $model_promotions
 */

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

        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_promotions');
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

        $sellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $sellerCenter = $sellerCenter['value'];

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

                echo "Pedido $orderId Chegou não pago, vou ignorar\n";
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

            $store = $this->model_stores->getStoresData($this->store);

            $logistic = $this->calculofrete->getLogisticStore(array(
                'freight_seller' 		=> $store['freight_seller'],
                'freight_seller_type'   => $store['freight_seller_type'],
                'store_id'				=> $store['id']
            ));

            // Inicia o pedido
            $newOrder = array();

            // Dados pedido
            /*if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') $newOrder['marketplaceOrderId'] = $orderMain['order_id'];
            else $newOrder['marketplaceOrderId'] = date('dms');*/
            $newOrder['marketplaceOrderId'] = $orderMain['order_id'];

            $newOrder['marketplaceServicesEndpoint'] = base_url('Api/Integration/Vtex/ServicesOrder');
            $newOrder['marketplacePaymentValue'] = str_replace('.', '', number_format(($sellerCenter === 'somaplace' ? $orderMain['net_amount'] : $orderMain['gross_amount']), 2, '.', ''));

            // Cliente
            $nameComplet = explode(" ", trim($orderMain['name_client']));
            if (count($nameComplet) <= 1) {
                $nameComplet = explode(" ", trim($orderMain['name_order']));
            }
            // Separa nome do sobrenome
            $lastName = $nameComplet[count($nameComplet)-1];
            unset($nameComplet[count($nameComplet)-1]);
            $firstName =  implode(" ", $nameComplet);

            $newOrder['clientProfileData'] = array();
            $newOrder['clientProfileData']['id'] = 'clientProfileData';
            $newOrder['clientProfileData']['email'] = empty($orderMain['email_client']) ? 'client@vtex.com' : $orderMain['email_client'];
            $newOrder['clientProfileData']['firstName'] = $firstName;
            $newOrder['clientProfileData']['lastName'] = $lastName;
            $newOrder['clientProfileData']['document'] = preg_replace('/\D/', '', $orderMain['cpf_cnpj_client']);
            $newOrder['clientProfileData']['documentType'] = strlen(preg_replace('/\D/', '', $orderMain['cpf_cnpj_client'])) == 14 ? "cnpj" : "cpf";
            $newOrder['clientProfileData']['phone'] = empty($orderMain['phone_client_1']) ? preg_replace('/\D/', '', $orderMain['phone_client_2']) : preg_replace('/\D/', '', $orderMain['phone_client_1']);
            $newOrder['clientProfileData']['corporateName'] = null;
            $newOrder['clientProfileData']['tradeName'] = null;
            $newOrder['clientProfileData']['corporateDocument'] = null;
            $newOrder['clientProfileData']['stateInscription'] = null;
            $newOrder['clientProfileData']['corporatePhone'] = null;
            $newOrder['clientProfileData']['isCorporate'] = false;
            $newOrder['clientProfileData']['userProfileId'] = null;

            // endereço de entrega e logistica
            $newOrder['shippingData'] = array();
            $newOrder['shippingData']['isFOB'] = true; // fob=true ==> transporte por conta do marketplace
            $newOrder['shippingData']['id'] = 'shippingData';
            $newOrder['shippingData']['address'] = array();
            $newOrder['shippingData']['address']['addressType']     = 'Residencial';
            $newOrder['shippingData']['address']['receiverName']    = $orderMain['name_client'];
            $newOrder['shippingData']['address']['postalCode']      = preg_replace('/\D/', '', $orderMain['order_address_zip']);
            $newOrder['shippingData']['address']['city']            = $orderMain['order_address_city'];
            $newOrder['shippingData']['address']['state']           = $orderMain['order_address_uf'];
            $newOrder['shippingData']['address']['country']         = 'BRA';
            $newOrder['shippingData']['address']['street']          = $orderMain['address_order'];
            $newOrder['shippingData']['address']['number']          = $orderMain['order_address_num'];
            $newOrder['shippingData']['address']['neighborhood']    = $orderMain['order_address_neigh'];
            $newOrder['shippingData']['address']['complement']      = $orderMain['order_address_compl'];
            $newOrder['shippingData']['address']['reference']       = $orderMain['order_reference'];
            $newOrder['shippingData']['address']['geoCoordinates']  = [];

            // Logistica
            $newOrder['shippingData']['logisticsInfo'] = array();
            // Produtos
            $newOrder['items']  = array();
            $arrItem            = array();
            $arrItemFormat      = array();
            $countIndex         = 0;
            foreach ($order as $iten) {
                if (in_array($iten['id_iten_product'], $arrItem)) {
                    continue;
                }

                $skuERP = $this->getSkuERP($iten);

                // não encontrou o SKU
                if (!$skuERP) {
                    $msgError = "Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']}.</li></ul>", "E");
                    continue 2;
                }

                if (!$this->getProductInStock($skuERP['sku'], $iten['qty_product'])) {
                    $msgError = "Produto sem estoque na integradora! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi encontrado estoque suficiente para envio do pedido! SKU={$skuERP['sku']}.</li></ul>", "E");
                    continue 2;
                }

                // Estoque zerado
                /*$stockProduct = $this->getStockProduct($iten);
                if ($stockProduct <= 0) {
                    $msgError = "Produto sem estoque para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>O produto não tem estoque para integrar! SKU={$iten['sku_product']}.</li></ul>", "E");
                    continue 2;
                }*/

                array_push($arrItem, $iten['id_iten_product']);

                if (array_key_exists($skuERP['sku'], $arrItemFormat)) {
                    $arrItemFormat[$skuERP['sku']]['quantity'] += (int)$iten['qty_product'];
                    continue;
                } else {
                    $priceTags = array();
                    if ($iten['discount_product'] > 0) {
                        $priceTags = array(
                            array(
                                "name"          => "Desconto marketplace",
                                "value"         => -(int)str_replace('.', '', number_format(($iten['discount_product'] * (int)$iten['qty_product']), 2, '.', '')),
                                "isPercentual"  => false,
                                "identifier"    => null,
                                "rawValue"      => -(float)number_format(($iten['discount_product'] * (int)$iten['qty_product']), 2, '.', ''),
                                "rate"          => null,
                                "jurisCode"     => null,
                                "jurisType"     => null,
                                "jurisName"     => null
                            )
                        );
                    }
                    $price = $iten['rate_product'] + $iten['discount_product'];

                    // criar pricetags da campanha [TEMPORÁRIO 26/11/21]
                    // se tem promoção e está na campanha, subtrair o valor da promoção de orders_item.discount.
                    /*$campaigns = $this->model_campaigns_v2->getPriceOrderCampaignByOrderProductIntto($orderMain['order_id'], $iten['id_product'], $orderMain['int_to_order']);

                    // existe promoção
                    if (count($campaigns)) {
                        $priceTags = array(); // limpo priceTags para criar novas, pois existe campanha no produto
                        $discountProduct = $iten['discount_product'];
                        // verificar se produto estava na promoção também
                        $promotion = $this->model_promotions->getProductPromotionByProductCreated($iten['id_product'], $iten['variant_product'], $orderMain['date_created'], $orderMain['int_to_order']);
                        // existiu promoção para esse produto

                        if ($promotion) {
                            $promotion_price = $promotion['promotion_price'];
                            $product_price = $promotion['product_price'];
                            $discount_promotion = $product_price - $promotion_price;
                            if ($discount_promotion < 0) {
                                $discount_promotion = 0;
                            }

                            // desconto correto sem a promoção, pois existe campanha ativa
                            $discountProduct -= $discount_promotion;
                        }

                        if ($discountProduct != 0) {
                            array_push($priceTags, array(
                                "name"          => "Desconto marketplace",
                                "value"         => -(int)str_replace('.', '', number_format($discountProduct * $iten['qty_product'], 2, '.', '')),
                                "isPercentual"  => false,
                                "identifier"    => null,
                                "rawValue"      => -(float)number_format($discountProduct * $iten['qty_product'], 2, '.', ''),
                                "rate"          => null,
                                "jurisCode"     => null,
                                "jurisType"     => null,
                                "jurisName"     => null
                            ));
                        }

                        // soma o desconto das campanhas
                        $discountCampaign = 0;
                        foreach ($campaigns as $campaign) {
                            $discountCampaign += ($campaign['product_price'] - $campaign['product_promotional_price']); // 3.72
                        }
                        $discountCampaign = (float)number_format($discountCampaign, 2, '.', '');

                        // soma o desconto das campanhas no desconto total
                        $discountProduct += $discountCampaign; // 3.72 + 0.10
                        $discountProduct = (float)number_format($discountProduct, 2, '.', '');

                        $price = $iten['rate_product'] + $discountProduct; // 70.58 + 3.73
                        $price = (float)number_format($price, 2, '.', '');

                        array_push($priceTags, array(
                            "name"          => "Desconto Seller Center",
                            "value"         => -(int)str_replace('.', '', number_format($discountCampaign * $iten['qty_product'], 2, '.', '')),
                            "isPercentual"  => false,
                            "identifier"    => null,
                            "rawValue"      => -(float)number_format($discountCampaign * $iten['qty_product'], 2, '.', ''),
                            "rate"          => null,
                            "jurisCode"     => null,
                            "jurisType"     => null,
                            "jurisName"     => null
                        ));
                    }*/

                    $price      = number_format($price, 2, '.', '');
                    $priceSku   = number_format($skuERP['price'], 2, '.', '');

                    if ($price != $priceSku) {
                        $discountDivergenceCampanha = $skuERP['price'] - $price;
                        $priceWithQuantity = $discountDivergenceCampanha * (int)$iten['qty_product']; // pricetags vai com desconto

                        $arrPriceTags = array(
                            "isPercentual"  => false,
                            "identifier"    => null,
                            "rate"          => null,
                            "jurisCode"     => null,
                            "jurisType"     => null,
                            "jurisName"     => null
                        );

                        if ($discountDivergenceCampanha < 0) {
                            $priceWithQuantity *= -1;
                            $price -= ($discountDivergenceCampanha*(-1));
                            $arrPriceTags["name"]       = "Acréscimo Divergência Preço Atualizado";
                            $arrPriceTags["value"]      = (int)str_replace('.', '', number_format($priceWithQuantity, 2, '.', ''));
                            $arrPriceTags["rawValue"]   = (float)number_format($priceWithQuantity, 2, '.', '');
                        } else {
                            $price += $discountDivergenceCampanha;
                            $arrPriceTags["name"]       = "Desconto Divergência Preço Atualizado";
                            $arrPriceTags["value"]      = -(int)str_replace('.', '', number_format($priceWithQuantity, 2, '.', ''));
                            $arrPriceTags["rawValue"]   = -(float)number_format($priceWithQuantity, 2, '.', '');
                        }
                        array_push($priceTags, $arrPriceTags);
                    }

                    $priceFormat = (int)str_replace('.', '', number_format($price, 2, '.', ''));
                    $arrItemFormat[$skuERP['sku']] = array(
                        'id'                => $skuERP['sku'],
                        'quantity'          => (int)$iten['qty_product'],
                        'seller'            => "1", // seller 1 é o principal VTEX
                        'commission'        => 0,
                        'freightCommission' => 0,
                        'price'             => $priceFormat,
                        'bundleItems'       => [],
                        'attachments'       => [],
                        'priceTags'         => $priceTags,
                        'measurementUnit'   => null,
                        'rewardValue'       => 0,
                        'isGift'            => false,
                        "itemAttachment"    => [
                            "name"      => null,
                            "content"   => []
                        ]
                    );
                }

                /*
                 Sugestão de envio de pedido sem identificação da doca. chamado https://support.vtex.com/hc/pt-br/requests/450041
                 1) Enviar o 'selectedSLA' como 'null'
                    Nesse caso o marketplace não possui uma doca ou estoque acordado com o seller para ser descontado.
                    Ao passar o objeto dessa forma a VTEX associa a automaticamente a doca e estoque mais vantajosos para criação do pedido.
                    "logisticsInfo": [
                        {
                            "itemIndex": 0,
                            "selectedSla": null,
                            "addressId": null,
                            "selectedDeliveryChannel": "delivery",
                            "deliveryIds": [],
                            "lockTTL": "10d",
                            "shippingEstimate": "9bd",
                            "bundlePrice": 1000,
                            "price": 1000,
                            "deliveryWindow": null
                        }
                    ]
                $dataLogisticsInfo = array(
                    'itemIndex'         => $countIndex,
                    'selectedSla'       => null,
                    'addressId'         => null,
                    'selectedDeliveryChannel'   => "delivery",
                    "deliveryIds"       => [],
                    'lockTTL'           => "0bd",
                    'shippingEstimate'  => "{$orderMain['ship_time']}bd",
                    'price'             => 0,
                    'deliveryWindow'    => null
                );
                 */

                $dataLogisticsInfo = array(
                    'itemIndex'         => $countIndex,
                    'selectedSla'       => null,
                    //'deliverycompany'   => "vtex:fob_{$orderMain['ship_service']}",
                    'lockTTL'           => "0bd", // dias de reserva
                    'shippingEstimate'  => "{$orderMain['ship_time']}bd",
                    'price'             => 0,
                    'deliveryWindow'    => null
                );

                if ($logistic['seller'] && $logistic['type'] === 'vtex') {
                    $expService = explode('_', $orderMain['ship_service']);
                    if (count($expService) === 2) {
                        $dataLogisticsInfo['selectedSla']       = $expService[0];
                        $dataLogisticsInfo['deliverycompany']   = $expService[0];
                    } else {
                        $dataLogisticsInfo['selectedSla']       = $orderMain['ship_service'];
                        $dataLogisticsInfo['deliverycompany']   = $orderMain['ship_service'];
                    }

                    // fob=true ==> transporte por conta do marketplace
                    $newOrder['shippingData']['isFOB'] = false;
                }

                array_push($newOrder['shippingData']['logisticsInfo'], $dataLogisticsInfo);
                $countIndex++;
            }

            // Adiciona os itens no array newOrder
            foreach ($arrItemFormat as $itenFormat) {
                array_push($newOrder['items'], $itenFormat);
            }

            // calcula o valor do frete para dividir entre os itens proporcionalmente
            $priceFreightTotal  = (float)$orderMain['total_ship'];
            $priceFreight       = $priceFreightTotal;
            $priceFreightPeIten = $priceFreightTotal / count($newOrder['shippingData']['logisticsInfo']);
            foreach ($newOrder['shippingData']['logisticsInfo'] as $keyLogisticInfo => $logisticInfo) {

                if (($keyLogisticInfo + 1) == count($newOrder['shippingData']['logisticsInfo'])) {
                    $newOrder['shippingData']['logisticsInfo'][$keyLogisticInfo]['price'] = (int)str_replace('.', '', number_format($priceFreight, 2, '.', ''));
                    continue;
                }
                $priceFreightTemp = (float)number_format($priceFreightPeIten, 2,'.', '');
                $priceFreight = $priceFreight - $priceFreightTemp;

                $newOrder['shippingData']['logisticsInfo'][$keyLogisticInfo]['price'] = (int)str_replace('.', '', number_format($priceFreightTemp, 2, '.', ''));

            }

            // Pagamento
            $newOrder['paymentData'] = null;
            /*
            $newOrder['paymentData']['id'] = 'paymentData';
            $newOrder['paymentData']['merchantName'] = $this->accountName;
            $newOrder['paymentData']['payments'] = array();

            $newOrder['paymentData']['payments'] = array(
                'paymentSystem'     => 0, // codigo pagamento
                'installments'      => 1,
                'value'             => str_replace('.', '', number_format($orderMain['gross_amount'], 2, '.', '')),
                'referenceValue'    => str_replace('.', '', number_format($orderMain['gross_amount'], 2, '.', ''))
            );
            */

            // VTEX obriga a enviar a requisição dentro de um array
            $newOrder = array($newOrder);

            // Enviar pedido
            $url        = "api/fulfillment/pvt/orders?sc={$this->salesChannel}&affiliateId={$this->affiliateId}";
            $dataOrder  = $this->sendREST($url, $newOrder, 'POST');

            // erro inesperado
            if (!in_array($dataOrder['httpcode'], array(200, 400, 500))) {
                $msgError = "Não foi possível integrar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder) . "\nENVIADO=".json_encode($newOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <p>Ocorreu um problema inesperado, em breve tentaremos integrar novamente.</p>", "E");
                continue;
            }
            $contentOrder = json_decode($dataOrder['content']);

            // divergencia de valores, deverá fazer uma nova simulação para ver se o frete mudou, para adicionar um desconto ou ascréscimo
            if (
                $dataOrder['httpcode'] == 400 &&
                isset($contentOrder->error->code) &&
                $contentOrder->error->code == 'FMT007' &&
                $logistic['seller'] &&
                $logistic['type'] === 'vtex'
            ) {
                $skuSimulation = array_map(function ($item){
                    return array('sku' => $item['id'], 'qty' => $item['quantity']);
                }, $newOrder[0]['items']);

                $priceSlas = $this->getShippingOrder($skuSimulation, $dataLogisticsInfo['selectedSla'] ?? '', $newOrder[0]['shippingData']['address']['postalCode']);

                foreach ($newOrder[0]['shippingData']['logisticsInfo'] as $keyItem => $item) {
                    $slaCurrent = (int)$item['price'];
                    $slaNew     = (int)$priceSlas[$newOrder[0]['items'][$item['itemIndex']]['id']];
                    if ($slaCurrent != $slaNew) {
                        $arrPriceTags = array(
                            "isPercentual"  => false,
                            "identifier"    => null,
                            "rate"          => null,
                            "jurisCode"     => null,
                            "jurisType"     => null,
                            "jurisName"     => null
                        );

                        if ($slaNew > $slaCurrent) {
                            $arrPriceTags['name'] = "Desconto Divergência Frete";
                            $arrPriceTags['value'] = -($slaNew - $slaCurrent);
                            $arrPriceTags['rawValue'] = -(float)substr_replace($slaNew - $slaCurrent, '.', -2, 0);
                        } else {
                            $arrPriceTags['name'] = "Acréscimo Divergência Frete";
                            $arrPriceTags['value'] = $slaCurrent - $slaNew;
                            $arrPriceTags['rawValue'] = +(float)substr_replace($slaCurrent - $slaNew, '.', -2, 0);
                        }

                        array_push($newOrder[0]['items'][$keyItem]['priceTags'], $arrPriceTags);
                    }
                }
                $dataOrder  = $this->sendREST($url, $newOrder, 'POST');
                $contentOrder = json_decode($dataOrder['content']);
            }

            // erro esperado
            if (in_array($dataOrder['httpcode'], array(400, 500))) {
                $msgError = "Não foi possível integrar o pedido {$orderId}! \nORDER_INTEGRATION=".json_encode($orderIntegration)."\nORDER=" . json_encode($order) . "\nRETORNO=".json_encode($dataOrder) . "\nENVIADO=".json_encode($newOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");

                // formatar mensagens de erro para log integration
                $arrErrors = array();
                $errors = $contentOrder->error->message;
                if (!is_array($errors)) $errors = (array)$errors;
                foreach ($errors as $error) {
                    $msgErrorIntegration = $error ?? "Erro desconhecido";
                    array_push($arrErrors, $msgErrorIntegration);
                }
                if (!count($arrErrors))
                    array_push($arrErrors, "Erro desconhecido");

                if (count($arrErrors) === 1 && $arrErrors[0] === 'Nome do merchant inválido') {
                    $arrErrors[0] = 'A configuração do afiliado está configurado para utilizar o próprio meio de pagamento. O ideal seria desativar a opção "Usar meu meio de pagamento", pois o pagamento é assumido pelo afiliado.';
                }

                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                continue;
            }

            $idVtex = $contentOrder[0]->orderId;

            $this->log_integration("Pedido {$orderId} integrado", "<h4>Novo pedido integrado com sucesso</h4> <ul><li>O pedido {$orderId}, foi criado na Vtex com o Nº {$idVtex}</li></ul>", "S");

            // Autorizar despacho
            $this->authorizeDispatch($orderId, $idVtex);

            // Iniciar manuseio (só poderá ir para manuseio quando finalizar o prazo de carência)
            // provavelmente quem fará isso é o seller ... não diz nada na documentação em mover para manuseio
            // https://help.vtex.com/pt/tutorial/integracao-entre-marketplace-nao-vtex-e-seller-vtex-acoes-referentes-ao
            //$this->startHandling($idVtex);

            // salva id da integração do bling
            $this->saveOrderIdIntegration($orderId, $idVtex);
            // remove da fila de integração
            if ($paidStatus != 3)
                $this->removeOrderIntegration($orderId);

            // controlador da lista para pedidos que chegaram como aguardando faturamento ou cancelado
            $this->controlRegisterIntegration($orderIntegration);

            $this->log_data('batch', $log_name, "Pedido {$orderId} integrado com sucesso! enviado=" . json_encode($newOrder) . ' retorno_bling='.json_encode($dataOrder));
            echo "Pedido {$orderId} integrado com sucesso com o código {$idVtex}!\n";
        }
    }

    private function authorizeDispatch($orderId, $orderIdVtex)
    {
        $url  = "api/fulfillment/pvt/orders/{$orderIdVtex}/fulfill?sc={$this->salesChannel}&affiliateId={$this->affiliateId}";
        $body = array("marketplaceOrderId" => $orderId);

        $authorizeDispatch = $this->sendREST($url, $body, 'POST');

        if ($authorizeDispatch['httpcode'] != 200) {
            $msgError = "Não foi possível autorizar o despacho do pedido {$orderId}! RETORNO=".json_encode($authorizeDispatch);
            echo "{$msgError}\n";
            $this->log_data('batch', 'vtex/createOrder/confirmPayment', $msgError, "W");
            $this->log_integration("Erro para autorizar o despacho do pedido {$orderId}", "<h4>Não foi possível autorizar o despacho para o pedido {$orderId}</h4> <p>Ocorreu um problema inesperado, provavelmente alguma instabilidade na integração.</p><p>Será necessário realizar a autorização para despacho manualmente.</p>", "E");
            return false;
        }

        return true;
    }

    private function startHandling($orderIdVtex)
    {
        $url  = "https://conectala.vtexcommercestable.com.br/api/oms/pvt/orders/{$orderIdVtex}/start-handling";
        $body = array();

        return $this->sendREST($url, $body, 'POST');
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
                    'orders.net_amount',
                    'orders.discount as discount_order',
                    'orders.total_ship as total_ship',
                    'orders.ship_company_preview as ship_company',
                    'orders.ship_service_preview as ship_service',
                    'orders.ship_time_preview as ship_time',
                    'orders.customer_address_num as order_address_num',
                    'orders.customer_address_compl as order_address_compl',
                    'orders.customer_address_neigh as order_address_neigh',
                    'orders.customer_address_city as order_address_city',
                    'orders.customer_address_uf as order_address_uf',
                    'orders.customer_address_zip as order_address_zip',
                    'orders.customer_reference as order_reference',
                    'orders.origin as int_to_order',
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
                    'orders_payment.forma_desc as forma_payment',
                    'products.product_id_erp as product_id_erp'
                )
            )
            ->from('orders')
            ->join('orders_item', 'orders.id = orders_item.order_id')
            ->join('orders_payment', 'orders.id = orders_payment.order_id', 'left')
            ->join('clients', 'orders.customer_id = clients.id', 'left')
            ->join('products', 'orders_item.product_id = products.id', 'left')
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
        return (bool)$this->db->delete(
            'orders_to_integration',
            array(
                'store_id' => $this->store,
                'order_id' => $order_id,
                'new_order' => 1
            ), 1
        );
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

        return (bool)$this->db->delete(
            'orders_to_integration',
            $where
        );
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
    private function getOrderCancel(int $orderId): bool
    {
//        $orderCancel = $this->db
//            ->get_where('orders_to_integration',
//                array(
//                    'order_id'      => $orderId,
//                    'store_id'      => $this->store,
//                    'paid_status'   => 96
//                )
//            )->row_array();
        $orderCancel =  $this->db
            ->from('orders_to_integration')
            ->where(array(
                'order_id'      => $orderId,
                'store_id'      => $this->store,
            ))
            ->where_in('paid_status', array(95, 96, 97))
            ->get()
            ->row_array();

        if (!$orderCancel) return false;

        return true;
    }

    /**
     * Recupera o SKU do produto/variação vendido
     *
     * @param   array       $item   Array com dados do produto vendido
     * @return  false|array         Retorna o sku do produto ou variação, em caso de erro retorna false
     */
    public function getSkuERP(array $item)
    {
        if ($item['variant_product'] == "") {
            $priceProduct = $this->db->get_where('products', array('id' => $item['id_product']))->row_array();
            return array(
                'sku'   => $item['sku_product'],
                'price' => (float)$priceProduct['price']
            );
        }

        $var = $this->db
            ->get_where('prd_variants',
                array(
                    'prd_id'    => $item['id_product'],
                    'variant'   => $item['variant_product']
                )
            )->row_array();

        if (!$var) {
            return false;
        }

        return array(
            'sku'   => $var['variant_id_erp'] ?? $var['sku'],
            'price' => (float)$var['price']
        );
    }

    /**
     * Checa se o produto tem estoque para integração
     *
     * @param   int $product    ID do produto para serem consultado estoque
     * @param   int $qty        Quantidade para validação
     * @return  bool            Retorna um array com o estoque total e estoque separado por ID
     */
    public function getProductInStock(string $product, int $qty): bool
    {
        // Consulta endpoint par obter estoque
        $url = "api/fulfillment/pvt/orderForms/simulation?affiliateId={$this->affiliateId}&sc={$this->salesChannel}";
        $body = array(
            'items' => array(
                array(
                    'id'        => $product,
                    'quantity'  => $qty,
                    'seller'    => 1
                )
            )
        );

        $dataStockProduct = $this->sendREST($url, $body, 'POST');

        if ($dataStockProduct['httpcode'] != 200) return false;

        $dataStock = json_decode($dataStockProduct['content']);

        if (!isset($dataStock->items[0]->quantity)) return false;

        return $qty <= $dataStock->items[0]->quantity;
    }

    /**
     * Checa se o produto tem estoque para integração
     *
     * @param   array       $product            ID do produto para serem consultado estoque
     * @param   string      $logisticSelected   Logística selecionado
     * @param   string      $cep                CEP do destinatário
     * @return  array|bool                      Retorna um array com o preço de entrega de cada sku
     * @throws  Exception
     */
    public function getShippingOrder(array $product, string $logisticSelected, string $cep)
    {
        // Consulta endpoint par obter estoque
        $url = "api/fulfillment/pvt/orderForms/simulation?affiliateId={$this->affiliateId}&sc={$this->salesChannel}";
        $body = array(
            'items' => array(),
            'postalCode' => $cep,
            'country' => 'BRA'
        );
        $arrShipping = array();

        foreach ($product as $sku) {
            array_push($body['items'], array(
                'id'        => $sku['sku'],
                'quantity'  => $sku['qty'],
                'seller'    => 1
            ));
        }

        $dataSla = $this->sendREST($url, $body, 'POST');

        if ($dataSla['httpcode'] != 200) {
            return false;
        }

        $data = json_decode($dataSla['content']);

        foreach ($data->logisticsInfo as $logistic) {
            $priceSelected = null;
            foreach ($logistic->slas as $sla) {
                if ($sla->id == $logisticSelected) {
                    $priceSelected = $sla->price;
                }
            }
            $arrShipping[$data->items[$logistic->itemIndex]->id] = $priceSelected;
        }

        return $arrShipping;
    }
}
