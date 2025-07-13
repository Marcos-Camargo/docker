<?php

use GuzzleHttp\Utils;

require 'system/libraries/Vendor/rastreio-correios/rastrear.class.php';

class Sgpweb extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);

        $endpoint = 'https://gestaodeenvios.com.br/sgp_login/v/2.2/api';
        if (array_key_exists('type_integration', $this->credentials)) {
            if ($this->credentials['type_integration'] === 'sgpweb') {
                $endpoint = 'https://www.sgpweb.com.br/novo/api';
            }
        }

        $this->setEndpoint($endpoint);
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $auth = array();
        $auth['query']['chave_integracao'] = $this->credentials['token'] ?? null;

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
        $dataFreight     = array();
        $smallestMeasure = array();
        $arrSkuProductId = array();
        $heightSGP 		 = 0;
        $widthSGP 		 = 0;
        $lengthSGP 		 = 0;
        $countLabelSGP 	 = 0;

        if (!array_key_exists('available_services', $this->credentials)) {
            throw new InvalidArgumentException("Nao foi identificado o tipo de contrato\n" . json_encode($this->credentials));
        }

        $arrQuote = array(
            'cep_origem'        => $dataQuote['zipcodeSender'],
            'cep_destino'       => $dataQuote['zipcodeRecipient'],
            'formato'           => "1",
            'mao_propria'       => "N",
            'aviso_recebimento' => "N",
            'servicos'          => array_values($this->credentials['available_services'])
        );

        foreach ($dataQuote['items'] as $sku) {

            $qtyLabel           = 0;
            $rate               = $sku['valor'] / $sku['quantidade'];
            $lengthItem         = $sku['comprimento'] * 100;
            $widthItem          = $sku['largura'] * 100;
            $heightItem         = $sku['altura'] * 100;
            $productId          = $dataQuote['dataInternal'][$sku['sku']]['prd_id'];

            if (isset($sku['variant'])) {
                $productId = $productId.'-'.$sku['variant'];
            } else {
                $productId = $productId.'-S';
            }

            if ($moduloFrete) {
                $arrSkuProductId[$productId] = $sku['sku'];
            }

            // Produto não tem condições para ser entregue por SGP.
            if (!$this->itsCorreios(array(
                'peso'          => $sku['peso'],
                'altura'        => $heightItem,
                'largura'       => $widthItem,
                'profundidade'  => $lengthItem,
                'rate'          => $rate,
            ))) {
                continue;
            }

            $product = $this->dbReadonly->where('id', $dataQuote['dataInternal'][$sku['sku']]['prd_id'])->get('products')->row_array();
            $qtyProductsPackage = $product['products_package'] ?? 1;

            $qtyToCalculation = ceil($sku['quantidade'] / $qtyProductsPackage);

            for ($qtyItem_Loop = 1; $qtyItem_Loop <= $qtyToCalculation; $qtyItem_Loop++) {
                $qtyItem = ($sku['quantidade'] - ($qtyItem_Loop * $qtyProductsPackage));

                if ($qtyItem >= 0) {
                    $qtyItem = $qtyProductsPackage;
                } else {
                    $qtyItem = $sku['quantidade'] - (($qtyItem_Loop - 1) * $qtyProductsPackage);
                }

                $lengthSGP  += $lengthItem;
                $widthSGP   += $widthItem;
                $heightSGP  += $heightItem;
                $peso_item  = $sku['peso'];
                $peso_validate = 0;

                if (isset($dataFreight[$countLabelSGP][$productId])) {
                    $peso_validate = $peso_item + $dataFreight[$countLabelSGP][$productId]['peso_bruto'];
                }

                if ($lengthSGP > 70 || $widthSGP > 70 || $heightSGP > 70 || $peso_validate > 30) {
                    $countLabelSGP++;
                    $lengthSGP  = $lengthItem;
                    $widthSGP   = $widthItem;
                    $heightSGP  = $heightItem;
                }

                if ($widthSGP < $lengthSGP) {
                    $smallestMeasure[$countLabelSGP] = "L";
                }

                if ($heightSGP < $lengthSGP) {
                    $smallestMeasure[$countLabelSGP] = "A";
                }

                if ($lengthSGP <= $widthSGP && $lengthSGP <= $heightSGP) {
                    $smallestMeasure[$countLabelSGP] = "C";
                }

                if ($widthSGP <= $lengthSGP && $widthSGP <= $heightSGP) {
                    $smallestMeasure[$countLabelSGP] = "L";
                }

                if ($heightSGP <= $lengthSGP && $heightSGP <= $widthSGP) {
                    $smallestMeasure[$countLabelSGP] = "A";
                }

                if ($heightSGP == $lengthSGP && $heightSGP == $widthSGP) {
                    $smallestMeasure[$countLabelSGP] = "A";
                }

                $peso_item *= $qtyItem;
                $rate_item = $rate * $qtyItem;

                if (isset($dataFreight[$countLabelSGP][$productId])) {
                    $rate_item += $dataFreight[$countLabelSGP][$productId]['rate'];
                    $peso_item += $dataFreight[$countLabelSGP][$productId]['peso_bruto'];

                    $qtyLabel++;
                } else {
                    $qtyLabel = 1;
                }

                $dataFreight[$countLabelSGP][$productId] = array(
                    'altura'        => $heightItem,
                    'largura'       => $widthItem,
                    'profundidade'  => $lengthItem,
                    'peso_bruto'    => $peso_item,
                    'qty'           => $qtyLabel,
                    "rate"          => (float)$rate_item
                );
            }
            if ($moduloFrete) {
                $countLabelSGP++;
                $lengthSGP  = 0;
                $widthSGP   = 0;
                $heightSGP  = 0;
            }
        }

        $promises = array();

        foreach ($dataFreight as $id_etiqueta => $etiqueta) {
            $weight      = 0;
            $length      = 0;
            $width       = 0;
            $height      = 0;
            $rateProduct = 0;
            $id_product  = 0;

            foreach ($etiqueta as $id_product => $product) {
                if ($smallestMeasure[$id_etiqueta] == null) {
                    $smallestMeasure[$id_etiqueta] = "A";
                }

                if ($product['profundidade'] == 0 || $product['altura'] == 0 || $product['largura'] == 0 || $product['peso_bruto'] == 0) {
                    throw new InvalidArgumentException("Foi encontrado medidas zerada na cotação na SGP Web\n" . Utils::jsonEncode(['dataFreight' => $dataFreight, 'smallestMeasure' => $smallestMeasure]));
                }

                switch ($smallestMeasure[$id_etiqueta]) {
                    case "L":
                        $length = max($length, (float)$product['profundidade']);
                        $height = max($height, (float)$product['altura']);

                        $width += (float)$product['largura'] * (int)$product['qty'];
                        break;
                    case "C":
                        $height = max($height, (float)$product['altura']);
                        $width = max($width, (float)$product['largura']);

                        $length += $product['profundidade'] * (int)$product['qty'];
                        break;
                    case "A":
                        $width = max($width, (float)$product['largura']);
                        $length = max($length, (float)$product['profundidade']);

                        $height += (float)$product['altura'] * (int)$product['qty'];
                        break;
                }

                $weight += (float)$product['peso_bruto'];
                $rateProduct += (float)$product['rate'];
            }

            $arrQuote['identificador']   = $id_product;
            $arrQuote['peso']            = (string) roundDecimal($weight);
            $arrQuote['comprimento']     = (string) roundDecimal($this->ajustaComprimento($length));
            $arrQuote['altura']          = (string) roundDecimal($this->ajustaAltura($height));
            $arrQuote['largura']         = (string) roundDecimal($this->ajustaLargura($width));
            $arrQuote['valor_declarado'] = roundDecimal($this->ajustaValorDeclarado($rateProduct));

            $oldProductId = explode("-", $id_product);
            $prdId = $oldProductId[0];

            $promises["$arrSkuProductId[$id_product]:$prdId:$id_etiqueta"] = array('json' => $arrQuote);
        }
        
        $services = array();
        $arrServices = $this->getQuoteUnit($promises, $moduloFrete);

        foreach ($arrServices as $arrService) {
            foreach ($arrService as $service) {
                $services[] = array(
                    'prd_id'    => $service['prd_id'] ?? null,
                    'skumkt'    => $service['skumkt'] ?? null,
                    'quote_id'  => $service['quote_id'],
                    'method_id' => $service['method_id'],
                    'value'     => (float) $service['value'],
                    'deadline'  => (int) $service['deadline'] + $dataQuote['crossDocking'],
                    'method'    => $service['method'],
                    'provider'  => $service['provider']
                );
            }
        }

        $new_services = [];
        foreach ($services as $service) {
            foreach ($new_services as $key_new_service => $new_service) {
                if ($new_service['skumkt'] == $service['skumkt'] && $new_service['method'] == $service['method']) {
                    $new_services[$key_new_service]['value'] += $service['value'];
                    if ($new_services[$key_new_service]['deadline'] < $service['deadline']) {
                        $new_services[$key_new_service]['deadline'] = $service['deadline'];
                    }
                    continue 2;
                }
            }

            $new_services[] = $service;
        }
        
        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $new_services
            )
        );
    }

    public function getQuoteUnit(array $promises, bool $moduloFrete): array
    {
        $countPackagePerProduct = 0;
        $arrServices = array();
        $arrMeasures = array();
        $responses = $this->requestAsync('POST', "/consulta-precos-prazos", $promises);
        
        foreach ($responses as $skumkt_product => $content_response) {
            $data_quote = $promises[$skumkt_product]['json'];
            $exp_prd_id_skumkt = explode(':', $skumkt_product);
            $prd_id = $exp_prd_id_skumkt[1];
            $skumkt = $exp_prd_id_skumkt[0];

            $content_response = is_object($content_response) && property_exists($content_response, 'identificador') ? ($content_response->servicos ?? []) : $content_response;

            // não encontrou transportadora
            if (
                !is_array((array)$content_response) ||
                !count((array)$content_response)
            ) {
                throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na SGP Web\n" . Utils::jsonEncode($content_response));
            }

            $servicesByCode = array_flip($this->credentials['available_services']);

            if ($moduloFrete) {
                $countPackagePerProduct++;
            }

            $arrMeasures['peso']            = $data_quote['peso'];
            $arrMeasures['comprimento']     = $data_quote['comprimento'];
            $arrMeasures['altura']          = $data_quote['altura'];
            $arrMeasures['largura']         = $data_quote['largura'];
            $arrMeasures['rate']            = $data_quote['valor_declarado'];

            foreach ($content_response as $service) {
                $idService      = str_pad($service->Codigo ?? null, 5, "0", STR_PAD_LEFT);
                $valueDelivery  = moneyToFloat($service->Valor ?? 0);

                // encontrou um erro, provavelmente sem precificação.
                if (empty($valueDelivery) || empty((int)$idService)) {
                    continue;
                }

                $codeService = $servicesByCode[$idService];
                if (!array_key_exists($countPackagePerProduct, $arrServices)) {
                    $arrServices[$countPackagePerProduct] = array();
                }

                if (array_key_exists($codeService, $arrServices[$countPackagePerProduct])) {
                    $arrServices[$countPackagePerProduct][$codeService]['value'] += $valueDelivery;
                    if ($service->PrazoEntrega > $arrServices[$countPackagePerProduct][$codeService]['deadline']) {
                        $arrServices[$countPackagePerProduct][$codeService]['deadline'] = $service->PrazoEntrega;
                    }

                    continue;
                }

                if ($codeService == 'MINI' && !$this->correiosMiniVerification($arrMeasures)) {
                    continue;
                }

                $arrServices[$countPackagePerProduct][$codeService] = array(
                    'prd_id'    => $moduloFrete ? $prd_id : null,
                    'skumkt'    => $moduloFrete ? $skumkt : null,
                    'quote_id'  => NULL,
                    'method_id' => $idService,
                    'value'     => $valueDelivery,
                    'deadline'  => $service->PrazoEntrega,
                    'method'    => $codeService,
                    'provider'  => 'CORREIOS - ' . $codeService,
                    'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                );
            }
        }

        return $arrServices;
    }

    /**
     * Retorna o valor ajustado.
     *
     * @param   float       $value      Valor a ser validado.
     * @param   string|null $service    Serviço para retorno com precisão.
     * @return  float
     */
    public function ajustaValorDeclarado(float $value, ?string $service = null): float
    {

        $sql = 'select * from settings where name = ?';
		$query = $this->dbReadonly->query($sql, array('valor_declarado_correios'));
		$setting = $query->row_array();

		if (!$setting || $setting['status'] == 2) {
            $minimo = 25.63;
        }else{
            $minimo = $setting['value'];
		}


        if ($service === null) {
            $value = max($value, $minimo);
            $value = min($value, 10000);
        } else if (strtolower($service) == 'mini') {
            $value = max($value, $minimo);
            $value = min($value, 100);
        } else if (strtolower($service) == 'pac') {
            $value = max($value, $minimo);
            $value = min($value, 3000);
        } else if (strtolower($service) == 'sedex') {
            $value = max($value, $minimo);
            $value = min($value, 10000);
        }

        return $value;
    }

    /**
     * Retorna o comprimento ajustado.
     *
     * @param   float       $depth      Valor a ser validado.
     * @param   string|null $service    Serviço para retorno com precisão.
     * @return  int
     */
    public function ajustaComprimento(float $depth, ?string $service = null): int
    {
        if ($service === null) {
            $depth = max($depth, 16);
            $depth = min($depth, 100);
        } else if (strtolower($service) == 'mini') {
            $depth = max($depth, 15);
            $depth = min($depth, 24);
        } else if (strtolower($service) == 'sedex' || strtolower($service) == 'pac') {
            $depth = max($depth, 16);
            $depth = min($depth, 100);
        }

        return $depth;
    }

    /**
     * Retorna a altura ajustado.
     *
     * @param   float       $height     Valor a ser validado.
     * @param   string|null $service    Serviço para retorno com precisão.
     * @return  int
     */
    public function ajustaAltura(float $height, ?string $service = null): int
    {
        if ($service === null) {
            $height = max($height, 2);
            $height = min($height, 100);
        } else if (strtolower($service) == 'mini') {
            $height = max($height, 1);
            $height = min($height, 4);
        } else if (strtolower($service) == 'sedex' || strtolower($service) == 'pac') {
            $height = max($height, 2);
            $height = min($height, 100);
        }

        return $height;
    }

    /**
     * Retorna a largura ajustado.
     *
     * @param   float       $width      Valor a ser validado.
     * @param   string|null $service    Serviço para retorno com precisão.
     * @return  int
     */
    public function ajustaLargura(float $width, ?string $service = null): int
    {
        if ($service === null) {
            $width = max($width, 11);
            $width = min($width, 100);
        } else if (strtolower($service) == 'mini') {
            $width = max($width, 10);
            $width = min($width, 16);
        } else if (strtolower($service) == 'sedex' || strtolower($service) == 'pac') {
            $width = max($width, 11);
            $width = min($width, 100);
        }

        return $width;
    }

    public function correiosMiniVerification(array $item): bool
    {
        $altura = $item['altura'];
        $largura = $item['largura'];
        $comprimento = $item['comprimento'];
        $rate = $item['rate'];
        $peso = $item['peso'] * 1000;

        return ($rate <= 100 && $peso <= 300 && $comprimento <= 24 && $largura <= 16 && $altura <= 4);
    }

    /**
     * @param   array $item
     * @return  bool
     */
    public function itsCorreiosMini(array $item): bool
    {
        $altura = $item['altura'];
        $largura = $item['largura'];
        $comprimento = $item['profundidade'];
        $rate = $item['rate'];

        $peso = ceil($altura * $largura * $comprimento / 6000);

        if ($peso > 5) {
            $aux_peso = $item['peso'];
            if ($peso < $aux_peso) {
                $peso = $aux_peso;
            }
        }

        return ($rate <= 100 && $peso <= 300 && $comprimento <= 24 && $largura <= 16 && $altura <= 4);
    }

    public function itsCorreios($item): bool
    {
        $altura      = $item['altura'];
        $largura     = $item['largura'];
        $comprimento = $item['profundidade'];
        $rate        = $item['rate'];
        $peso        = $item['peso'];
        $peso_cubico = ceil(($altura * $largura * $comprimento) / 6000);

        return (
            $peso           <= 30 &&
            $peso_cubico    <= 30 &&
            $largura        <= 100 &&
            $comprimento    <= 100 &&
            $altura         <= 100 &&
            ($altura + $largura + $comprimento) <= 200 &&
            $rate           <= 10000
        );
    }


    /**
     * Contrata frete pelo SGP
     *
     * @param array	$order		Dados do pedido
     * @param array	$store		Dados da loja
     * @param array	$nfe		Dados de nota fiscal
     * @param array $client		Dados do client
     */
    public function hireFreight(array $order, array $store, array $nfe, array $client)
    {
        $this->setClientGuzzle(10);
        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_freights');

        $log_name = __CLASS__.'/'.__FUNCTION__;

        $serviceIsCorreios          = in_array(strtoupper($order['ship_service_preview']), array('PAC', 'SEDEX', 'MINI'));
        $altura_correios 			= 0;
        $largura_correios 			= 0;
        $comprimento_correios 		= 0;
        $weight_correios 		    = 0;
        $etiquetas_correios 		= array();
        $menor_medida_correios 		= array();
        $count_etiqueta_correios 	= 0;
        $frete_real_order 			= 0;
        $arrObjetos 				= array();
        $ship_service 				= $serviceIsCorreios ? strtoupper($order['ship_service_preview']) : 'PAC';
        $cepOrigem					= onlyNumbers($store['zipcode']);
        $cepDestino 				= onlyNumbers($order['customer_address_zip']);
        $order['quote_frete_taxa'] 	= 0;
        $order['frete_real'] 		= 0;
        $itens 						= $this->model_orders->getOrdersItemData($order['id']);
        $servicesSGP                = $this->credentials['available_services'];

        $sempreSedex = false;
        // se for loja 155 no conecta, deve sempre ir pro SEDEX, loja 1p
        if ($this->sellerCenter === 'conectala' && ($store['id'] == 155 || $order['origin'] == 'ML'|| $order['origin'] =='MLC')) {
            if (!isset($servicesSGP['SEDEX'])) {
                throw new InvalidArgumentException('Ambiente não parametrizado para utilizar o serviço SEDEX.');
            }

            $sempreSedex = true;
        }

        // monto a logica para criar as etiquetas
        foreach ($itens as $item) {
            $items_cancel = $this->model_order_items_cancel->getByOrderIdAndItem($order['id'], $item['id']);
            if ($items_cancel) {
                if ($items_cancel['qty'] != $item['qty']) {
                    $item['qty'] = $item['qty'] - $items_cancel['qty'];
                } else {
                    // O cancelamento é total, não deve considerar o produto.
                    continue;
                }
            }
            $qty_etiqueta = 0;

            $product = $this->dbReadonly->where('id', $item['product_id'])->get('products')->row_array();
            $qtyProductsPackage = $product['products_package'] ?? 1;

            $qtd_calulo = ceil($item['qty'] / $qtyProductsPackage);
            $rate = $item['rate'];
            $profundidade_item = $item['profundidade'];
            $largura_item = $item['largura'];
            $altura_item = $item['altura'];
            $productId = $item['product_id'];

            for ($qty_item = 1; $qty_item <= $qtd_calulo; $qty_item++) {

                $quantidade_item = ($item['qty'] - ($qty_item * $qtyProductsPackage));

                if ($quantidade_item >= 0) $quantidade_item = $qtyProductsPackage;
                else $quantidade_item = $item['qty'] - (($qty_item - 1) * $qtyProductsPackage);

                $peso_item = $item['pesobruto'];

                $comprimento_correios   += $profundidade_item;
                $largura_correios       += $largura_item;
                $altura_correios        += $altura_item;
                $weight_correios        += $peso_item;

                if ($comprimento_correios > 70 || $largura_correios > 70 || $altura_correios > 70 || $weight_correios > 30) {
                    $count_etiqueta_correios++;
                    $comprimento_correios   = $profundidade_item;
                    $largura_correios       = $largura_item;
                    $altura_correios        = $altura_item;
                    $weight_correios        = $peso_item;
                }

                if ($largura_correios < $comprimento_correios) {
                    $menor_medida_correios[$count_etiqueta_correios] = "L";
                }
                if ($altura_correios < $comprimento_correios) {
                    $menor_medida_correios[$count_etiqueta_correios] = "A";
                }

                if ($comprimento_correios <= $largura_correios && $comprimento_correios <= $altura_correios) {
                    $menor_medida_correios[$count_etiqueta_correios] = "C";
                }
                if ($largura_correios <= $comprimento_correios && $largura_correios <= $altura_correios) {
                    $menor_medida_correios[$count_etiqueta_correios] = "L";
                }
                if ($altura_correios <= $comprimento_correios && $altura_correios <= $largura_correios) {
                    $menor_medida_correios[$count_etiqueta_correios] = "A";
                }
                if ($altura_correios == $comprimento_correios && $altura_correios == $largura_correios) {
                    $menor_medida_correios[$count_etiqueta_correios] = "A";
                }

                $peso_item *= $quantidade_item;
                $rate_item = $rate * $quantidade_item;

                if (isset($etiquetas_correios[$count_etiqueta_correios][$productId])) {
                    $rate_item += $etiquetas_correios[$count_etiqueta_correios][$productId]['rate'];
                    $peso_item += $etiquetas_correios[$count_etiqueta_correios][$productId]['peso_bruto'];

                    $qty_etiqueta++;
                } else
                    $qty_etiqueta = 1;

                $etiquetas_correios[$count_etiqueta_correios][$productId] = array(
                    'altura' => $altura_item,
                    'largura' => $largura_item,
                    'profundidade' => $profundidade_item,
                    'peso_bruto' => $peso_item,
                    'qty' => $qty_etiqueta,
                    "name" => $item['name'],
                    "rate" => (float)$rate_item
                );
            }
        }

        foreach ($etiquetas_correios as $id_etiqueta => $etiqueta) {

            $peso 				= 0;
            $comprimento 		= 0;
            $largura 			= 0;
            $altura 			= 0;
            $valor_declarado 	= 0;
            $quantidade 		= 0;
            $total_ship_item 	= 0;

            foreach($etiqueta as $id_product => $product) {

                if ($menor_medida_correios[$count_etiqueta_correios] == null) {
                    $menor_medida_correios[$count_etiqueta_correios] = "A";
                }

                if($product['profundidade'] == 0 || $product['altura'] == 0 || $product['largura'] == 0 || $product['peso_bruto'] == 0) {
                    echo 'Foi encontrado medidas zerada. Pedido=' . $order['id'] . " Produto=$id_product Medidas=" . print_r(array('profundidade' => $product['profundidade'], 'altura' => $product['altura'], 'largura' => $product['largura'], 'peso_bruto' => $product['peso_bruto']), true) . "\n";
                    throw new InvalidArgumentException('Produto do pedido com dimensões zeradas.');
                }

                switch ($menor_medida_correios[$id_etiqueta]) {
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
                $valor_declarado += (float)$product['rate'];
                $quantidade += (int)$product['qty'];

            }

            // verifico se o serviço existe mapeado no parâmetro para pegar o ID do serviço.
            if (!isset($servicesSGP[$ship_service])) {
                $ship_service = 'PAC';
            }

            $serviceId = $sempreSedex ? $servicesSGP['SEDEX'] : $servicesSGP[$ship_service]; // inicia com serviço do pedido
            $serviceName = $sempreSedex ? 'SEDEX' : $ship_service; // inicia com serviço do pedido

            // Muda pra PAC caso chegue MINI e não pode ser MINI.
            /*$arrMini = array(
                'rate'          => $this->ajustaValorDeclarado($valor_declarado, $serviceName),
                'peso'          => $peso,
                'profundidade'  => $this->ajustaComprimento($comprimento, $serviceName),
                'largura'       => $this->ajustaLargura($largura, $serviceName),
                'altura'        => $this->ajustaAltura($altura, $serviceName),
            );*/

            $arrMeasures['peso']            = $peso;
            $arrMeasures['comprimento']     = $comprimento;
            $arrMeasures['altura']          = $altura;
            $arrMeasures['largura']         = $largura;
            $arrMeasures['rate']            = $valor_declarado;

            if ($serviceName == 'MINI' && !$this->correiosMiniVerification($arrMeasures)) {
                $serviceId = $servicesSGP['PAC'];
                $serviceName = "PAC";
            }

            // Verifica se é PAC
            if ($serviceId == $servicesSGP['PAC']) {

                $querySettingsServiceAllow = $this->model_settings->getSettingDatabyName('servicos_permitidos_simulation');
                $expServiceAllow = explode(',', $querySettingsServiceAllow['status']);

                // verifico se o seller center pode usar o serviço MINI
                if (
                    ($querySettingsServiceAllow['status'] == 1 && in_array('MINI', $expServiceAllow)) ||
                    ($querySettingsServiceAllow['status'] == 2)
                ) {
                    // Verifica se define o serviço como MINI
                    if (isset($servicesSGP['MINI']) && $this->correiosMiniVerification($arrMeasures)) {
                        $serviceId = $servicesSGP['MINI'];
                        $serviceName = "MINI";
                    }
                }
            }

            $arrCorreiosPrecoPrazo = array();
            $arrCorreiosPrecoPrazo['identificador'] 	= $order['id'];
            $arrCorreiosPrecoPrazo['cep_origem'] 		= $cepOrigem;
            $arrCorreiosPrecoPrazo['cep_destino'] 		= $cepDestino;
            $arrCorreiosPrecoPrazo['formato'] 			= "1";
            $arrCorreiosPrecoPrazo['peso'] 				= roundDecimal($peso, 3);
            $arrCorreiosPrecoPrazo['comprimento'] 		= roundDecimal($this->ajustaComprimento($comprimento, $serviceName));
            $arrCorreiosPrecoPrazo['altura'] 			= roundDecimal($this->ajustaAltura($altura, $serviceName));
            $arrCorreiosPrecoPrazo['largura'] 			= roundDecimal($this->ajustaLargura($largura, $serviceName));
            $arrCorreiosPrecoPrazo['valor_declarado'] 	= roundDecimal($this->ajustaValorDeclarado($valor_declarado, $serviceName));
            $arrCorreiosPrecoPrazo['mao_propria'] 		= "N";
            $arrCorreiosPrecoPrazo['aviso_recebimento'] = "N";
            $arrCorreiosPrecoPrazo['servicos'] 			= array_values($servicesSGP);

            try {
                $response = $this->request('POST', "/consulta-precos-prazos", array('json' => $arrCorreiosPrecoPrazo));
                $contentQuote = Utils::jsonDecode($response->getBody()->getContents(), true);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }

            $contentQuote = array_key_exists('identificador', $contentQuote) ? ($contentQuote['servicos'] ?? []) : $contentQuote;
            // não encontrou cotação para o serviço
            $getQuoteService = getArrayByValueIn($contentQuote, (int)$serviceId, 'Codigo');
            if(!isset($getQuoteService['Valor']) || $getQuoteService['Valor'] == "R$ 0,00"){
                // Serviço é mini
                if ($serviceName == 'MINI') {
                    // verifica com o serviço contrário (PAC <> SEDEX) pois o atual deu erro
                    echo "Testar serviço PAC pois o atual é MINI e deu erro\n";
                    $serviceId = $servicesSGP['PAC'];
                    $serviceName = 'PAC';
                    $getQuoteService = getArrayByValueIn($contentQuote, (int)$serviceId, 'Codigo');
                }

                if(!isset($getQuoteService['Valor']) || $getQuoteService['Valor'] == "R$ 0,00") {
                    // verifica com o serviço contrário (PAC <> SEDEX) pois o atual deu erro
                    echo "Testar serviço contrário (PAC <> SEDEX) pois o atual deu erro\n";
                    $serviceId = $serviceId == $servicesSGP['PAC'] ? $servicesSGP['SEDEX'] : $servicesSGP['PAC'];
                    $serviceName = $serviceName == 'PAC' ? 'SEDEX' : 'PAC';
                    $getQuoteService = getArrayByValueIn($contentQuote, (int)$serviceId, 'Codigo');

                    // encontrou erro
                    if (!isset($getQuoteService['Valor']) || $getQuoteService['Valor'] == "R$ 0,00") {
                        throw new InvalidArgumentException($getQuoteService['MsgErro']);
                    }
                }
            }

            if (!isset($getQuoteService['Valor']) || $getQuoteService['Valor'] == "R$ 0,00"){
                throw new InvalidArgumentException($getQuoteService['MsgErro'] ?? 'Erro desconhecido.');
            }

            get_instance()->log_data('batch',$log_name.'/precos-prazos', 'enviou='.json_encode($arrCorreiosPrecoPrazo) . ', recebeu=' . Utils::jsonEncode($contentQuote));

            $correios = array(
                "identificador" 		=> $order['id'],
                "destinatario" 			=> $order['customer_name'],
                "cpf_cnpj" 				=> onlyNumbers($client['cpf_cnpj']),
                "endereco" 				=> $order['customer_address'],
                "numero" 				=> $order['customer_address_num'],
                "bairro" 				=> $order['customer_address_neigh'],
                "cidade" 				=> $order['customer_address_city'],
                "uf" 					=> $order['customer_address_uf'],
                "cep" 					=> $cepDestino,
                "complemento" 			=> $order['customer_address_compl'],
                "servico_correios" 		=> $serviceId,
                "empresa" 				=> "", // Empresa do destinatário
                "remetente" 			=> $store['raz_social'],
                "endereco_remetente" 	=> $store['address'],
                "numero_remetente" 		=> $store['addr_num'],
                "complemento_remetente" => $store['addr_compl'],
                "bairro_remetente" 		=> $store['addr_neigh'],
                "cidade_remetente" 		=> $store['addr_city'],
                "uf_remetente"			=> $store['addr_uf'],
                "cep_remetente" 		=> $cepOrigem,
                "telefone_remetente" 	=> onlyNumbers($store['phone_1']),
                "email_remetente" 		=> $store['responsible_email'],
                "empresa_remetente" 	=> $store['raz_social'],
                "cpf_cnpj_remetente" 	=> onlyNumbers($store['CNPJ']),
                "nota_fiscal" 			=> str_pad((int)$nfe['nfe_num'] , 4 , '0' , STR_PAD_LEFT),
                "valor_declarado" 		=> $this->ajustaValorDeclarado(($valor_declarado + $order['total_ship']), $serviceName),
                "peso" 					=> (float)$peso * 1000,
                "comprimento" 			=> (float)$this->ajustaComprimento($comprimento, $serviceName),
                "largura" 				=> (float)$this->ajustaLargura($largura, $serviceName),
                "altura" 				=> (float)$this->ajustaAltura($altura, $serviceName),
                "tipo" 					=> 1, // 1 = pacote | 0 = envelope;
                "previamente_impressa" 	=> 0, // Indica se a etiqueta já foi impressa e não deverá aparecer na lista para impressão do SGPWEB (Booleano 1/0 ou string "S" /"N" );
//				"email" => $cliente['email'], // não vou enviar pois pode ultrapassar o limite de caracter
//				"telefone" => onlyNumbers($order['customer_phone']),
//				"plp" => "123456789",
//				"departamento" => "", // Departamento (se não informado, assume-se o padrão do SGPWEB);
//				"cartao" => "64789594", // Número do cartão de postagem;
//				"produto" => "", // Identificação do produto;
//				"aos_cuidados" => "João", // Nome pessoa responsável pelo recebimento
//				"maos_proprias" => "1", // Identifica se entrega será feita somente em mãos próprias do destinatário (Booleano 1/0 ou string "S" /"N" )
//				"aviso_recebimento" => "1", // Aviso de recebimento (Booleano 1/0 ou string "S" /"N" );
//				"observacao" => "Sua observação aqui...", // Observação que constará na etiqueta;
//				"monitorar" => "1", // flag utilizado para informar aos correios que deverá haver monitoramento via e-mail ou SMS para o número do destinatário;
//				"clique_retire_agencia" => "AC BRUMADINHO" // Quando a postagem possui a opção "Clique e Retire" deve ser preenchido com o destino;
            );

            echo "DADOS PRE-POSTAGEM = ".json_encode($correios)."\n";

            $data['objetos'] = array($correios);

            try {
                $response = $this->request('POST', "/pre-postagem", array('json' => $data));
                $contentHire = Utils::jsonDecode($response->getBody()->getContents());
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }

            $contentHire = $contentHire->retorno->objetos[0];

            if (isset($contentHire->erros)) {
                throw new InvalidArgumentException($contentHire->erros);
            }

            get_instance()->log_data('batch', $log_name.'/pre-postagem', "Pre-postagem realizada. PEDIDO={$order['id']} ENVIADO=".Utils::jsonEncode($data)." RETORNO=" . Utils::jsonEncode($contentHire));

            $rastreio = $contentHire->objeto;

            // Cria um array com os códigos de rastreio.
            $arrObjetos[] = trim($rastreio);

            $total_ship_item += moneyToFloat($getQuoteService['Valor']);

            // prazo previsto a partir do dia de hoje + o prazo de entrega da transportadora.
            $expectedDeadline = get_instance()->somar_dias_uteis(date("Y-m-d"), (int)$getQuoteService['PrazoEntrega'], '');

            // Dados do frete para cadastro/atualização.
            $freight = array(
                "order_id" 			=> $order['id'],
                "company_id" 		=> $order['company_id'],
                "ship_company" 		=> "CORREIOS",
                "method" 			=> $serviceName,
                "CNPJ" 				=> "34028316000103",
                "status_ship" 		=> "0",
                "date_delivered" 	=> "",
                "ship_value" 		=> $total_ship_item, // mostro o que foi pago e não o valor real da contratação.
                "prazoprevisto" 	=> $expectedDeadline,
                "idservico" 		=> $serviceId,
                "codigo_rastreio" 	=> $rastreio,
                "sgp" 				=> 1,
                'in_resend_active'	=> $order['in_resend_active']
            );

            $frete_real_order += $total_ship_item;

            // cria registro de frete de cada item
            foreach($etiqueta as $id_product => $product) {
                $freight["item_id"] = $id_product;
                // Cria dados do frete
                if (!$this->model_freights->create($freight)) {
                    throw new InvalidArgumentException('Ocorreu um problema para realizar a contratação do frete.');
                }
            }
        }

        $pathEtiquetas = $this->getPathLabel();

        $_fretes = $this->model_freights->getFreightsDataByOrderId($order['id']);

        $etiquetaA4 		= base_url("$pathEtiquetas/P_{$order['id']}_A4_{$order['in_resend_active']}.pdf");
        $etiquetaThermal    = base_url("$pathEtiquetas/P_{$order['id']}_Termica_{$order['in_resend_active']}.pdf");

        foreach ($_fretes as $_frete) {
            $_frete['link_etiqueta_a4'] = $etiquetaA4;
            $_frete['link_etiqueta_termica'] = $etiquetaThermal;
            $_frete['data_etiqueta'] = date('Y-m-d H:i:s');

            if (!($this->model_freights->replace($_frete))) {
                throw new InvalidArgumentException('Ocorreu um problema para realizar a contratação do frete.');
            }
        }

        $order['quote_frete_taxa'] 	+= (float)($order['total_ship'] - $frete_real_order);
        $order['frete_real'] 		+= $frete_real_order;

        $order['paid_status'] = 50; // Mantém o status no 50 para o lojista ver que precisa gerar a etiqueta
        $this->model_orders->updateByOrigin($order['id'], $order);

        // Transforma o array em string dividos por pipe
        $expCorreios = implode("|", $arrObjetos);

        $this->saveLabel($expCorreios, $order['id']);

        get_instance()->log_data('batch', $log_name, 'Frete contratado, pedido='.$order['id'] . ', etiqueta(s)='.$expCorreios);
        
        echo "Frete do pedido {$order['id']} contratado\n";
    }

    public function saveLabel(string $label, int $orderId)
    {
        $order = $this->model_orders->getOrdersData(0, $orderId);
        $pathEtiquetas = $this->getPathLabel();
        $endpoint_label = str_replace('/api', '', $this->endpoint);

        // Salvar pdf de etiqueta A4 e etiqueta térmica.
        // pdfUnitário | true = términa , false = A4.
        $getEtiquetaA4 = "$endpoint_label/webservice.php?opcao=pdf&key={$this->credentials['token']}&postObjeto=$label&ordem=id&pdfUnitario=false";
        $getEtiquetaThermal = "$endpoint_label/webservice.php?opcao=pdf&key={$this->credentials['token']}&postObjeto=$label&ordem=id&pdfUnitario=true";

        copy($getEtiquetaA4, FCPATH . "$pathEtiquetas/P_{$orderId}_A4_{$order['in_resend_active']}.pdf");
        copy($getEtiquetaThermal, FCPATH . "$pathEtiquetas/P_{$orderId}_Termica_{$order['in_resend_active']}.pdf");
    }

    public function saveLabelGroup(string $label, string $plp)
    {
        $pathEtiquetas = $this->getPathLabel();
        $endpoint_label = str_replace('/api', '', $this->endpoint);

        // Salvar pdf de etiqueta A4 e etiqueta térmica.
        // pdfUnitário | true = términa , false = A4.
        $getEtiquetaA4 = "$endpoint_label/webservice.php?opcao=pdf&key={$this->credentials['token']}&postObjeto=$label&ordem=id&pdfUnitario=false";
        $getEtiquetaThermal = "$endpoint_label/webservice.php?opcao=pdf&key={$this->credentials['token']}&postObjeto=$label&ordem=id&pdfUnitario=true";
        
        copy($getEtiquetaA4, FCPATH . "$pathEtiquetas/P_T_{$plp}_A4.pdf");
        copy($getEtiquetaThermal, FCPATH . "$pathEtiquetas/P_T_{$plp}_Termica.pdf");
    }

    /**
     * Consultar as ocorrências do rastreio para Correios
     *
     * @param   array   $codesCorreios  Código dos correios separado por vírgulas.
     * @return  void                    Retorna o status do rastreio.
     */
    public function tracking_old(array $codesCorreios): void
    {
        $this->setClientGuzzle(10);
        $this->load->model('model_settings');
        $this->load->model('model_freights');

        $log_name        = __CLASS__.'/'.__FUNCTION__;
        $credentialsCorreios = $this->model_settings->getValueIfAtiveByName('credentials_correios');

        if (!$credentialsCorreios) {
            throw new InvalidArgumentException('Parâmetro "credentials_correios" não configurado.');
        }

        $dataCredentials = json_decode($credentialsCorreios);

        $params = array();

        // setando os parâmetros de inicialização.
        if (isset($dataCredentials->user) && isset($dataCredentials->pass)) {
            $params = array('user' => $dataCredentials->user, 'pass' => $dataCredentials->pass);
        }

        // note que: mesmo que não sejam passados parâmetros,
        // a classe deve funcionar corretamente com os parâmetros padrões.
        Rastrear::init( $params );

        echo json_encode($codesCorreios)."\n\n";
        // rastreando um objeto.
        $trackingCorreios = Rastrear::get(implode('',$codesCorreios));
        /*$trackingCorreios = json_decode('{
            "numero": "OO715738074BR",
            "sigla": "OO",
            "nome": "ETIQUETA LOGICA PAC",
            "categoria": "ENCOMENDA PAC",
            "evento": [
                {
                    "tipo": "DO",
                    "status": "01",
                    "data": "10/02/2021",
                    "hora": "14:33",
                    "descricao": "Objeto entregue ao destinatário",
                    "local": "Unidade de Tratamento",
                    "codigo": "00000000",
                    "cidade": "FLORIANÓPOLIS",
                    "uf": "SC",
                    "destino": {
                        "local": "Unidade de Distribuição",
                        "codigo": "00000000",
                        "cidade": "FLORIANÓPOLIS",
                        "bairro": "SARANDI",
                        "uf": "SC"
                    }
                },
                {
                    "tipo": "DO",
                    "status": "01",
                    "data": "10/02/2021",
                    "hora": "14:33",
                    "descricao": "Objeto saiu para entrega ao desinatário",
                    "local": "Unidade de Tratamento",
                    "codigo": "00000000",
                    "cidade": "FLORIANÓPOLIS",
                    "uf": "SC",
                    "destino": {
                        "local": "Unidade de Distribuição",
                        "codigo": "00000000",
                        "cidade": "FLORIANÓPOLIS",
                        "bairro": "SARANDI",
                        "uf": "SC"
                    }
                },
                {
                    "tipo": "RO",
                    "status": "01",
                    "data": "03/02/2021",
                    "hora": "09:18",
                    "descricao": "Objeto em trânsito - por favor aguarde",
                    "local": "Agência dos Correios",
                    "codigo": "00000000",
                    "cidade": "SAO PAULO",
                    "uf": "SP",
                    "destino": {
                        "local": "Unidade de Tratamento",
                        "codigo": "00000000",
                        "cidade": "FLORIANÓPOLIS",
                        "bairro": "EMPRESARIAL COLINA",
                        "uf": "SC"
                    }
                },
                {
                    "tipo": "PO",
                    "status": "01",
                    "data": "01/02/2021",
                    "hora": "16:56",
                    "descricao": "Objeto postado",
                    "local": "Agência dos Correios",
                    "codigo": "00000000",
                    "cidade": "SAO PAULO",
                    "uf": "SP"
                }
            ]
        }');*/

        if (isset($trackingCorreios->numero) && $trackingCorreios->numero === 'Erro') {
            echo json_encode($trackingCorreios)."\n";
            echo "vou tentar sem credencial dos correios\n";
            Rastrear::init();
            $trackingCorreios = array();
            foreach ($codesCorreios as $code) {
                $respCorreios = Rastrear::get($code);
                $trackingCorreios[] = $respCorreios;
            }
            echo json_encode($trackingCorreios)."\n";
        }

        // continuou com erro
        if (isset($trackingCorreios->numero) && $trackingCorreios->numero === 'Erro') {
            echo "continuou com erro, vou parar o rastreio nos correios\n";
            echo json_encode($trackingCorreios)."\n";
            throw new InvalidArgumentException("Não foi possível obter dados de rastreio.\n" . json_encode($trackingCorreios));
        }

        if (isset($trackingCorreios->erro)) {
            echo 'Erro ao consultar rastreios dos Correios' . json_encode($trackingCorreios) . "\n";
            get_instance()->log_data('batch', $log_name, 'Erro ao consultar rastreios dos Correios' . json_encode($trackingCorreios), "E");
            throw new InvalidArgumentException("Ocorreu um erro para obter dados de rastreio.\n" . json_encode($trackingCorreios));
        }

        $dataLabelsOccurrence = $trackingCorreios->objeto ?? array($trackingCorreios);

        if (is_array($trackingCorreios) && isset($trackingCorreios[0]->numero)) {
            $dataLabelsOccurrence = $trackingCorreios;
        }

        foreach ($dataLabelsOccurrence as $obj) {

            $orders = $this->model_freights->getOrderByCodeTracking($obj->numero);

            foreach ($orders as $order) {

                $frete = $this->model_freights->getFreightForCodeTracking($order['id'], $obj->numero);

                // verificando se retornou erro.
                // os erros normalmente indicam um objeto não encontrado.
                if (isset($obj->erro)) {
                    echo "Pedido {$order['id']} ( $obj->numero ) - encontrou erro: $obj->erro\nBODY_CORREIOS".json_encode($obj)."\n\n";
                    if ($obj->erro != "Objeto não encontrado na base de dados dos Correios.") { // fluxo normal objto não postado, nos piores dos caso não encontrado mesmo
                        echo 'Erro ao rastreio o objeto: ' . $frete['codigo_rastreio'] . ' do pedido ' . $order['id'] . ' frete ' . $frete['id'] . '. Mensagem:' . $obj->erro . "\n";
                        get_instance()->log_data('batch', $log_name, 'Erro ao rastreio o objeto: ' . $frete['codigo_rastreio'] . ' do pedido ' . $order['id'] . ' frete ' . $frete['id'] . '. Mensagem:' . $obj->erro, "E");
                    }
                    continue;
                }

                // NOTA: caso objeto rastreado possua apenas 1 evento.
                // Correios retorna o evento dentro de um Object e não um Array.
                if (is_object($obj->evento)) {
                    $tmp = array();
                    $tmp[] = $obj->evento;
                    $obj->evento = $tmp;
                }

                if (!isset($obj->evento[0])) { // objeto encontrado, mas sem status.
                    echo 'Codigo de Rastreio ' . $frete['codigo_rastreio'] . ' do pedido ' . $order['id'] . ' frete ' . $frete['id'] . ' não encontrou registro. Retorno:' . json_encode($obj, true) . "\n";
                    get_instance()->log_data('batch', $log_name, 'Codigo de Rastreio ' . $frete['codigo_rastreio'] . ' do pedido ' . $order['id'] . ' frete ' . $frete['id'] . ' não encontrou registro. Retorno:' . json_encode($obj, true), "E");
                    continue;
                }

                // inverte array para vir por primeiro os mais antigos.
                $ordemCertaReg = array_reverse($obj->evento);

                foreach ($ordemCertaReg as $ev) {

                    // valida se não existe dados.
                    if (!isset($ev->data) ||
                        !isset($ev->hora) ||
                        !isset($ev->descricao)
                    ) {
                        echo 'Foi encontrado erro de informação no recebimento de informação ' . $frete['codigo_rastreio'] . ' do pedido ' . $order['id'] . ' frete ' . $frete['id'] . '. Retorno:' . json_encode($ev, true) . "\n";
                        get_instance()->log_data('batch', $log_name, 'Foi encontrado erro de informação no recebimento de informação ' . $frete['codigo_rastreio'] . ' do pedido ' . $order['id'] . ' frete ' . $frete['id'] . '. Retorno:' . json_encode($ev, true), "E");
                        continue;
                    }

                    $nameOccurrence = $ev->descricao;

                    if (
                        $nameOccurrence == "Objeto em trânsito - por favor aguarde" &&
                        (
                            !isset($ev->destino->local) ||
                            !isset($ev->destino->cidade) ||
                            !isset($ev->destino->uf)
                        )
                    ) {
                        $nameOccurrence = "Em trânsito de $ev->local";
                    } elseif ($ev->descricao == "Objeto em trânsito - por favor aguarde")
                        $nameOccurrence = "Em trânsito para {$ev->destino->local} - {$ev->destino->cidade}/{$ev->destino->uf}";

                    $dataOccurrence = array(
                        'name'              => $nameOccurrence,
                        'description'       => $ev->detalhe ?? null,
                        'code'              => $ev->status ?? 0,
                        'code_name'         => $nameOccurrence,
                        'type'              => $ev->tipo ?? '',
                        'date'              => DateTime::createFromFormat('d/m/Y', $ev->data)->format('Y-m-d') . " $ev->hora:00",
                        'statusOrder'       => $order['paid_status'],
                        'freightId'         => $frete['id'],
                        'orderId'           => $order['id'],
                        'trackingCode'      => $frete['codigo_rastreio'],
                        'address_place'     => $ev->destino->local ?? ($ev->local ?? null),
                        'address_name'      => $ev->destino->logradouro ?? ($ev->endereco->logradouro ?? null),
                        'address_number'    => $ev->destino->numero ?? ($ev->endereco->numero ?? null),
                        'address_zipcode'   => $ev->destino->cep ?? ($ev->endereco->cep ?? null),
                        'address_neigh'     => $ev->destino->bairro ?? ($ev->endereco->bairro ?? null),
                        'address_city'      => $ev->destino->cidade ?? ($ev->endereco->localidade ?? null),
                        'address_state'     => $ev->destino->uf ?? ($ev->endereco->uf ?? null)
                    );

                    $this->setNewRegisterOccurrence($dataOccurrence);
                }
            }
        }
    }

    /**
     * Consultar as ocorrências do rastreio para Correios
     *
     * @param   array   $order  Dados do pedido.
     * @param   array   $frete  Dados do frete.
     * @throws  Exception
     */
    public function tracking(array $rastreios): void
    {

        unset($this->credentials['token']);

        $this->setEndpoint('https://api.exitoinf.com.br');
        $this->setClientGuzzle(10);
        $this->load->model('model_freights');

        $apptype = 'gestao';
        if (array_key_exists('type_integration', $this->credentials)) {
            if ($this->credentials['type_integration'] === 'sgpweb') {
                $apptype = 'sgpweb';
            }
        }
      
        $key_redis_sgp = $this->sellerCenter.":tokensgp";
       
        $tokenRedis = null;
        if($this->redis && $this->redis->is_connected){
            $tokenRedis = $this->redis->get($key_redis_sgp);
        }

        if(empty($tokenRedis)){
            // Chama o método de autenticação
            $tokenRedis = $this->authExito($apptype);
            if($this->redis && $this->redis->is_connected){
                $this->redis->setex($key_redis_sgp, 7200, $tokenRedis);
            }
        }
        
        $auth = array();

        // $auth['headers']['Authorization'] = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6NDQ2NTAsInVzdWFyaW8iOiIwMjQzMDU5IiwiYXBwbGljYXRpb24iOiJnZXN0YW8iLCJrZXkiOiJlNGEwMTAyMDFiYWZiYjkyNzE4ZGFkODM5YmNhMTY4NSIsImlhdCI6MTY5NjYyNjIzMX0.5xm6dak89gRt4M32QbQLPwMduCAmduflpi2matO5l8o';
        $auth['headers']['Authorization'] = 'Bearer ' . $tokenRedis;
        $auth['headers']['x-apptype']     = $apptype;

        $this->authRequest  = $auth;

        $tracking_codes = [];
        foreach($rastreios as $rastreio){
            $tracking_codes[] = $rastreio;
        }
        
        $max_codes_per_request = 400;
       
        // Divide os códigos em lotes de 1000 e processa cada lote separadamente
        $tracking_code_batches = array_chunk($tracking_codes, $max_codes_per_request);

        $attempt_limit = 0;
        while (true) {
            if ($attempt_limit > 5) {
                echo "Tentou mais que 5 vezes, será interrompido.\n";
                break;
            }
            try {
                $batch = array_shift($tracking_code_batches); // Pega o próximo lote

                if (empty($batch)) {
                    break; // Todos os lotes foram processados
                }

                $batch_tracking_codes = implode(',', $batch);

                $options = array(
                    'query' => array( 
                        'codigosRastreio'  => $batch_tracking_codes,
                        'resultado' => 'T'
                    )
                );
                
                $response = $this->request('GET', "/servicos/rastreio", $options);
                $contentQuote = Utils::jsonDecode($response->getBody()->getContents());
                //zeramos o attemp para garantir 5 tentativas para cada novo bloco
                $attempt_limit = 0;
            } catch (InvalidArgumentException $exception) {
                $attempt_limit++;
                $message = $exception->getMessage();
                if (likeText('%Too Many Requests%', $message)) {
                    echo "Tentando novamente...";
                }
                echo "$message\n";
                continue;
            }

            foreach ($contentQuote as $item) {
                if (property_exists($item, 'erro')) {
                    $message = "ERRO Tracking ($item->numero) - " . $item->erro;
                    echo $message . "\n";
                    continue;
                }

                if (!property_exists($item, 'evento')) {
                    $message = "ERRO Tracking ($item->numero) - " . json_encode($item);
                    echo $message . "\n";
                    continue;
                }
            
                $events = array_reverse($item->evento);

                $codigoRastro = $item->numero;

                $fretes = $this->model_freights->getOrderByCodeTracking($codigoRastro);

                foreach ($fretes as $frete){
               
                    foreach ($events as $event) {

                        // valida se não existe dados.
                        if (
                            !property_exists($event, 'data') ||
                            !property_exists($event, 'hora') ||
                            !property_exists($event, 'descricao')
                        ) {
                            echo "Foi encontrado erro de informação no recebimento de informação {$codigoRastro} frete {$frete['freight_id']}.  Retorno:" . json_encode($event, true) . "\n";
                            continue;
                        }
    
                        $nameOccurrence = $event->descricao;
    
                        if (
                            $nameOccurrence == "Objeto em trânsito - por favor aguarde" &&
                            (
                                !property_exists($event, 'local') ||
                                !property_exists($event, 'cidade') ||
                                !property_exists($event, 'uf')
                            )
                        ) {
                            $nameOccurrence = "Em trânsito de $event->local";
                        } elseif ($event->descricao == "Objeto em trânsito - por favor aguarde") {
                            $nameOccurrence = "Em trânsito para {$event->local} - {$event->cidade}/{$event->uf}";
                        }
    
                        $dataOccurrence = array(
                            'name'              => $nameOccurrence,
                            'description'       => $event->detalhe ?? null,
                            'code'              => $event->status ?? 0,
                            'code_name'         => $nameOccurrence,
                            'type'              => $event->tipo ?? '',
                            'date'              => DateTime::createFromFormat('d/m/Y H:i:s', $event->data . " $event->hora:00")->format('Y-m-d H:i:s'),
                            'statusOrder'       => $frete['paid_status'],
                            'freightId'         => $frete['freight_id'],
                            'orderId'           => $frete['id'],
                            'trackingCode'      => $codigoRastro,
                            'address_place'     => $event->local       ?? ($event->local ?? null),
                            'address_name'      => $event->logradouro  ?? ($event->endereco->logradouro ?? null),
                            'address_number'    => $event->numero      ?? ($event->endereco->numero ?? null),
                            'address_zipcode'   => $event->cep         ?? ($event->endereco->cep ?? null),
                            'address_neigh'     => $event->bairro      ?? ($event->endereco->bairro ?? null),
                            'address_city'      => $event->cidade      ?? ($event->endereco->localidade ?? null),
                            'address_state'     => $event->uf          ?? ($event->endereco->uf ?? null)
                        );
    
                        $this->setNewRegisterOccurrence($dataOccurrence);
                    }
                    
                }
            }
        }
    }

     /**
     * Gerar o token novo no sgp/gestao ou usar o do redis
     *
     * @throws  Exception
     */
    public function authExito(string $apptype)
    {
        $credentialsCorreios = $this->model_settings->getValueIfAtiveByName('credentials_sgpV2');

        if (!$credentialsCorreios) {
            throw new InvalidArgumentException('Parâmetro "credentials_sgpV2" não configurado.');
        }

        $dataCredentials = json_decode($credentialsCorreios);

        $params = array();
        
        // setando os parâmetros de inicialização.
        if (isset($dataCredentials->user) && isset($dataCredentials->pass)) {
            $params = array('usuario' => $dataCredentials->user, 'senha' => $dataCredentials->pass);
        }

        $options = [
            'json' => $params,
            'headers' => [
                'x-apptype' => $apptype,
            ],
        ];

        try {
            $response = $this->request('POST', '/auth/login', $options);
            $authResult = json_decode($response->getBody()->getContents(), true);

            if (isset($authResult['token'])) {
                return $authResult['token'];
            }

        } catch (\Exception $e) {
            throw new InvalidArgumentException("Erro durante a autenticação: " . $e->getMessage(), 0, $e);
        }
    }
}