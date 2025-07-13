<?php

use GuzzleHttp\Utils;

class Sequoia extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
        $this->setEndpoint('http://apiml-hml.sequoialog.com.br:8082/api/v1');
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $platform = $this->dbReadonly->where('name', 'plataform_sequoia_sellercenter')->get('settings')->row_object();
        
        if (!$platform || $platform->status != 1) {
            $platform = 'conecta-la';
        } else {
            $platform = $platform->value;
        }
        
        $auth = array();
        $auth['json']['ControleOrigem'] = $this->credentials['token'] ?? null;
        $auth['headers']['x-api-key'] = $this->credentials['token'] ?? null;
        $auth['json']['metadata']['sfx']['client_id'] = $this->credentials['client_id'] ?? null;
        $auth['json']['metadata']['platform'] = $platform;

        $this->authRequest = $auth;
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse() {}

    /**
     * Cotação.
     *
     * @param   array   $dataQuote      Dados para realizar a cotação.
     * @param   bool    $moduloFrete    Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        return array(
            'success'   => true,
            'data'      => array(
                'services'  => array()
            )
        );

        $quoteSequoia = true;
        // verifica se a logística Sequoia deve ser cotado em tabela interna.
        $settingQuoteSequoia = $this->dbReadonly->where('name', 'quote_sequoia_table_internal')->get('settings')->row_object();
        if ($settingQuoteSequoia && $settingQuoteSequoia->status == 1) {
            $quoteSequoia = false;
        }

        $services = array();

        if ($quoteSequoia) {
            try {
                $getCEPClient = $this->zipCodeQuery($dataQuote['zipcodeRecipient']);
                $getCEPSeller = $this->zipCodeQuery($dataQuote['zipcodeSender']);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException("Não foi encontrado o CEP de destino e/ou origem para Sequoia - CEPClient={$dataQuote['zipcodeRecipient']} - CEPSeller={$dataQuote['zipcodeSender']}\n{$exception->getMessage()}");
            }

            if (!$getCEPClient || !$getCEPSeller) {
                throw new InvalidArgumentException("Não foi encontrado o CEP de destino e/ou origem para Sequoia - CEPClient={$dataQuote['zipcodeRecipient']} - CEPSeller={$dataQuote['zipcodeSender']}");
            }

            $shippingValue  = null;
            $shippingTime   = null;

            foreach ($dataQuote['items'] as $item) {
                $arrQuote = array(
                    "ProdutoId"         => 1, // Código do Produto
                    "PesoValido"        => $item['peso'], // Peso válido para cálculo do frete
                    "UFOrigem"          => $getCEPSeller->state, // UF de origem da prestação
                    "UFPagador"         => $getCEPClient->state, // UF do tomador do frete
                    "CEPDestinatario"   => $dataQuote['zipcodeRecipient'], // CEP do Destinatário
                    "ValorNF"           => $item['valor'], // Valor total dos produtos
                );

                try {
                    $response = $this->request('POST', "/frete/obter-calculo", array('json' => $arrQuote));
                    $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }

                /*$httpCode = 200;
                $response = json_decode('
                {
                    "statusCode": 202,
                    "frete": {
                        "valorFrete": 9999,
                        "gris": 50,
                        "adValorem": 0,
                        "tX_ICMS": 0,
                        "pesoValido": 100,
                        "total": 0,
                        "regiao": null,
                        "aliq": "0",
                        "pid": null,
                        "cfop": "5353",
                        "tes": "506",
                        "cst": "041",
                        "filialPrest": null,
                        "empresa": "12",
                        "cnpjEmissor": null,
                        "ativo": 0
                    }
                }');*/

                $freightResponse = $contentOrder->frete;

                if ($shippingValue === null) {
                    $shippingValue = $freightResponse->valorFrete;
                    $shippingTime = $freightResponse->gris;
                } elseif($shippingValue > $freightResponse->valorFrete) {
                    $shippingValue = $freightResponse->valorFrete;
                    $shippingTime = $freightResponse->gris;
                }
            }

            if ($shippingTime) {
                $shippingTime += (int)$dataQuote['crossDocking'];
            }

            $services = array(
                'quote_id'  => null,
                'method_id' => null,
                'value'     => $shippingValue,
                'deadline'  => $shippingTime,
                'method'    => 'Sequoia',
                'provider'  => 'Sequoia'
            );
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }

    /**
     * Contrata o frete pela Sequoia
     *
     * @param  array	$order	Dados do pedido
     * @param  array	$store	Dados da loja
     * @param  array	$nfe	Dados de nota fiscal
     * @param  array 	$client	Dados do client
     */
    public function hireFreight(array $order, array $store, array $nfe, array $client)
    {
        $this->setEndpoint('https://sequoia-sfs-api-prd.azurewebsites.net/v2');
        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_freights');
        $this->load->model('model_products_catalog');

        $items 					= $this->model_orders->getOrdersItemData($order['id']);
        $log_name 				= __CLASS__.'/'.__FUNCTION__;
        $altura_sequoia 		= 0;
        $largura_sequoia 		= 0;
        $comprimento_sequoia 	= 0;
        $etiquetas_sequoia 	    = array();
        $menor_medida_sequoia 	= array();
        $count_etiqueta_sequoia = 0;

        $order_number = $this->getNumberTracking($order);

        echo "order_number = $order_number\n";

        // formato os campos para inserir no array
        $dateEmissionNfe = get_instance()->formatDateBr_En($nfe['date_emission']);

        // monto a logica para criar as etiquetas
        foreach ($items as $item) {
            $items_cancel = $this->model_order_items_cancel->getByOrderIdAndItem($order['id'], $item['id']);
            if ($items_cancel) {
                if ($items_cancel['qty'] != $item['qty']) {
                    $item['qty'] = $item['qty'] - $items_cancel['qty'];
                } else {
                    // O cancelamento é total, não deve considerar o produto.
                    continue;
                }
            }

            $product = $this->db->select('p.products_package,c.name as category')
                ->where('p.id', $item['product_id'])
                ->join('categories as c', 'c.id = left(substr(p.category_id,3),length(p.category_id)-4)')
                ->get('products as p')
                ->row_array();
            
            $qtd_embalado_item  = $product['products_package'] ?? 1;
            $qty_etiqueta       = 0;
            $qtd_calulo 		= ceil($item['qty'] / $qtd_embalado_item);
            $rate 				= $item['rate'];
            $profundidade_item 	= $item['profundidade'];
            $largura_item 		= $item['largura'];
            $altura_item 		= $item['altura'];
            $productId 			= $item['product_id'];

            for ($qty_item = 1; $qty_item <= $qtd_calulo; $qty_item++) {

                $quantidade_item = ($item['qty'] - ($qty_item * $qtd_embalado_item));

                if ($quantidade_item >= 0) $quantidade_item = $qtd_embalado_item;
                else $quantidade_item = $item['qty'] - (($qty_item - 1) * $qtd_embalado_item);

                $comprimento_sequoia += $profundidade_item;
                $largura_sequoia += $largura_item;
                $altura_sequoia += $altura_item;
                $peso_item = $item['pesobruto'];

                if ($comprimento_sequoia > 105 || $largura_sequoia > 105 || $altura_sequoia > 105) {
                    $count_etiqueta_sequoia++;
                    $comprimento_sequoia = $profundidade_item;
                    $largura_sequoia = $largura_item;
                    $altura_sequoia = $altura_item;
                }

                if ($largura_sequoia < $comprimento_sequoia) $menor_medida_sequoia[$count_etiqueta_sequoia] = "L";
                if ($altura_sequoia < $comprimento_sequoia) $menor_medida_sequoia[$count_etiqueta_sequoia] = "A";

                if ($comprimento_sequoia <= $largura_sequoia && $comprimento_sequoia <= $altura_sequoia) $menor_medida_sequoia[$count_etiqueta_sequoia] = "C";
                if ($largura_sequoia <= $comprimento_sequoia && $largura_sequoia <= $altura_sequoia) $menor_medida_sequoia[$count_etiqueta_sequoia] = "L";
                if ($altura_sequoia <= $comprimento_sequoia && $altura_sequoia <= $largura_sequoia) $menor_medida_sequoia[$count_etiqueta_sequoia] = "A";
                if ($altura_sequoia == $comprimento_sequoia && $altura_sequoia == $largura_sequoia) $menor_medida_sequoia[$count_etiqueta_sequoia] = "A";

                $peso_item *= $quantidade_item;
                $rate_item = $rate * $quantidade_item;

                if (isset($etiquetas_sequoia[$count_etiqueta_sequoia][$productId])) {
                    $rate_item += $etiquetas_sequoia[$count_etiqueta_sequoia][$productId]['rate'];
                    $peso_item += $etiquetas_sequoia[$count_etiqueta_sequoia][$productId]['peso_bruto'];

                    $qty_etiqueta++;
                } else {
                    $qty_etiqueta = 1;
                }

                $etiquetas_sequoia[$count_etiqueta_sequoia][$productId] = array(
                    'altura' 		=> $altura_item,
                    'largura' 		=> $largura_item,
                    'profundidade' 	=> $profundidade_item,
                    'peso_bruto' 	=> $peso_item,
                    'qty' 			=> $qty_etiqueta,
                    "name" 			=> $item['name'],
                    "rate" 			=> (float)$rate_item,
                    "category" 		=> $product['category'] ?? null
                );
            }
        }

        // define os packages
        $packages = array();
        $itemToBarcode = array();
        $countVolume = 1;
        foreach ($etiquetas_sequoia as $id_etiqueta => $etiqueta) {
            // Barcode ficará com no mínimo duas decimais, ficando XXXXX-01.
            $barcode = "$order_number-" . str_pad($countVolume , 2 , 0 , STR_PAD_LEFT);

            $peso 				= 0;
            $comprimento 		= 0;
            $largura 			= 0;
            $altura 			= 0;
            $itemsPack			= array();

            foreach ($etiqueta as $id_product => $product) {

                if ($menor_medida_sequoia[$count_etiqueta_sequoia] == null)
                    $menor_medida_sequoia[$count_etiqueta_sequoia] = "A";

                if ($product['profundidade'] == 0 || $product['altura'] == 0 || $product['largura'] == 0 || $product['peso_bruto'] == 0) {
                    throw new InvalidArgumentException('Produto do pedido com dimensões zeradas.');
                }

                switch ($menor_medida_sequoia[$id_etiqueta]) {
                    case "L":
                        $comprimento = max($comprimento, (float)$product['profundidade']);
                        $altura = max($altura, (float)$product['altura']);

                        $largura += (float)$product['largura'] * (int)$product['qty'];
                        break;
                    case "C":
                        $altura = max($altura, (float)$product['altura']);
                        $largura = max($largura, (float)$product['largura']);

                        $comprimento += $product['profundidade'] * (int)$product['qty'];
                        break;
                    case "A":
                        $largura = max($largura, (float)$product['largura']);
                        $comprimento = max($comprimento, (float)$product['profundidade']);

                        $altura += (float)$product['altura'] * (int)$product['qty'];
                        break;
                }

                $peso += (float)$product['peso_bruto'];

                $itemsPack[] = array(
                    'item_id' 				=> (string)$id_product,
                    'list_price' 			=> (float)$product['rate'],
                    'list_price_currency'   => 'BRL',
                    'categories' 			=> array($product['category'])
                );

                foreach ($itemsPack as $item) {
                    $itemToBarcode[$item['item_id']][] = array(
                        'volume'   => $barcode,
                        'quantity' => $product['qty']
                    );
                }
            }

            $fatorCub = 300*10; // em kg
            $packages[$id_etiqueta] = array(
                'barcode' 		 => $barcode, // As embalagens devem ter barcode diferente de vazio e serem únicos.
                'invoice_key' 	 => $nfe['chave'],
                'weight_g' 		 => (int)($peso*1000),
                'length_cm' 	 => (int)$comprimento,
                'width_cm' 		 => (int)$largura,
                'height_cm' 	 => (int)$altura,
                'cubic_weight_g' => ceil(($comprimento * $largura * $altura) / $fatorCub),
                'items' 		 => $itemsPack
            );
            $countVolume++;
        }

        // separo o primeiro nome do último nome do seller
        $fNameSeller = "";
        $lNameSellerArr = array();
        $expNameSeller = explode(" ", $store['responsible_name']);
        foreach ($expNameSeller as $nameSeller){
            if (empty($fNameSeller)) {
                $fNameSeller = $nameSeller;
                continue;
            }
            $lNameSellerArr[] = $nameSeller;
        }
        $lNameSeller = implode(" ", $lNameSellerArr);

        // separo o primeiro nome do último nome do cliente
        $fName = "";
        $lNameArr = array();
        $expName = explode(" ", trim($order['customer_name']));
        foreach ($expName as $name){
            if (empty($fName)) {
                $fName = $name;
                continue;
            }
            $lNameArr[] = $name;
        }
        if (count($lNameArr)) {
            $lName = implode(" ", $lNameArr);
        }
        else {
            $lName = $fName;
        }
        $costumer_phone = $order['customer_phone'];

        if (empty($order['customer_phone'])) {
            $costumer_phone = $client["phone_1"];

            if (empty($client['phone_1'])) {
                $costumer_phone = $client['phone_2'];
            }
        }

        if (!is_numeric($client['ie']))
        {
            $client['ie'] = null;
        }

        // arr de envio para sequoia
        $arrOrderLogistic = array(
            'metadata' => array(
                'extended_id'	=> null,
                'seller' => array(
                    "corporate" 					=> strlen(onlyNumbers($store['CNPJ'])) == 14, // Boolean que indica se o seller é pessoa jurídica.
                    "state_inscription" 			=> $store['inscricao_estadual'], //IE
                    "state_inscription_exempted" 	=> empty($store['inscricao_estadual']), // Isento IE
                    "document" 						=> onlyNumbers($store['CNPJ']), // CNPJ
                    "first_name" 					=> $fNameSeller, // Primeiro nome do responsável
                    "last_name" 					=> $lNameSeller, // Sobrenome do responsável
                    "trading_name" 					=> $store['name'], // fantasia
                    "company_name" 					=> $store['raz_social'], // razão social
                    "email" 						=> $store['responsible_email'],
                    "market_segment" 				=> "others", // Segmento de mercado do seller

                    "phones" => array(),

                    "address" => array(
                        "zip_code" 		=> $store['zipcode'],
                        "city" 			=> $store['addr_city'],
                        "state" 		=> $store['addr_uf'],
                        "neighborhood" 	=> $store['addr_neigh'],
                        "street" 		=> $store['address'],
                        "number" 		=> $store['addr_num'],
                        "complement" 	=> $store['addr_compl'],
                        "reference" 	=> '',
                        "country_code"	=> 'BR'
                    )
                )
            ),
            'order' => array(
                'number'			=> null,
                'observation'		=> '',
                'shipment_value'	=> (float)$order['total_ship'],
                'shipment_currency'	=> 'BRL',
                'packages' 			=> array(),
                'recipient' => array(
                    'corporate'					 => strlen(onlyNumbers($client['cpf_cnpj'])) == 14,
                    'state_inscription'			 => onlyNumbers($client['ie']),
                    'state_inscription_exempted' => empty($client['ie']),
                    'document'					 => onlyNumbers($client['cpf_cnpj']),
                    'first_name'				 => $fName,
                    'last_name'					 => $lName,
                    'trading_name'				 => strlen(onlyNumbers($client['cpf_cnpj'])) == 14 ? $order['customer_name'] : '',
                    'company_name'				 => strlen(onlyNumbers($client['cpf_cnpj'])) == 14 ? $order['customer_name'] : '',
                    'email'						 => $client['email'],
                    'phones' 					 => array(onlyNumbers($costumer_phone)),

                    'address' => array(
                        'zip_code'		=> $order['customer_address_zip'],
                        'city'			=> $order['customer_address_city'],
                        'state'			=> $order['customer_address_uf'],
                        'neighborhood'	=> $order['customer_address_neigh'],
                        'street'		=> $order['customer_address'],
                        'number'		=> $order['customer_address_num'],
                        'complement'	=> $order['customer_address_compl'],
                        'reference'		=> $order['customer_reference'],
                        'country_code' 	=> 'BR'
                    )
                ),
                'invoices' => array(
                    array(
                        'key'		=> $nfe['chave'],
                        'number'	=> $nfe['nfe_num'],
                        'issued_on' => $dateEmissionNfe,
                        'value'		=> (float)($order['total_order'] + $order['total_ship']),
                        'currency'	=> 'BRL',
//                        'metadata' 	=> array(
//                            'name',
//                            'value'
//                        )
                    )
                )
            )
        );

        if (!empty($store['phone_1'])) {
            $arrOrderLogistic['metadata']['seller']['phones'][] = onlyNumbers($store['phone_1']);
        }
        if (!empty($store['phone_2'])) {
            $arrOrderLogistic['metadata']['seller']['phones'][] = onlyNumbers($store['phone_2']);
        }

        try {

            // criar o pedido de entrega
            if (ENVIRONMENT == 'development'){
                $this->setEndpoint('https://app-sfx-api-hml.azurewebsites.net/v2');
            }

            $trackingCodes = array();
            foreach ($packages as $package) {

                $arrOrderLogistic['order']['packages']       = array($package);
                $arrOrderLogistic['metadata']['extended_id'] = $package['barcode'];
                $arrOrderLogistic['order']['number']         = $package['barcode'];
                $trackingCodes[$package['barcode']]          = null;

                $response = $this->request('POST', "/order/integration", array('json' => $arrOrderLogistic));
                $contentHire = Utils::jsonDecode($response->getBody()->getContents());
                //sleep(2);
                //$contentHire = new StdClass;
                //$contentHire->trackingCode = "TESTE-" . date('s');

                $trackingCodes[$package['barcode']] = $contentHire->trackingCode;
            }
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
            try {
                $contentHireError = Utils::jsonDecode($error);
            } catch (Exception $exception) {
                throw new InvalidArgumentException($error);
            }

            $messageError = 'Ocorreu um problema para realizar a contratação do frete. Não foi possível criar o pedido de entrega.';
            // erro esperado pela API
            if (isset($contentHireError->erros) && is_array($contentHireError->erros) && count($contentHireError->erros) > 0) {
                $arrErros = array();
                foreach ($contentHireError->erros as $erro) {
                    $arrErros[] = $erro->message . "($erro->property)";
                }

                $messageError = implode(' | ', $arrErros);
            }

            throw new InvalidArgumentException($messageError);
        }

        get_instance()->log_data('batch', $log_name, "Pedido ( {$order['id']} ) criado na sequoia.\ncontent=" . Utils::jsonEncode($contentHire) . "\nbody=" . Utils::jsonEncode($arrOrderLogistic));

        $urlTracking = "https://sequoia-sfs-web-prd.azurewebsites.net/rastreamento/pedido";

        // Dados do frete para cadastro.
        $arrTempProductExist = array(); // Variável de controle para não duplicar o item.
        echo "Frete do pedido ( {$order['id']} ) contratado na sequoia.\nvolumes=".json_encode($itemToBarcode)."\n";
        foreach ($items as $item) {
            if (in_array($item['product_id'], $arrTempProductExist)) {
                continue;
            }

            $arrTempProductExist[] = $item['product_id'];

            foreach ($itemToBarcode[$item['product_id']] as $itemPack) {
                $freight = array(
                    "order_id" 				=> $order['id'],
                    "item_id" 				=> $item['product_id'],
                    "company_id" 			=> $order['company_id'],
                    "ship_company" 			=> "Sequoia",
                    "method" 				=> 'Sequoia',
                    "CNPJ" 					=> "",
                    "status_ship" 			=> "0",
                    "date_delivered" 		=> "",
                    "ship_value" 			=> $order['total_ship'],
                    "prazoprevisto" 		=> get_instance()->somar_dias_uteis($order['data_pago'] ?? date('Y-m-d H:i:s'), $order['ship_time_preview']),
                    "idservico" 			=> 0,
                    "codigo_rastreio" 		=> $trackingCodes[$itemPack['volume']], //$contentHire->trackingCode,
                    "sgp" 					=> 5,
                    "link_etiqueta_a4" 		=> null,
                    "link_etiqueta_termica"	=> null,
                    "data_etiqueta" 		=> date('Y-m-d H:i:s'),
                    "url_tracking"			=> $urlTracking,
                    "shipping_order_id"		=> $itemPack['volume'] ?? null //$order_number
                );
                $this->model_freights->create($freight);
            }
        }

        // avançar pedido pro status 51
        $this->model_orders->updatePaidStatus($order['id'], 51);
    }

    /**
     * Consultar as ocorrências do rastreio para Sequoia.
     *
     * @param   array   $order  Dados do pedido.
     * @param   array   $frete  Dados do frete.
     * @return  void            Retorna o status do rastreio.
     */
    public function tracking(array $order, array $frete): void
    {
        $options = array();
        $url = "https://func-order-tracking-reader-prd.azurewebsites.net/api/order-tracking";
        $options['query']['code'] = 'jxaDisFocU1LJRzB7I1EUwCanttlbhlfV7j/UDG2DiFBTAEmTAUWCg==';
        //$options['query']['tracking_code'] = $frete['codigo_rastreio'];

        $options['query']['number'] = $this->getNumberTracking($order);
        $options['query']['seller_document'] = onlyNumbers($this->dataQuote['CNPJ']);

        if (strtotime($frete['data_etiqueta']) > strtotime('2023-01-26 15:50:00')) {
            $options['query']['number'] = $frete['shipping_order_id'];
        }

        if (ENVIRONMENT === 'development'){
            $url = "https://func-order-tracking-reader-hml.azurewebsites.net/api/order-tracking";
            $options['query']['code'] = 'v6SEwAzS7Ha5/ehLuWbmE2MuIxwwpFXeRZrUvBtdtjRmDtnMkGAFTg==';
        }

        $this->setEndpoint($url);

        try {
            $response = $this->request('GET', "", $options);
            $historyVolume = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!isset($historyVolume->history) || !is_array($historyVolume->history) || count($historyVolume->history) == 0) {
            return;
        }

        $historyVolume = $historyVolume->history;
        $historyVolume = array_reverse($historyVolume);

        /**
         * ************ STATUS ************
         *
         * Nome                     Código
         * Nenhum                   0
         * AColetar                 1
         * ColetaEmRota             2
         * ColetaCancelada          3
         * Coletado                 4
         * RecepcaoFisica           5
         * ADevolverOrigem          6
         * Devolvido                7
         * EmRota                   8
         * Ocorrencia               9
         * Entregue                 10
         * Extraviado               11
         * EntregaCancelada         12
         * Agendado                 13
         * ProdutoAvariado          14
         * Postado                  15
         * EntregaLocker            16
         * EmTransferencia          17
         * Transferida              18
         * DevolucaoTransferencia   19
         * DevolucaoTransferida     20
         * ADevolverDestino         21
         * AColetarDevolucao        22
         * DevolucaoEmRota          23
         * Inconsistente            24
         * DevolucaoColetado        25
         * Reentregar               26
         * Recoletar                27
         * DevolucaoCancelada       28
         */
        // registro de teste de entrega
//        array_push($historyVolume, (object)[
//            "created_on"         => '2021-05-19T16:11:29.442-03:00',
//            "status_code"        => 4,
//            "status_description" => 'Coletado',
//            "additional_info"    => ''
//        ]);
//        array_push($historyVolume, (object)[
//            "created_on"         => '2021-05-19T17:40:29.442-03:00',
//            "status_code"        => 10,
//            "status_description" => 'Entregue',
//            "additional_info"    => ''
//        ]);

        foreach ($historyVolume as $history) {

            $dataOccurrence = array(
                'description'       => $history->additional_info,
                'name'              => $history->status_description,
                'code'              => $history->status_code,
                'code_name'         => NULL,
                'type'              => NULL,
                'date'              => subtractHoursToDatetime(datetimeNoGMT($history->created_on), 3),// Voltar 3 horas, na API é UTC+0, deve converter para UTF-3.
                'statusOrder'       => $order['paid_status'],
                'freightId'         => $frete['id'],
                'orderId'           => $order['id'],
                'trackingCode'      => $frete['codigo_rastreio'],
                'address_place'     => NULL,
                'address_name'      => NULL,
                'address_number'    => NULL,
                'address_zipcode'   => NULL,
                'address_neigh'     => NULL,
                'address_city'      => NULL,
                'address_state'     => NULL
            );

            $this->setNewRegisterOccurrence($dataOccurrence);

        }
    }

    /**
     * Recupera o número do pedido de entrega.
     *
     * @param   array   $order  Dados do pedido.
     * @return  mixed
     */
    private function getNumberTracking(array $order)
    {
        return $this->sellerCenter == 'somaplace' ? $order['numero_marketplace'] : $order['id'];
    }
}