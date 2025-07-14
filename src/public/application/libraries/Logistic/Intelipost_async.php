<?php

use GuzzleHttp\Utils;

class Intelipost extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
        $this->setEndpoint('https://api.intelipost.com.br/api/v1');
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $auth = array();
        $auth['headers']['api-key']  = $this->credentials['token'] ?? '';
        $auth['headers']['platform'] = $this->sellerCenter;

        $this->authRequest = $auth;
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse()
    {
        $dataStore = $this->dbReadonly->get_where('stores', array('id' => $this->store))->row_object();
        $bodyWarehouse = array(
            'federal_tax_payer_id' 	=> onlyNumbers($dataStore->CNPJ),
            'state_tax_payer_id'	=> onlyNumbers($dataStore->inscricao_estadual),
            'code'					=> $this->store,
            'zip_code' 				=> onlyNumbers($dataStore->zipcode),
            'name' 					=> $dataStore->name,
            'official_name' 		=> $dataStore->raz_social,
            'phone' 				=> $dataStore->phone_1,
            'email' 				=> $dataStore->responsible_email,
            'street' 				=> $dataStore->address,
            'reference' 			=> $dataStore->addr_compl,
            'number' 				=> $dataStore->addr_num,
            'quarter' 				=> $dataStore->addr_neigh,
            'city' 					=> $dataStore->addr_city,
            'state_code' 			=> $dataStore->addr_uf,
            'additional' 			=> ''
        );

        try {
            $response = $this->request('GET', "/warehouse/code/$this->store");
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());

            // verificar se os dados do CD está atualizado
            // Dados CD
            $wareHouseIntelipost = $contentOrder->content;

            if (
                onlyNumbers($wareHouseIntelipost->federal_tax_payer_id)	!= onlyNumbers($dataStore->CNPJ) ||
                onlyNumbers($wareHouseIntelipost->state_tax_payer_id)	!= onlyNumbers($dataStore->inscricao_estadual) ||
                onlyNumbers($wareHouseIntelipost->zip_code)				!= onlyNumbers($dataStore->zipcode) ||
                $wareHouseIntelipost->name					            != $dataStore->name ||
                $wareHouseIntelipost->official_name		                != $dataStore->raz_social ||
                onlyNumbers($wareHouseIntelipost->phone)				!= onlyNumbers($dataStore->phone_1) ||
                $wareHouseIntelipost->email				                != $dataStore->responsible_email ||
                $wareHouseIntelipost->street				            != $dataStore->address ||
                $wareHouseIntelipost->reference			                != $dataStore->addr_compl ||
                $wareHouseIntelipost->number				            != $dataStore->addr_num ||
                $wareHouseIntelipost->quarter				            != $dataStore->addr_neigh ||
                $wareHouseIntelipost->city					            != $dataStore->addr_city ||
                $wareHouseIntelipost->state_code			            != $dataStore->addr_uf
            ) { // algum dado está desatualizado, preciso atualizar
                // atualizar todos os dados do CD
                try {
                    $this->request('PUT', "/warehouse/code/$this->store", array('json' => $bodyWarehouse));
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }
            }
        } catch (InvalidArgumentException $exception) {
            if (isset($response)) {
                if ($response->getStatusCode() == 404) {
                    // cadastrar CD
                    try {
                        $this->request('POST', "/warehouse", array('json' => $bodyWarehouse));
                    } catch (InvalidArgumentException $exception) {
                        throw new InvalidArgumentException($exception->getMessage());
                    }
                }
            } else {
                try {
                    $this->request('POST', "/warehouse", array('json' => $bodyWarehouse));
                    return;
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }
            }
            throw new InvalidArgumentException($exception->getMessage());
        }
    }

    /**
     * Cotação.
     *
     * @param   array   $dataQuote      Dados para realizar a cotação.
     * @param   bool    $moduloFrete    Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        if (!isset(
            $dataQuote['zipcodeSender'],
            $dataQuote['zipcodeRecipient'],
            $dataQuote['items']
        ) || !is_array($dataQuote['items'])) {
            throw new InvalidArgumentException('Dados de cotação incompletos');
        }

        $sales_channel = $this->sellerCenter;
        if (!$this->freightSeller) {
            $sales_channel .= "-$this->store";
        }

        $arrSkuProductId = array();
        $arrQuote = array(
            "origin_zip_code" => $dataQuote['zipcodeSender'],
            "destination_zip_code" => $dataQuote['zipcodeRecipient'],
            "products" => array(),
            "additional_information" => [
                "lead_time_business_days"   => $dataQuote['crossDocking'] ?? 0, // Adiciona mais dias dentro do prazo estimado na cotação. Exemplo: Prazo do fornecedor (crossdocking) para um produto específico.
                "sales_channel"             => $sales_channel,
                "rule_tags"                 => array($this->sellerCenter)
            ],
            "identification" => [
                "url" => base_url()
            ]
        );

        foreach ($dataQuote['items'] as $sku) {
            if (!isset($sku['peso'], $sku['valor'], $sku['quantidade'], $sku['largura'], $sku['altura'], $sku['comprimento'], $sku['skuseller'], $sku['sku'])) {
                throw new InvalidArgumentException('Item da cotação inválido');
            }
            $dataProduct = array(
                "weight"            => $sku['peso'],
                "cost_of_goods"     => $sku['valor'] / $sku['quantidade'],
                "width"             => $sku['largura'] * 100,
                "height"            => $sku['altura'] * 100,
                "length"            => $sku['comprimento'] * 100,
                "quantity"          => $sku['quantidade'],
                "sku_id"            => $sku['skuseller'],
                "can_group"         => false, // true/false - produto agrupável
                "product_category"  => "Other"
            );

            $arrSkuProductId[] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['products'][] = $dataProduct;
        }

        try {
            $services = $this->getQuoteUnit($arrQuote, $arrSkuProductId);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }

    /**
     * Recupera os dados de preço e prazo a transportadora, caso não consiga recuperar a cotação na hora do pedido, será feita uma nova para o serviço escolhido.
     *
     * @param   array       $skus           Dados do SKU para cotação.
     * @param   int|null    $cross_docking  Dias de crossdocking.
     * @param   string      $zip            CEP de destino.
     * @param   string|null $quoteId        Código da cotação na hora da venda.
     * @param   string|null $shipService    Serviço escolhido pelo cliente, que efetuará a entrega.
     * @return  array
     * @throws  InvalidArgumentException
     */
    public function getPriceAndDeadlineIntelipost(array $skus, ?int $cross_docking, string $zip, ?string $quoteId, ?string $shipService): array
    {
        if (empty($quoteId)) {
            try {
                $response = $this->request('GET', "/quote/$quoteId");
                $contentOrder = Utils::jsonDecode($response->getBody()->getContents());

                if (isset($contentOrder->content->delivery_options) && is_array($contentOrder->content->delivery_options) && count($contentOrder->content->delivery_options)) {
                    foreach ($contentOrder->content->delivery_options as $service) {
                        if ($service->delivery_method_name === $shipService) {
                            return array(
                                'quote_id'  => sprintf('%.0f', $contentOrder->content->id),
                                'method_id' => $service->delivery_method_id,
                                'value'     => $service->final_shipping_cost,
                                'deadline'  => $service->delivery_estimate_business_days,
                                'method'    => $service->delivery_method_name,
                                'provider'  => $service->logistic_provider_name
                            );
                        }
                    }
                }
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        }

        $cepOrigin      = onlyNumbers($skus['expedidor']['endereco']['cep']);
        $cepDestination = onlyNumbers($zip);

        $sales_channel = $this->sellerCenter;
        if ($this->freightSeller) {
            $sales_channel .= "-$this->store";
        }

        $arrQuote = array(
            "origin_zip_code" => $cepOrigin,
            "destination_zip_code" => $cepDestination,
            //"quoting_mode" => "DYNAMIC_BOX_ALL_ITEMS", // Tipo de cotação (DYNAMIC_BOX_ALL_ITEMS, REGISTERED_BOXES, DYNAMIC_BOX_SINGLE_ITEM, DYNAMIC_BOX_BY_SKU)
            "products" => [],
            "additional_information" => [
                // "free_shipping"          => true,
                // "extra_cost_absolute"    => 0, // Aumenta/Diminui o custo de frete com o valor passado neste parâmetro.
                // "extra_cost_percentage"  => 0, // Aumenta/Diminui o custo de frete com o valor de porcentagem passado neste parâmetro.
                "lead_time_business_days"   => $cross_docking ?? 0, // Adiciona mais dias dentro do prazo estimado na cotação. Exemplo: Prazo do fornecedor (crossdocking) para um produto específico.
                "sales_channel"             => $sales_channel,
                "rule_tags"                 => array($this->sellerCenter)
            ],
            "identification" => [
                "url" => base_url()
            ]
        );

        foreach ($skus['volumes'] as $sku) {
            $arrQuote['products'][] = array(
                "weight"            => $sku['peso'],
                "cost_of_goods"     => $sku['valor']/$sku['quantidade'],
                "width"             => $sku['largura'] * 100,
                "height"            => $sku['altura'] * 100,
                "length"            => $sku['comprimento'] * 100,
                "quantity"          => $sku['quantidade'],
                "sku_id"            => $sku['sku'],
                "can_group"         => false, // true/false - produto agrupável ?
                "product_category"  => "Other"
            );
        }
        
        try {
            $response = $this->request('POST', "/quote_by_product", array('json' => $arrQuote));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $services = $contentOrder->content->delivery_options;
        if (count($services)) {
            foreach ($services as $service) {
                if ($service->delivery_method_name === $shipService) {
                    return array(
                        'quote_id'  => sprintf('%.0f', $contentOrder->content->id),
                        'method_id' => $service->delivery_method_id,
                        'value'     => $service->final_shipping_cost,
                        'deadline'  => $service->delivery_estimate_business_days,
                        'method'    => $service->delivery_method_name,
                        'provider'  => $service->logistic_provider_name
                    );
                }
            }
        }

        throw new InvalidArgumentException("Não possível realizar a cotação. Serviço ($shipService) não encontrado.");
    }

    /**
     * @param   array   $body               Corpo da requisição.
     * @param   array   $arrSkuProductId    Código de SKU e PRD_ID para gerar o retorno.
     * @return  array
     * @throws  InvalidArgumentException
     */
    private function getQuoteUnit(array $body, array $arrSkuProductId): array
    {
        try {
            $response = $this->request('POST', "/quote_by_product", array('json' => $body));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // não encontrou transportadora
        if (
            !isset($contentOrder->content->delivery_options) ||
            !is_array($contentOrder->content->delivery_options) ||
            !count($contentOrder->content->delivery_options)
        ) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Intelipost\n" . Utils::jsonEncode($contentOrder));
        }

        $services = array();

        foreach ($contentOrder->content->delivery_options as $service) {
            $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                'quote_id'      => sprintf('%.0f', $contentOrder->content->id),
                'method_id'     => $service->delivery_method_id,
                'value'         => $service->final_shipping_cost,
                'deadline'      => $service->delivery_estimate_business_days,
                'method'        => $service->delivery_method_name,
                'provider'      => $service->logistic_provider_name,
                'token_oferta'  => sprintf('%.0f', $contentOrder->content->id),
                'provider_cnpj' => null,
                'custo_frete'   => $service->provider_shipping_cost ?? null,
                'quote_json'    => Utils::jsonEncode($contentOrder)
            )));
        }

        return $services;
    }

    /**
     * @param   object  $content_response   Dados da requisição.
     * @param   array   $skumkt_product     Dados skumkt e product_id.
     * @return  array
     * @throws  InvalidArgumentException
     */
    private function getQuoteAsync(object $content_response, array $skumkt_product): array
    {
        // não encontrou transportadora
        if (
            !isset($content_response->content->delivery_options) ||
            !is_array($content_response->content->delivery_options) ||
            !count($content_response->content->delivery_options)
        ) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Intelipost\n" . Utils::jsonEncode($content_response));
        }

        $services = array();

        foreach ($content_response->content->delivery_options as $service) {
            $services[] = array(
                'prd_id'        => $skumkt_product[1],
                'skumkt'        => $skumkt_product[0],
                'quote_id'      => sprintf('%.0f', $content_response->content->id),
                'method_id'     => $service->delivery_method_id,
                'value'         => $service->final_shipping_cost,
                'deadline'      => $service->delivery_estimate_business_days,
                'method'        => $service->delivery_method_name,
                'provider'      => $service->logistic_provider_name,
                'token_oferta'  => sprintf('%.0f', $content_response->content->id),
                'provider_cnpj' => null,
                'custo_frete'   => $service->provider_shipping_cost ?? null,
                'quote_json'    => Utils::jsonEncode($content_response)
            );
        }

        return $services;
    }
    
    /**
     * Salvar e atualizar pedido com os dados de rastreio de transportadora da Intelipost
     *
     * @param 	array 	$freight	    Pedido na intelipost
     * @param 	string 	$tracking		Código do rastreio
     * @param 	array 	$order		    Código do pedido (orders.id)
     * @param	int 	$volume			Volume do pedido na intelipost
     * @param 	int 	$inResendActive Pedido está em processo de reenvio
     * @return  void                    Retorno o status da atualização
     */
    public function getLabelIntelipost(array $freight, string $tracking, array $order, int $volume, int $inResendActive): void
    {
        $this->load->model('model_orders');
        $this->load->model('model_freights');

        $pathEtiquetas = $this->getPathLabel();

        $etiquetaA4      = "$pathEtiquetas/P_{$order['id']}_{$volume}_{$inResendActive}_A4.pdf";
        $etiquetaThermal = "$pathEtiquetas/P_{$order['id']}_{$volume}_{$inResendActive}_Termica.pdf";

        try {
            $response = $this->request('GET', "/shipment_order/get_label/{$freight['shipping_order_id']}/$volume");
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $getEtiqueta = $contentOrder->content->label_url;
        copy($getEtiqueta, FCPATH . $etiquetaA4);
        copy($getEtiqueta, FCPATH . $etiquetaThermal);

        // Em processo de implementação, em breve será removido.
        if ($this->sellerCenter === 'connectparts' || $this->sellerCenter === 'naterra') {
            $this->model_freights->updateByVolumeAndId(array(
                'link_etiqueta_a4'      => base_url($etiquetaA4),
                'link_etiqueta_termica' => base_url($etiquetaThermal),
                'codigo_rastreio'       => $tracking
            ), $order['id'], $volume);
        } else {
            $this->model_freights->update(array(
                'link_etiqueta_a4'      => base_url($etiquetaA4),
                'link_etiqueta_termica' => base_url($etiquetaThermal),
                'codigo_rastreio'       => $tracking
            ), $freight['id']);
        }

        if ($order['paid_status'] == 50) {
            $this->model_orders->updatePaidStatus($order['id'], $inResendActive ? 53 : 51);
        }

    }
    
    /**
     * Contrata o frete pela Intelipost
     *
     * @param  array	$order					Dados do pedido
     * @param  array	$store					Dados da loja
     * @param  array	$nfe					Dados de nota fiscal
     * @param  array 	$client					Dados do client
     * @param  bool		$orderWaitTrackingCode 	Pedido já contratado está aguardando rastreio
     */
    public function hireFreight(array $order, array $store, array $nfe, array $client, bool $orderWaitTrackingCode)
    {
        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_freights');
        $this->load->model('model_products_catalog');
        $this->load->model('model_quotes_ship');

        $items = $this->model_orders->getOrdersItemData($order['id']);
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $quote = array();
        $quote['volumes'] = array();
        $quote['destinatario']['endereco']['cep'] = onlyNumbers($order['customer_address_zip']);
        $quote['expedidor']['endereco']['cep'] 	  = onlyNumbers($store['zipcode']);
        $cross_docking = 0;

        // formato os campos para inserir no array
        $dateCreate 	= get_instance()->formatDateBr_En($nfe['date_emission']);

        $freightOrders = $this->model_freights->getDataFreightsOrderId($order['id']);
        if (count($freightOrders)){
            if (!empty($freightOrders[0]['codigo_rastreio']))  {
                $this->model_orders->updatePaidStatus($order['id'], 51);
                return;
            }
        }

        // Pedido já com frete aguardando código de rastreio.
        if ($orderWaitTrackingCode) {

            $freightOrders = $this->model_freights->getDataFreightsOrderId($order['id']);

            $trackingAlreadyRead = array();

            if ($freightOrders) {
                foreach ($freightOrders as $freightOrder) {
                    try {
                        $response = $this->request('GET', "/shipment_order/invoice_key/{$nfe['chave']}");
                        $contentOrder = Utils::jsonDecode($response->getBody()->getContents());

                    } catch (InvalidArgumentException $exception) {
                        return;
                    }

                    foreach ($contentOrder->content as $content) {

                        // Não foi enviado por nós.
                        if ($content->platform !== $this->sellerCenter) {
                            continue;
                        }

                        foreach ($content->shipment_order_volume_array as $tracking) {
                            $preShipmentListState = $tracking->pre_shipment_list_state;

                            if ($preShipmentListState  == 'SUCCESS' && in_array($order['paid_status'], [50,80])) {
                                $trackingCode = $tracking->tracking_code;
                                if ($tracking->tracking_code === null) {
                                    $trackingCode = $nfe['nfe_num'];
                                }

                                $volume = $tracking->shipment_order_volume_number;

                                // Veririficar se já foi lido o rastreio, caso tenha que mais um objeto na Intelipost.
                                if (in_array("{$freightOrder['shipping_order_id']}-$volume", $trackingAlreadyRead)) {
                                    continue;
                                }
                                $trackingAlreadyRead[] = "{$freightOrder['shipping_order_id']}-$volume";

                                echo "Pedido {$order['id']} com volume $volume de intelipost, baixando a etiqueta.\n";
                                $this->getLabelIntelipost($freightOrder, $trackingCode, $order, $volume, $order['in_resend_active']);
                                get_instance()->log_data('batch', $log_name, "Atualizado código de rastreio do pedido\n\nPEDIDO={$order['id']}\ncontent=" . Utils::jsonEncode($contentOrder));
                            } else {
                                echo "Pedido {$order['id']} intelipost, ainda sem disparo de PLP\n";
                            }
                        }
                    }
                }
                return;
            }
        }

        $codeWarehouse = null;
        if (!$this->freightSeller) {
            try {
                $this->setWarehouse();
                $codeWarehouse = $this->store;
            } catch (InvalidArgumentException $exception) {
                echo "ERRO para encontrar/criar/atualizar o warehouse do pedido ( {$order['id']} ) na intelipost.\n";
                get_instance()->log_data('batch', $log_name, "ERRO para encontrar/criar/atualizar o warehouse do pedido ( {$order['id']} ) na intelipost.", "E");
                throw new InvalidArgumentException('Ocorreu um problema para realizar a contratação do frete. Não foi possível encontrar/criar/atualizar centro de distribuição.');
            }
        }

        $shipVolumes = array();
        $arrTracking = array();
        // criou um array dos skus para fazer a cotação e montar os volume para contratação
        $countVolume = 0;
        foreach ($items as $iten) {
            $items_cancel = $this->model_order_items_cancel->getByOrderIdAndItem($order['id'], $item['id']);
            if ($items_cancel) {
                if ($items_cancel['qty'] != $item['qty']) {
                    $item['qty'] = $item['qty'] - $items_cancel['qty'];
                } else {
                    // O cancelamento é total, não deve considerar o produto.
                    continue;
                }
            }

            if (!is_null($iten['product_catalog_id'])) { // se é produto de catálogo, pega as dimensoes de lá
                $prd_catalog = $this->model_products_catalog->getProductProductData($iten['product_id']);
                $iten['largura'] 		= $prd_catalog['width'];
                $iten['altura'] 		= $prd_catalog['height'];
                $iten['profundidade'] 	= $prd_catalog['length'];
                $iten['pesobruto'] 		= $prd_catalog['gross_weight'];
            }
            $quote['volumes'][] = array(
                'sku' 			=> $iten['skumkt'],
                'quantidade' 	=> (int) $iten['qty'],
                'altura' 		=> (float) $iten['altura'] / 100,
                'largura' 		=> (float) $iten['largura'] /100,
                'comprimento' 	=> (float) $iten['profundidade'] /100,
                'peso' 			=> (float) $iten['pesobruto'],
                'valor' 		=> (float) ( (float) $iten['rate'] * (int) $iten['qty'] )
            );

            if ($iten['prazo_operacional_extra'] > $cross_docking) {
                $cross_docking = $iten['prazo_operacional_extra'];
            }

            $product = $this->dbReadonly->select('products_package')->where('id', $iten['product_id'])->get('products')->row_array();
            $qtd_embalado_iten 	= $product['products_package'] ?? 1;

            $qtd_calulo = ceil($iten['qty'] / $qtd_embalado_iten);

            for ($qty_iten = 1; $qty_iten <= $qtd_calulo; $qty_iten++) {
                $countVolume++;

                $quantidade_iten = ($iten['qty'] - ($qty_iten * $qtd_embalado_iten));

                if ($quantidade_iten >= 0) $quantidade_iten = $qtd_embalado_iten;
                else $quantidade_iten = $iten['qty'] - (($qty_iten - 1) * $qtd_embalado_iten);

                $arrTracking[] = ['tracking' => null, 'item_id' => $iten['product_id'], 'volume_number' => $countVolume];

                $shipVolumes[] = array(
                    "name" 							=> "Volume item {$iten['id']} - $qty_iten", // Nome do volume
                    "shipment_order_volume_number" 	=> $countVolume, // Identificador único do volume por pedido que será utilizado depois para poder alterar informações de volume.
                    "volume_type_code" 				=> "BOX", // Tipo do volume (ENVELOPE, BOX, BAG, TUBE, PALLET)
                    "weight" 						=> $iten['pesobruto'],
                    "width" 						=> $iten['largura'],
                    "height" 						=> $iten['altura'],
                    "length" 						=> $iten['profundidade'],
                    "products_quantity" 			=> $quantidade_iten,
                    "products_nature" 				=> "porducts", // Natureza do produto
                    //"products" => [ // Lista de produtos do volume. (Opcional) // por enquanto a base está sem informação da dimensão do produto
                    //	[
                    //		"weight" 		=> $iten['pesobruto'],
                    //		"width" 		=> $larguraProd,
                    //		"height" 		=> $alturaProd,
                    //		"length" 		=> $comprimentoProd,
                    //		"price" 		=> $iten['rate'],
                    //		"description" 	=> $iten['name'],
                    //		"sku" 			=> $iten['skumkt'],
                    //		"category" 		=> "Other",
                    //		"quantity" 		=> $iten['qty'],
                    //		"image_url" 	=> $iten['principal_image']
                    //	]
                    //],
                    "shipment_order_volume_invoice" => [ // Dados nfe
                        "invoice_series" 			=> $nfe['nfe_serie'],
                        "invoice_number" 			=> $nfe['nfe_num'],
                        "invoice_key" 				=> $nfe['chave'],
                        "invoice_date" 				=> $dateCreate,
                        "invoice_total_value" 		=> $order['gross_amount'],
                        "invoice_products_value" 	=> $order['total_order'],
                        "invoice_cfop" 				=> $order['customer_address_uf'] == $store['addr_uf'] ? "5102" : "6102"
                    ],
                    "tracking_code" => null, // Enviar null caso queria que a intelipost gere, caso gerado em outra plataforma enviar nesse campo
                    //"client_pre_shipment_list" => "1000" // Numero de identificação da PLP no sistema do embarcador (gaiola, romaneio, etc).
                );
            }
        }

        // faço a cotação para pegar os dados atualizações
        $quote_ship_data = $this->model_quotes_ship->getQuoteShipByOrderId($order['id']);
        try {
            $quoteIntelipost = $this->getPriceAndDeadlineIntelipost(
                $quote,
                $cross_docking,
                $order['customer_address_zip'],
                $quote_ship_data['token_oferta'] ?? null,
                $order['ship_service_preview']
            );
        } catch (InvalidArgumentException $exception) {
            echo "Erro para realizar a cotação do pedido ( {$order['id']} ) {$exception->getMessage()}\n";
            throw new InvalidArgumentException("Ocorreu um problema para realizar a contratação do frete. Não foi possível realizar a cotação para recuperar o valor e prazo atualizado.\n{$exception->getMessage()}");
        }

        // separo o primeiro nome do último nome
        $fName = "";
        $lNameArr = array();
        $expName = explode(" ", $order['customer_name']);

        foreach ($expName as $name){
            if (empty($fName)) {
                $fName = $name;
                continue;
            }
            $lNameArr[] = $name;
        }
        $lName = implode(" ", $lNameArr);

        $order_number = date('dHis').$order['id'];
        echo "order_number = $order_number\n";

        $sales_channel = $this->sellerCenter;
        if ($this->freightSeller) {
            $sales_channel .= "-$this->store";
        }

        $intelipost = array(
            "quote_id" 						=> $quoteIntelipost['quote_id'], // Id da cotação
            "order_number" 					=> $order_number, // Identificação do pedido de entrega
            "parent_shipment_order_number" 	=> null, // enviar para logística reversa
            "customer_shipping_costs" 		=> $quoteIntelipost['value'], // preço da cotação
            "sales_channel" 				=> $sales_channel, // Canal de Vendas
            "scheduled" 					=> false, // Entrega agendada
            //"created" 					=>  $dateCreate ? $dateCreate : date('Y-m-d H:i:s'), // Data de criação. Sugerimos utilizar a data de faturamento.
            //"shipped_date" 				=> $shippedDate ? $shippedDate.' 00:00:00' : $order['data_limite_cross_docking'], // Data de despacho do pedido de envio.
            "shipment_order_type" 			=> "NORMAL", // Tipo de pedido de envio. Enviar "RETURN" em caso de pedido de Logística Reversa.Enviar "RESEND" em caso de pedido que foi reenviado.
            "delivery_method_id" 			=> $quoteIntelipost['method_id'], // Método de entrega utilizado no pedido de envio (pegar o que foi enviado na cotação e o que o seller escolheu)
            //"delivery_method_external_id" => 1, // Método de entrega utilizado pela transportadora e que será utilizado no pedido de envio. Para que este id possa ser utilizado, um dê/para de dm_id deve estar cadastrado na intelipost. No caso de envio de ambos delivery_method_id e delivery_method_external_id, será considerado no pedido o delivery_method_id.
            //"carrier" => [ // Dados do responsável pela carga.
            //	"driver" => [ // Dados do motorista.
            //		"federal_tax_id" => "78847892387" // CPF do motorista.
            //	],
            //	"vehicle" => [ // Dados do veículo
            //		"licence_plate" => "BRT-8982" // Placa do veículo.
            //	]
            //],
            "end_customer" => [ // Informações do cliente final
                "first_name" 			=> $fName,
                "last_name" 			=> $lName,
                "email" 				=> "api@conectala.com.br",
                "phone" 				=> onlyNumbers($client['phone_1']),
                "cellphone" 			=> onlyNumbers($client['phone_2']),
                "is_company" 			=> strlen(onlyNumbers($client['cpf_cnpj'])) === 14, // por enquanto só vendemos para PF
                "federal_tax_payer_id" 	=> onlyNumbers($client['cpf_cnpj']), // CPF ou CNPJ. Não enviar caracteres especiais.
                "shipping_country" 		=> "Brasil",
                "shipping_state" 		=> $order['customer_address_uf'],
                "shipping_city" 		=> get_instance()->getStateNameByUF($order['customer_address_uf']),
                "shipping_address" 		=> $order['customer_address'],
                "shipping_number" 		=> $order['customer_address_num'],
                "shipping_quarter" 		=> $order['customer_address_neigh'],
                "shipping_reference" 	=> $order['customer_reference'],
                "shipping_additional" 	=> $order['customer_address_compl'],
                "shipping_zip_code" 	=> onlyNumbers($order['customer_address_zip'])
            ],
            "origin_zip_code" 				=> onlyNumbers($store['zipcode']), // CEP do deposito/embarcador de origem. Este campo deve ser enviado em caso de existir mais de um deposito.
            //"origin_warehouse_code" 		=> $codeWarehouse, // Código do deposito de origem. Este campo deve ser enviado em caso de existir mais de um deposito.
            "shipment_order_volume_array" 	=> $shipVolumes, // Volume a ser enviado,
            "estimated_delivery_date" 		=> get_instance()->somar_dias_uteis($order['data_pago'] ?? date('Y-m-d H:i:s'),$order['ship_time_preview']), // Data estimada de entrega, embarcador
            "additional_information" => [ // Informações adicionais do pedido de envio. Este objeto suporta N campos chave => valor, conforme exemplos
                "rule_tags" => $this->sellerCenter
            ],
            "external_order_numbers" => [ // Números adicionais para identificação do pedido em integrações
                "marketplace"	=> $order['numero_marketplace'], // Número do pedido no Marketplace
                "sales" 		=> $order['id'],
                "plataforma" 	=> $order['id'], // Número do pedido na Plataforma
                "erp" 			=> $order['order_id_integration']
            ]
        );

        if ($codeWarehouse) {
            $intelipost['origin_warehouse_code'] = $codeWarehouse;
        }

        try {
            $response = $this->request('POST', "/shipment_order", array('json' => $intelipost));
            $contentHire = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        get_instance()->log_data('batch',$log_name, "Pedido ( {$order['id']} ) criado na intelipost.\ncontent=" . Utils::jsonEncode($contentHire));

        $urlTrackingIntelipost 	= $contentHire->content->tracking_url;
        $preShipmentListState   = $contentHire->content->shipment_order_volume_array[0]->pre_shipment_list_state;
        $trackerCodeIntelipost  = $contentHire->content->shipment_order_volume_array[0]->tracking_code;
        $invoiceNumber 			= $contentHire->content->shipment_order_volume_array[0]->shipment_order_volume_invoice->invoice_number;

        if ($preShipmentListState  == 'SUCCESS' && $trackerCodeIntelipost == NULL) {
            $trackerCodeIntelipost = $invoiceNumber;
        }

        $responseVolumeShipOrder = $contentHire->content->shipment_order_volume_array;

        $pathEtiquetas = $this->getPathLabel();
        $isCorreios = likeText("%correios%", strtolower($quoteIntelipost['provider']));

        // leio o array de rastreio para criar registro na tabela freights e salvar os pdfs
        foreach ($arrTracking as $index => $tracking) {
            $etiquetaA4 = null;
            $etiquetaTermica = null;
            if ($isCorreios) {
                $etiquetaA4      = base_url("$pathEtiquetas/P_{$order['id']}_{$tracking['volume_number']}_A4.pdf");
                $etiquetaTermica = base_url("$pathEtiquetas/P_{$order['id']}_{$tracking['volume_number']}_Termica.pdf");

                try {
                    $response = $this->request('GET', "/shipment_order/get_label/$order_number/{$tracking['volume_number']}");
                    $contentLabel = Utils::jsonDecode($response->getBody()->getContents());
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }

                $getEtiqueta = $contentLabel->content->label_url;
                copy($getEtiqueta, FCPATH . "$pathEtiquetas/P_{$order['id']}_{$tracking['volume_number']}_A4.pdf");
                copy($getEtiqueta, FCPATH . "$pathEtiquetas/P_{$order['id']}_{$tracking['volume_number']}_Termica.pdf");
            }

            $tracking['tracking'] = $isCorreios ? $responseVolumeShipOrder[$index]->tracking_code : $trackerCodeIntelipost;

            // Dados do frete para cadastro.
            $freight = array(
                "order_id" 				=> $order['id'],
                "item_id" 				=> $tracking['item_id'],
                "company_id" 			=> $order['company_id'],
                "ship_company" 			=> "Intelipost",
                "method" 				=> $quoteIntelipost['method'],
                "CNPJ" 					=> "",
                "status_ship" 			=> 0,
                "date_delivered" 		=> "",
                "ship_value" 			=> $quoteIntelipost['value'],
                "prazoprevisto" 		=> get_instance()->somar_dias_uteis($order['data_pago'] ?? date('Y-m-d H:i:s'), $order['ship_time_preview']),
                "idservico" 			=> $quoteIntelipost['method_id'],
                "codigo_rastreio" 		=> $tracking['tracking'],
                "sgp" 					=> 4,
                "link_etiqueta_a4" 		=> $etiquetaA4,
                "link_etiqueta_termica" => $etiquetaTermica,
                "data_etiqueta" 		=> date('Y-m-d H:i:s'),
                "url_tracking"			=> $urlTrackingIntelipost, // "https://status.ondeestameupedido.com/tracking/{$codeIntelipost}/{$order_number}"
                "shipping_order_id"		=> $order_number
            );

            // Em processo de implementação, em breve será removido.
            if ($this->sellerCenter === 'connectparts' || $this->sellerCenter === 'naterra') {
                $freight['volume'] = $tracking['volume_number'];
            }

            $this->model_freights->create($freight);
        }
        // avançar pedido pro status 50 (verificar se vai pro 50 para agerar plp ou vai direto pro 51)
        $this->model_orders->updatePaidStatus($order['id'], $isCorreios || !empty($tracking['tracking']) ? 51 : 50);

        echo "Frete do pedido ( {$order['id']} ) contratado na intelipost.\n";
    }

    /**
     * Consultar as ocorrências do rastreio para Intelipost
     *
     * @param   array   $order  Dados do pedido.
     * @param   array   $frete  Dados do frete.
     * @return  void            Retorna o status do rastreio.
     */
    public function tracking(array $order, array $frete): void
    {
        $this->load->model('model_nfes');

        $invoiceKey = $this->model_nfes->getInvoiceKeyByOrderId($order['id'])[0];

        try {
            $response = $this->request('GET', "/shipment_order/invoice_key/{$invoiceKey["chave"]}");
            $contents = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        /**
         * *********************** STATUS ***********************
         *
         * CLARIFY_DELIVERY_FAIL            Averiguar falha na entrega
         * CANCELLED                        Cancelado
         * SHIPPED                          Despachado
         * IN_TRANSIT                       Em trânsito
         * DELIVERED                        Entregue
         * DELIVERY_FAILED                  Falha na entrega
         * CREATED_AT_LOGISTIC_PROVIDER     Criado na transportadora
         * TO_BE_DELIVERED                  Saiu para entrega
         * CLARIFY_ADDRESS                  Averiguar endereço
         * AUTHORIZATION_CODE_EXPIRED       Código de autorização expirado
         * RETURN_CANCELLED                 Autorização Cancelada
         * CLARIFY_LABEL_FAIL               Averiguar falha na etiqueta
         *
         * WAITING_FOR_DROP_OFF             Aguardando Postagem
         * READY_FOR_SHIPPING               Pronto para envio
         * KEEP_PREVIOUS                    Manter status anterior
         * LABEL_CREATED                    Etiqueta criada
         * NEW                              Criado
         */

        foreach($contents->content as $content) {
            foreach ($content->shipment_order_volume_array as $content_shipment){
                if (!isset($content_shipment->shipment_order_volume_state_history_array)){
                    continue;
                }

                $historyVolume = $content_shipment->shipment_order_volume_state_history_array;
                // inverto a ordem para sempre começar a ler os mais antigos
                $historyVolume = array_reverse($historyVolume);
                // registro de teste de entrega
                /*array_push($historyVolume, (object)[
                    "shipment_order_volume_id"=> 275566426,
                    "shipment_order_volume_state"=> "SHIPPED",
                    "tracking_state"=> null,
                    "created"=> 1613740004927,
                    "created_iso"=> "2021-02-19T17:40:29.442-03:00",
                    "provider_message"=> "",
                    "provider_state"=> null,
                    "esprinter_message"=> null,
                    "shipper_provider_state"=> null,
                    "extra"=> (object)[],
                    "shipment_volume_micro_state"=> (object)[
                        "id"=> 125,
                        "code"=> "1",
                        "default_name"=> "ENTRADA FILIAL DESTINO",
                        "i18n_name"=> "ENTRY_TO_DESTINATION_SUBSIDIARY_1",
                        "description"=> "A carga chegou na filial de entrega final. Alta probablidade de entrega no dia seguinte.",
                        "shipment_order_volume_state_id"=> 14,
                        "shipment_volume_state_source_id"=> 3,
                        "shipment_volume_state"=> "IN_TRANSIT",
                        "shipment_volume_state_localized"=> "Em trânsito",
                        "name"=> "ENTRADA FILIAL DESTINO"
                    ],
                    "location"=> null,
                    "request_hash"=> null,
                    "request_origin"=> null,
                    "attachments"=> null,
                    "shipment_order_volume_state_localized"=> "Em trânsito",
                    "shipment_order_volume_state_history"=> 1840432588,
                    "event_date"=> 1613730595000,
                    "event_date_iso"=> "2021-02-19T07:29:55.000-03:00"
                ]);*/

                foreach ($historyVolume as $history) {

                    if (in_array($history->shipment_order_volume_state, array('NEW', 'LABEL_CREATED', 'CANCELLED'))) {
                        continue;
                    }

                    $dataOccurrence = array(
                        'name'              => $history->shipment_volume_micro_state->name,
                        'description'       => $history->shipment_volume_micro_state->description,
                        'code'              => $history->shipment_volume_micro_state->code,
                        'code_name'         => $history->shipment_order_volume_state,
                        'type'              => '',
                        'date'              => date('Y-m-d H:i:s', strtotime($history->created_iso)),
                        'statusOrder'       => $order['paid_status'],
                        'freightId'         => $frete['id'],
                        'orderId'           => $order['id'],
                        'trackingCode'      => $frete['codigo_rastreio'],
                        'address_place'     => $history->location->local        ?? null,
                        'address_name'      => $history->location->address      ?? null,
                        'address_number'    => $history->location->number       ?? null,
                        'address_zipcode'   => $history->location->zip_code     ?? null,
                        'address_neigh'     => $history->location->quarter      ?? null,
                        'address_city'      => $history->location->city         ?? null,
                        'address_state'     => $history->location->state_code   ?? null
                    );

                    $this->setNewRegisterOccurrence($dataOccurrence);
                }
            }
        }
    }
}