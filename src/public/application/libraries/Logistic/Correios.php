<?php

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

class Correios extends Logistic
{
    protected $additional_service = array(
        '03220' => '019',
        '04162' => '019',
        '03298' => '064',
        '04669' => '064',
        '04227' => '065'
    );

    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $endpoint = 'https://apihom.correios.com.br';

//        if (likeText('%production%', ENVIRONMENT)) {
            $endpoint = 'https://api.correios.com.br';
//        }

        $this->setEndpoint($endpoint);
        $token = $this->generateToken();

        $auth['headers']['Authorization'] = "Bearer $token";

        $this->authRequest = $auth;
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse()
    {
    }

    /**
     * Recuperar/gera token.
     *
     * @return  string|null
     */
    private function generateToken(): ?string
    {
        // Consulta dados de credenciais em cache.
        $store_id = $this->freightSeller ? $this->store : 0;
        $key_redis = "$this->sellerCenter:integration_logistic:$this->logistic:$store_id:token";
        if ($this->redis && $this->redis->is_connected) {
            $data_redis = $this->redis->get($key_redis);
            // Encontrou algo em cache.
            if ($data_redis !== null) {
                $data_redis         = json_decode($data_redis, true);
                $temp_token         = $data_redis['temp_token'];
                $token_expires_at   = $data_redis['token_expires_at'];

                // Token ainda é válido.
                if (strtotime($token_expires_at) > strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {
                    return $temp_token;
                }
            }
        }

        $credentials_integration = $this->credentials;

        // Se não existe token, data de expiração ou a data já expirou, gera um novo token.
        if (empty($this->credentials['temp_token']) || empty($this->credentials['token_expires_at']) || strtotime($this->credentials['token_expires_at']) < strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {
            $user = $this->credentials['user'] ?? null;
            $password = $this->credentials['password'] ?? null;
            $post_card = $this->credentials['post_card'] ?? null;

            // Informação pendente.
            if (empty($user) || empty($password) || empty($post_card)) {
                throw new InvalidArgumentException("Usuário ou senha inválido.");
            }

            // Consulta o novo token.
            $response = $this->request('POST', '/token/v1/autentica/cartaopostagem', [
                'auth' => array(
                    $user,
                    $password
                ), 'json' => array(
                    'numero' => $post_card
                )
            ]);

            $contentQuote = Utils::jsonDecode($response->getBody()->getContents(), true);

            $temp_token         = $contentQuote['token'] ?? null;
            $token_expires_at   = $contentQuote['expiraEm'] ?? null;
            $dr_se              = $contentQuote['cartaoPostagem']['dr'] ?? null;

            // Conseguiu recuperar o token e data de expiração.
            if ($temp_token && $token_expires_at && $dr_se) {
                $credentials_update = array(
                    'dr_se' => $dr_se,
                    'temp_token' => $temp_token,
                    'token_expires_at' => $token_expires_at
                );
                $this->credentials = $credentials_integration = array_merge($this->credentials, $credentials_update);
                // Salva o token e a data de expiração no banco para não precisar gerar o token novamente, caso não tenha cache.
                if (!$this->ms_shipping_carrier->use_ms_shipping) {
                    $this->db->where(['integration' => $this->logistic, 'store_id' => $this->freightSeller ? $this->store : 0])->update('integration_logistic', array('credentials' => json_encode($credentials_integration, JSON_UNESCAPED_UNICODE)));
                }

                if ($this->redis && $this->redis->is_connected) {
                    try {
                        // Pega o data de expiração e converte em minutos.
                        $now_date = new DateTime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL));
                        $exp_date = new DateTime($token_expires_at);

                        $time_redis = ($exp_date->diff($now_date)->h * 3600) + ($exp_date->diff($now_date)->i * 60);

                        // Remove 30 min do tempo.
                        if ($time_redis - 1800 > 0) {
                            $time_redis -= 1800;
                        }

                        // Salva em cache.
                        $this->redis->setex($key_redis, $time_redis, json_encode(array('temp_token' => $temp_token, 'token_expires_at' => $token_expires_at), JSON_UNESCAPED_UNICODE));
                        $this->redis->setex("$this->sellerCenter:credencials_integration:$this->store", 3600, json_encode($credentials_integration, JSON_UNESCAPED_UNICODE));
                    } catch (Exception $exception) {}
                }
            } else {
                throw new InvalidArgumentException("Não foi possível identificar o Token, Data de expiração ou DR");
            }
        }

        return $credentials_integration['temp_token'] ?? null;
    }

    /**
     * Cotação.
     *
     * @param array $dataQuote Dados para realizar a cotação.
     * @param bool $moduloFrete Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        $dataFreight = array();
        $smallestMeasure = array();
        $arrSkuProductId = array();
        $heightSGP = 0;
        $widthSGP = 0;
        $lengthSGP = 0;
        $countLabelSGP = 0;

        if (!array_key_exists('available_services', $this->credentials)) {
            throw new InvalidArgumentException("Nao foi identificado o tipo de contrato\n" . json_encode($this->credentials));
        }

        if (!array_key_exists('contract', $this->credentials)) {
            throw new InvalidArgumentException("Nao foi identificado o contrato\n" . json_encode($this->credentials));
        }

        foreach ($dataQuote['items'] as $sku) {

            $qtyLabel = 0;
            $rate = $sku['valor'] / $sku['quantidade'];
            $lengthItem = $sku['comprimento'] * 100;
            $widthItem = $sku['largura'] * 100;
            $heightItem = $sku['altura'] * 100;
            $productId = $dataQuote['dataInternal'][$sku['sku']]['prd_id'];

            if (isset($sku['variant'])) {
                $productId = $productId . '-' . $sku['variant'];
            } else {
                $productId = $productId . '-S';
            }

            $arrSkuProductId[$productId] = $sku['sku'];

            // Produto não tem condições para ser entregue por SGP.
            if (!$this->itsCorreios(array(
                'peso' => $sku['peso'],
                'altura' => $heightItem,
                'largura' => $widthItem,
                'profundidade' => $lengthItem,
                'rate' => $rate,
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

                $lengthSGP += $lengthItem;
                $widthSGP += $widthItem;
                $heightSGP += $heightItem;
                $peso_item = $sku['peso'];
                $peso_validate = 0;

                if (isset($dataFreight[$countLabelSGP][$productId])) {
                    $peso_validate = $peso_item + $dataFreight[$countLabelSGP][$productId]['peso_bruto'];
                }

                if ($lengthSGP > 70 || $widthSGP > 70 || $heightSGP > 70 || $peso_validate > 30) {
                    $countLabelSGP++;
                    $lengthSGP = $lengthItem;
                    $widthSGP = $widthItem;
                    $heightSGP = $heightItem;
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
                    'altura' => $heightItem,
                    'largura' => $widthItem,
                    'profundidade' => $lengthItem,
                    'peso_bruto' => $peso_item,
                    'qty' => $qtyLabel,
                    "rate" => (float)$rate_item
                );
            }
        }

        $promises = array();
        $procuts_to_quote = array();

        foreach ($dataFreight as $id_etiqueta => $etiqueta) {
            $weight = 0;
            $length = 0;
            $width = 0;
            $height = 0;
            $rateProduct = 0;
            $id_product = 0;
            $procuts_to_quote_label = array();

            foreach ($etiqueta as $id_product => $product) {
                $procuts_to_quote_label[] = array(
                    'prd_id' => $id_product,
                    'skumkt' => $arrSkuProductId[$id_product]
                );
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

            $product_params_price = array();
            $product_params_deadline = array();
            $contracts = array_values($this->credentials['available_services']);

            foreach ($contracts as $service) {
                $additional_service = $this->additional_service[$service] ?? null;

                if (empty($additional_service)) {
                    continue;
                }

                $product_params_price[] = array(
                    "coProduto"     => $service,
                    "nuRequisicao"  => "1",
                    "tpObjeto"      => "2", // 1 - Envelope, 2 - Caixa/Pacote; 3 - Rolo/Cilindro
                    "nuContrato"    => $this->credentials['contract'],
                    "nuDR"          => $this->credentials['dr_se'],
                    "cepOrigem"     => $dataQuote['zipcodeSender'],
                    "cepDestino"    => $dataQuote['zipcodeRecipient'],
                    "psObjeto"      => roundDecimal($weight, 3) * 1000,
                    "comprimento"   => roundDecimal($this->ajustaComprimento($length)),
                    "largura"       => roundDecimal($this->ajustaLargura($width)),
                    "altura"        => roundDecimal($this->ajustaAltura($height)),
                    "servicosAdicionais" => array(
                        array(
                            "coServAdicional" => $additional_service
                        )
                    ),
                    "vlDeclarado"   => roundDecimal($this->ajustaValorDeclarado($rateProduct))
                );
                $product_params_deadline[] = array(
                    "coProduto" => $service,
                    "nuRequisicao" => "1",
                    "cepOrigem" => $dataQuote['zipcodeSender'],
                    "cepDestino" => $dataQuote['zipcodeRecipient']
                );
            }

            $arrQuote_price = array(
                'idLote' => time(),
                'parametrosProduto' => $product_params_price
            );
            $arrQuote_deadline = array(
                'idLote' => time(),
                'parametrosPrazo' => $product_params_deadline
            );

            $oldProductId = explode("-", $id_product);
            $prdId = $oldProductId[0];


            $procuts_to_quote["$arrSkuProductId[$id_product]:$prdId:$id_etiqueta"] = $procuts_to_quote_label;

            $promises["$arrSkuProductId[$id_product]:$prdId:$id_etiqueta:price"] = array('json' => $arrQuote_price);
            $promises["$arrSkuProductId[$id_product]:$prdId:$id_etiqueta:deadline"] = array('json' => $arrQuote_deadline);
        }

        $services = array();
        $arrServices = $this->getQuoteUnit($promises, $procuts_to_quote);

        foreach ($arrServices as $arrService) {
            foreach ($arrService as $service) {
                $services[] = array(
                    'prd_id' => $service['prd_id'] ?? null,
                    'skumkt' => $service['skumkt'] ?? null,
                    'quote_id' => $service['quote_id'],
                    'method_id' => $service['method_id'],
                    'value' => (float)$service['value'],
                    'deadline' => (int)$service['deadline'] + $dataQuote['crossDocking'],
                    'method' => $service['method'],
                    'provider' => $service['provider'],
                    'quote_json'=> $service['quote_json']
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
            'success' => true,
            'data' => array(
                'services' => $new_services
            )
        );
    }

    /**
     * Formata os dados da cotação.
     *
     * @param array $promises
     * @return array
     */
    public function getQuoteUnit(array $promises, array $procuts_to_quote): array
    {
        $countPackagePerProduct = 0;
        $arrServices = array();
        $arrMeasures = array();
        $responses = $this->requestAsync('POST', [':price' => '/preco/v1/nacional', ':deadline' => '/prazo/v1/nacional'], $promises);

        foreach ($responses as $skumkt_product => $content_response) {
            // Não ler prazo, será lido através do preço.
            if (likeText('%:deadline%', $skumkt_product)) {
                continue;
            }

            // Não tem prazo.
            if (!array_key_exists(str_replace(':price', ':deadline', $skumkt_product), $responses)) {
                continue;
            }

            $data_quote = $promises[$skumkt_product]['json']['parametrosProduto'][0];
            $exp_prd_id_skumkt = explode(':', $skumkt_product);
            $prd_id = $exp_prd_id_skumkt[1];
            $skumkt = $exp_prd_id_skumkt[0];
            $products_quoted = $procuts_to_quote[str_replace(':price','',$skumkt_product)];

            $servicesByCode = array_flip($this->credentials['available_services']);

            $value_service_produto = array();
            foreach ($products_quoted as $key_quoted => $product_quoted) {
                $countPackagePerProduct++;

                $arrMeasures['peso'] = $data_quote['psObjeto'];
                $arrMeasures['comprimento'] = $data_quote['comprimento'];
                $arrMeasures['altura'] = $data_quote['altura'];
                $arrMeasures['largura'] = $data_quote['largura'];
                $arrMeasures['rate'] = $data_quote['vlDeclarado'];

                foreach ($content_response as $service) {
                    $idService = str_pad($service->coProduto ?? null, 5, "0", STR_PAD_LEFT);
                    $valueDelivery = moneyToFloat($service->pcFinal ?? 0);

                    // encontrou um erro, provavelmente sem precificação.
                    if (!empty($service->txErro) || empty($valueDelivery) || empty((int)$idService)) {
                        continue;
                    }

                    if (!array_key_exists($idService, $value_service_produto)) {
                        $value_service_produto[$idService] = 0;
                    }

                    if (($key_quoted + 1) == count($products_quoted)) {
                        $valueDelivery -= $value_service_produto[$idService];
                    } else {
                        $valueDelivery = roundDecimal($valueDelivery / count($products_quoted));
                        $value_service_produto[$idService] += $valueDelivery;
                    }

                    $deadline_quote_service = getArrayByValueIn($responses[str_replace(':price', ':deadline', $skumkt_product)], $idService, 'coProduto');

                    // Não encontrou prazo para o serviço.
                    if (empty($deadline_quote_service)) {
                        continue;
                    }

                    $deadline = $deadline_quote_service->prazoEntrega;

                    $codeService = $servicesByCode[$idService];
                    if (!array_key_exists($countPackagePerProduct, $arrServices)) {
                        $arrServices[$countPackagePerProduct] = array();
                    }

                    if (array_key_exists($codeService, $arrServices[$countPackagePerProduct])) {
                        $arrServices[$countPackagePerProduct][$codeService]['value'] += $valueDelivery;
                        if ($deadline > $arrServices[$countPackagePerProduct][$codeService]['deadline']) {
                            $arrServices[$countPackagePerProduct][$codeService]['deadline'] = $deadline;
                        }

                        continue;
                    }

                    // Não é mini.
                    if ($codeService == 'MINI' && !$this->correiosMiniVerification($arrMeasures)) {
                        continue;
                    }

                    $arrServices[$countPackagePerProduct][$codeService] = array(
                        'prd_id'    => explode('-',$product_quoted['prd_id'])[0],
                        'skumkt'    => $product_quoted['skumkt'],
                        'quote_id'  => NULL,
                        'method_id' => $idService,
                        'value'     => $valueDelivery,
                        'deadline'  => $deadline,
                        'method'    => $codeService,
                        'provider'  => "CORREIOS - $codeService",
                        'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                    );
                }
            }
        }

        return $arrServices;
    }

    /**
     * Retorna o valor ajustado.
     *
     * @param float $value Valor a ser validado.
     * @param string|null $service Serviço para retorno com precisão.
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
            $value = max($value,  $minimo);
            $value = min($value, 10000);
        } else if (strtolower($service) == 'mini') {
            $value = max($value,  $minimo);
            $value = min($value, 100);
        } else if (strtolower($service) == 'pac') {
            $value = max($value,  $minimo);
            $value = min($value, 3000);
        } else if (strtolower($service) == 'sedex') {
            $value = max($value,  $minimo);
            $value = min($value, 10000);
        }

        return $value;
    }

    /**
     * Retorna o comprimento ajustado.
     *
     * @param float $depth Valor a ser validado.
     * @param string|null $service Serviço para retorno com precisão.
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
     * @param float $height Valor a ser validado.
     * @param string|null $service Serviço para retorno com precisão.
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
     * @param float $width Valor a ser validado.
     * @param string|null $service Serviço para retorno com precisão.
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

    /**
     * Verifica se o objeto é serviço MINI.
     *
     * @param array $item
     * @return bool
     */
    public function correiosMiniVerification(array $item): bool
    {
        $altura = $item['altura'];
        $largura = $item['largura'];
        $comprimento = $item['comprimento'];
        $rate = $item['rate'];
        $peso = $item['peso'];

        return ($rate <= 100 && $peso <= 300 && $comprimento <= 24 && $largura <= 16 && $altura <= 4);
    }

    /**
     * Verificar se o objeto é Correios.
     *
     * @param $item
     * @return bool
     */
    public function itsCorreios($item): bool
    {
        $altura = $item['altura'];
        $largura = $item['largura'];
        $comprimento = $item['profundidade'];
        $rate = $item['rate'];
        $peso = $item['peso'];
        $peso_cubico = ceil(($altura * $largura * $comprimento) / 6000);

        return (
            $peso <= 30 &&
            $peso_cubico <= 30 &&
            $largura <= 100 &&
            $comprimento <= 100 &&
            $altura <= 100 &&
            ($altura + $largura + $comprimento) <= 200 &&
            $rate <= 10000
        );
    }


    /**
     * Contrata frete pelo SGP
     *
     * @param array $order Dados do pedido
     * @param array $store Dados da loja
     * @param array $nfe Dados de nota fiscal
     * @param array $client Dados do client
     */
    public function hireFreight(array $order, array $store, array $nfe, array $client)
    {
        $this->setClientGuzzle(10);
        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_freights');

        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $serviceIsCorreios = in_array(strtoupper($order['ship_service_preview']), array('PAC', 'SEDEX', 'MINI'));
        $altura_correios = 0;
        $largura_correios = 0;
        $comprimento_correios = 0;
        $weight_correios = 0;
        $etiquetas_correios = array();
        $menor_medida_correios = array();
        $count_etiqueta_correios = 0;
        $frete_real_order = 0;
        $arrObjetos = array();
        $ship_service = $serviceIsCorreios ? strtoupper($order['ship_service_preview']) : 'PAC';
        $cepOrigem = onlyNumbers($store['zipcode']);
        $cepDestino = onlyNumbers($order['customer_address_zip']);
        $order_update['quote_frete_taxa'] = 0;
        $order_update['frete_real'] = 0;
        $itens = $this->model_orders->getOrdersItemData($order['id']);
        $servicesSGP    = $this->credentials['available_services'];
        $pathEtiquetas  = $this->getPathLabel();
        $etiquetaA4     = base_url("$pathEtiquetas/P_{$order['id']}_A4_{$order['in_resend_active']}.pdf");

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

                $comprimento_correios += $profundidade_item;
                $largura_correios += $largura_item;
                $altura_correios += $altura_item;
                $weight_correios += $peso_item;

                if ($comprimento_correios > 70 || $largura_correios > 70 || $altura_correios > 70 || $weight_correios > 30) {
                    $count_etiqueta_correios++;
                    $comprimento_correios = $profundidade_item;
                    $largura_correios = $largura_item;
                    $altura_correios = $altura_item;
                    $weight_correios = $peso_item;
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
                    'altura'        => $altura_item,
                    'largura'       => $largura_item,
                    'profundidade'  => $profundidade_item,
                    'peso_bruto'    => $peso_item,
                    'qty'           => $qty_etiqueta,
                    "name"          => $item['name'],
                    "rate"          => (float)$rate_item
                );
            }
        }

        foreach ($etiquetas_correios as $id_etiqueta => $etiqueta) {

            $peso = 0;
            $comprimento = 0;
            $largura = 0;
            $altura = 0;
            $valor_declarado = 0;
            $total_ship_item = 0;

            foreach ($etiqueta as $id_product => $product) {

                if ($menor_medida_correios[$count_etiqueta_correios] == null) {
                    $menor_medida_correios[$count_etiqueta_correios] = "A";
                }

                if ($product['profundidade'] == 0 || $product['altura'] == 0 || $product['largura'] == 0 || $product['peso_bruto'] == 0) {
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
            }

            // verifico se o serviço existe mapeado no parâmetro para pegar o ID do serviço.
            if (!isset($servicesSGP[$ship_service])) {
                $ship_service = 'PAC';
            }

            // Nome do serviço.
            $serviceName = $ship_service;
            // Código do serviço.
            $serviceId = $servicesSGP[$serviceName] ?? null;

            // Serviço não configurado. Trocar.
            if (is_null($serviceId)) {
                if (in_array('pac', $this->credentials['services'])) {
                    $serviceName = 'PAC';
                } elseif (in_array('sedex', $this->credentials['services'])) {
                    $serviceName = 'SEDEX';
                } elseif (in_array('mini', $this->credentials['services'])) {
                    $serviceName = 'MINI';
                } else {
                    throw new InvalidArgumentException("Serviços mal configurados. " . json_encode($this->credentials['services'], JSON_UNESCAPED_UNICODE));
                }

                // Trocou de serviço, pegar o novo código.
                $serviceId = $servicesSGP[$serviceName];
            }

            // Monta um vetor para validar o serviço MINI.
            $arrMeasures = array(
                'peso'          => $peso * 1000,
                'comprimento'   => $comprimento,
                'altura'        => $altura,
                'largura'       => $largura,
                'rate'          => $valor_declarado
            );

            // Valida se o serviço MINI pode ser atendido, caso não, deverá trocar de serviço.
            if ($serviceName == 'MINI' && !$this->correiosMiniVerification($arrMeasures)) {
                if (in_array('pac', $this->credentials['services'])) {
                    $serviceName = 'PAC';
                } elseif (in_array('sedex', $this->credentials['services'])) {
                    $serviceName = 'SEDEX';
                } else {
                    throw new InvalidArgumentException("Serviço MINI é o único configurado e não pode ser atendido.");
                }

                $serviceId = $servicesSGP[$serviceName];
            }

            // Monta request para fazer cotação.
            $product_params_price    = array();
            $product_params_deadline = array();
            $contracts = array_values($this->credentials['available_services']);

            foreach ($contracts as $service) {
                $additional_service = $this->additional_service[$service] ?? null;

                if (empty($additional_service)) {
                    continue;
                }

                $product_params_price[] = array(
                    "coProduto"     => $service,
                    "nuRequisicao"  => "1",
                    "nuContrato"    => $this->credentials['contract'],
                    "nuDR"          => $this->credentials['dr_se'],
                    "cepOrigem"     => $cepOrigem,
                    "cepDestino"    => $cepDestino,
                    "psObjeto"      => roundDecimal($peso, 3) * 1000,
                    "comprimento"   => roundDecimal($this->ajustaComprimento($comprimento, $serviceName)),
                    "largura"       => roundDecimal($this->ajustaLargura($largura, $serviceName)),
                    "altura"        => roundDecimal($this->ajustaAltura($altura, $serviceName)),
                    "servicosAdicionais" => array(
                        array(
                            "coServAdicional" => $additional_service
                        )
                    ),
                    "vlDeclarado"   => roundDecimal($this->ajustaValorDeclarado($valor_declarado, $serviceName))
                );

                $product_params_deadline[] = array(
                    "coProduto" => $service,
                    "nuRequisicao" => "1",
                    "cepOrigem" => $cepOrigem,
                    "cepDestino" => $cepDestino
                );
            }

            $arrCorreiosPrice = array(
                'idLote' => time(),
                'parametrosProduto' => $product_params_price
            );
            $arrCorreiosDeadline = array(
                'idLote' => time(),
                'parametrosPrazo' => $product_params_deadline
            );

            // Consulta o Preço.
            try {
                $response = $this->request('POST', "/preco/v1/nacional", array('json' => $arrCorreiosPrice));
                $contentQuote = Utils::jsonDecode($response->getBody()->getContents(), true);
            } catch (InvalidArgumentException $exception) {
                $message = $exception->getMessage();

                $message_decode = json_decode($message);
                if (!empty($message_decode) && is_array($message_decode)) {
                    $message = implode(' | ', array_filter(
                        array_map(function($error){
                            if (!empty($error->txErro)) {
                                return $error->txErro;
                            }
                            return null;
                        }, $message_decode),
                            function($error) {
                            return $error;
                        })
                    );
                }

                throw new InvalidArgumentException($message);
            }

            // Consulta o Prazo.
            try {
                $response = $this->request('POST', "/prazo/v1/nacional", array('json' => $arrCorreiosDeadline));
                $contentQuoteDeadline = Utils::jsonDecode($response->getBody()->getContents(), true);
            } catch (InvalidArgumentException $exception) {
                $message = $exception->getMessage();

                $message_decode = json_decode($message);
                if (!empty($message_decode) && is_array($message_decode)) {
                    $message = implode(' | ', array_filter(
                        array_map(function($error){
                            if (!empty($error->txErro)) {
                                return $error->txErro;
                            }
                            return null;
                        }, $message_decode),
                        function($error) {
                            return $error;
                        })
                    );
                }

                throw new InvalidArgumentException($message);
            }

            // não encontrou cotação para o serviço
            $getQuoteService = getArrayByValueIn($contentQuote, $serviceId, 'coProduto');
            if (!isset($getQuoteService['pcFinal']) || $getQuoteService['pcFinal'] == '0,00' || !empty($getQuoteService['txErro'])) {
                // Serviço é mini, precisa trocar, pois não pode ser atendido.
                if ($serviceName == 'MINI') {
                    // Verifica com o serviço contrário (PAC <> SEDEX) pois o atual deu erro
                    echo "Testar serviço PAC pois o atual é MINI e deu erro\n";
                    $serviceId = $servicesSGP['PAC'];
                    $serviceName = 'PAC';
                    $getQuoteService = getArrayByValueIn($contentQuote, $serviceId, 'coProduto');
                }

                if (!isset($getQuoteService['pcFinal']) || $getQuoteService['pcFinal'] == '0,00' || !empty($getQuoteService['txErro'])) {
                    // verifica com o serviço contrário (PAC <> SEDEX) pois o atual deu erro
                    echo "Testar serviço contrário (PAC <> SEDEX) pois o atual deu erro\n";
                    $serviceId = $serviceId == $servicesSGP['PAC'] ? $servicesSGP['SEDEX'] : $servicesSGP['PAC'];
                    $serviceName = $serviceName == 'PAC' ? 'SEDEX' : 'PAC';
                    $getQuoteService = getArrayByValueIn($contentQuote, $serviceId, 'coProduto');

                    // encontrou erro
                    if (!isset($getQuoteService['pcFinal']) || $getQuoteService['pcFinal'] == '0,00' || !empty($getQuoteService['txErro'])) {
                        throw new InvalidArgumentException($getQuoteService['txErro']);
                    }
                }
            }

            if (!empty($getQuoteService['txErro'])) {
                throw new InvalidArgumentException($getQuoteService['txErro']);
            }

            $getQuoteServiceDeadline = getArrayByValueIn($contentQuoteDeadline, $serviceId, 'coProduto');

            get_instance()->log_data('batch', $log_name . '/preco/v1/nacional', 'enviou=' . json_encode($arrCorreiosPrice) . ', recebeu=' . Utils::jsonEncode($contentQuote));

            $phone1_store = separatePhoneAndDdd(onlyNumbers($store['phone_1']));
            $phone2_store = separatePhoneAndDdd(onlyNumbers($store['phone_2']));
            $phone1_client = separatePhoneAndDdd(onlyNumbers($client['phone_1']));
            $phone2_client = separatePhoneAndDdd(onlyNumbers($client['phone_2']));

            $correios = [
                "idCorreios" => $this->credentials['user'],
                "remetente" => [
                    /*"dddTelefone" => "48",
                    "telefone" => "33330000",
                    "dddCelular" => "48",
                    "celular" => "996677961",*/
                    "nome"      => $store['raz_social'],
                    "email"     => $store['responsible_email'],
                    "cpfCnpj"   => onlyNumbers($store['CNPJ']),
                    "endereco"  => [
                        "cep"           => $cepOrigem,
                        "logradouro"    => $store['address'],
                        "numero"        => $store['addr_num'],
                        "complemento"   => $store['addr_compl'],
                        "bairro"        => $store['addr_neigh'],
                        "cidade"        => $store['addr_city'],
                        "uf"            => $store['addr_uf']
                    ]
                ],
                "destinatario" => [
                    /*"dddTelefone" => "21",
                    "telefone" => "88885555",
                    "dddCelular" => "21",
                    "celular" => "123456789",*/
                    "nome"      => $order['customer_name'],
                    "email"     => $client['email'],
                    "cpfCnpj"   => onlyNumbers($client['cpf_cnpj']),
                    "endereco"  => [
                        "cep"           => $cepDestino,
                        "logradouro"    => $order['customer_address'],
                        "numero"        => $order['customer_address_num'],
                        "complemento"   => $order['customer_address_compl'],
                        "bairro"        => $order['customer_address_neigh'],
                        "cidade"        => $order['customer_address_city'],
                        "uf"            => $order['customer_address_uf']
                    ]
                ],
                "codigoServico"         => $serviceId,
                "precoServico"          => roundDecimal($order['total_ship']),
                "precoPrePostagem"      => roundDecimal($this->ajustaValorDeclarado($valor_declarado, $serviceName) + roundDecimal($order['total_ship'])),
                "numeroNotaFiscal"      => (int)$nfe['nfe_num'],
                "numeroCartaoPostagem"  => $this->credentials['post_card'],
                "chaveNFe"              => trim($nfe['chave']),
                "listaServicoAdicional" => [
                    [
                        "codigoServicoAdicional" => $this->additional_service[$serviceId],
                        "valorDeclarado"         => roundDecimal($this->ajustaValorDeclarado($valor_declarado, $serviceName))
                    ]
                ],
                "pesoInformado"                 => roundDecimal($peso * 1000, 0),
                "codigoFormatoObjetoInformado"  => 2, // 1 - Envelope, 2 - Caixa/Pacote; 3 - Rolo/Cilindro
                "alturaInformada"               => roundDecimal($this->ajustaAltura($altura, $serviceName)),
                "larguraInformada"              => roundDecimal($this->ajustaLargura($largura, $serviceName)),
                "comprimentoInformado"          => roundDecimal($this->ajustaComprimento($comprimento, $serviceName)),
                "cienteObjetoNaoProibido"       => 1
            ];

            $correios = $this->addPhoneToHire($correios, $phone1_store, 'remetente');
            $correios = $this->addPhoneToHire($correios, $phone2_store, 'remetente');
            $correios = $this->addPhoneToHire($correios, $phone1_client, 'destinatario');
            $correios = $this->addPhoneToHire($correios, $phone2_client, 'destinatario');

            echo "DADOS PRE-POSTAGEM = " . json_encode($correios, JSON_UNESCAPED_UNICODE) . "\n";

            try {
                $response = $this->request('POST', "/prepostagem/v1/prepostagens", array('json' => $correios));
                $contentHire = Utils::jsonDecode($response->getBody()->getContents());
            } catch (InvalidArgumentException $exception) {
                $message = $exception->getMessage();

                $message_decode = json_decode($message, true);
                if (!empty($message_decode['msgs']) && is_array($message_decode['msgs'])) {
                    $message = implode(' | ', $message_decode['msgs']);
                }

                throw new InvalidArgumentException($message);
            }

            $tracking_code = $contentHire->codigoObjeto ?? null;
            $tracking_id = $contentHire->id ?? null;

            if (empty($tracking_code) || empty($tracking_id)) {
                get_instance()->log_data('batch', $log_name, "Código de rastreio ou código da contratação não encontrado para realizar a pré postagem. PEDIDO={$order['id']} ENVIADO=" . Utils::jsonEncode($correios, JSON_UNESCAPED_UNICODE) . " RETORNO=" . Utils::jsonEncode($contentHire, JSON_UNESCAPED_UNICODE), 'E');
                throw new InvalidArgumentException('Código de rastreio ou código da contratação não encontrado para realizar a pré postagem.');
            }

            get_instance()->log_data('batch', $log_name . '/prepostagem/v1/prepostagens', "Pre-postagem realizada. PEDIDO={$order['id']} ENVIADO=" . Utils::jsonEncode($correios, JSON_UNESCAPED_UNICODE) . " RETORNO=" . Utils::jsonEncode($contentHire, JSON_UNESCAPED_UNICODE));

            // Cria um array com os códigos de rastreio.
            $arrObjetos[] = trim($tracking_code);

            $total_ship_item += moneyToFloat($getQuoteService['pcFinal']);

            // prazo previsto a partir do dia de hoje + o prazo de entrega da transportadora.
            $deadline = $getQuoteServiceDeadline['prazoEntrega'] ?? $order['ship_time_preview'];
            $expectedDeadline = get_instance()->somar_dias_uteis(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL), (int)$deadline);

            // Dados do frete para cadastro/atualização.
            $freight = array(
                "order_id"          => $order['id'],
                "company_id"        => $order['company_id'],
                "ship_company"      => "CORREIOS",
                "method"            => $serviceName,
                "CNPJ"              => "34028316000103",
                "status_ship"       => "0",
                "date_delivered"    => "",
                "ship_value"        => $total_ship_item, // mostro o que foi pago e não o valor real da contratação.
                "prazoprevisto"     => $expectedDeadline,
                "idservico"         => $serviceId,
                "codigo_rastreio"   => $tracking_code,
                "sgp"               => $this->type_logistic_to_hire[$this->logistic],
                'shipping_order_id' => $tracking_id,
                'in_resend_active'  => $order['in_resend_active'],
                'volume'            => $id_etiqueta + 1,
                'link_etiqueta_a4'  => $etiquetaA4,
                'data_etiqueta'     => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL),
            );

            $frete_real_order += $total_ship_item;

            // cria registro de frete de cada item
            foreach ($etiqueta as $id_product => $product) {
                $freight["item_id"] = $id_product;
                // Cria dados do frete
                if (!$this->model_freights->create($freight)) {
                    throw new InvalidArgumentException('Ocorreu um problema para realizar a contratação do frete.');
                }
            }
        }

        $order_update['quote_frete_taxa'] += (float)($order['total_ship'] - $frete_real_order);
        $order_update['frete_real'] += $frete_real_order;
        $order_update['paid_status'] = 50; // Mantém o status no 50 para o lojista ver que precisa gerar a etiqueta

        $this->model_orders->updateByOrigin($order['id'], $order_update);

        // Transforma o array em string divididos por pipe.
        $expCorreios = implode("|", $arrObjetos);

        $this->saveLabel($arrObjetos, $order['id'], $order);

        get_instance()->log_data('batch', $log_name, "Frete contratado, pedido=$order[id], etiqueta(s)=$expCorreios");

        echo "Frete do pedido $order[id] contratado\n";
    }

    /**
     *  Salva etiqueta.
     *
     * @param   array|string $arrObjetos
     * @param   int          $orderId
     * @param   array        $order
     * @return  void
     */
    public function saveLabel($arrObjetos, int $orderId, array $order = array())
    {
        if (empty($order)) {
            $order = $this->model_orders->getOrdersData(0, $orderId);
        }
        $pathEtiquetas = $this->getPathLabel();

        $path = FCPATH . "$pathEtiquetas/P_{$orderId}_A4_{$order['in_resend_active']}.pdf";
        if (file_exists($path)) {
            unlink($path);
        }

        try {
            $arrObjetos = is_array($arrObjetos) ? $arrObjetos : array($arrObjetos);

            $receipt = array(
                "codigosObjeto" => $arrObjetos,
                //"numeroCartaoPostagem" => $this->credentials['post_card'],
                "tipoRotulo" => "P", // Tipo de Rótulo: P (padrão) ou R (reduzido)
                "formatoRotulo" => "ET" // Formato de Rótulo: ET (Etiqueta) ou EV (Envelope)
            );
            $response = $this->request('POST', "/prepostagem/v1/prepostagens/rotulo/assincrono/pdf", array('json' => $receipt));
            $contentReceipt = Utils::jsonDecode($response->getBody()->getContents());

            $receipt_id = $contentReceipt->idRecibo;

            // Aguardar 1 segundo para não dar erro.
            // Emissão de rótulo: Cannot invoke "String._dt_getBytes(java.nio.charset.Charset)" because "src" is null.
            // idRecibo não encontrado, gentileza verificar se foi informado corretamente.
            sleep(1);

            $response = $this->request('GET', "/prepostagem/v1/prepostagens/rotulo/download/assincrono/$receipt_id");
            $contentLabel = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            $message = "Erro para gerar a etiqueta. pedido=$order[id], etiqueta(s)=".implode(',', $arrObjetos).". {$exception->getMessage()}";
            get_instance()->log_data('batch', __CLASS__ . '/' . __FUNCTION__, $message);
            return;
        }
        // aqui etiquetas
        $pdf_base64 = $contentLabel->dados;

        $decoded = base64_decode($pdf_base64);
        file_put_contents($path, $decoded);
    }

    /**
     * Salva etiquetas agrupadas.
     *
     * @param   array|string $tracking
     * @param   string       $plp
     * @return  array
     */
    public function saveLabelGroup($tracking, string $plp): array
    {
        $pathEtiquetas = $this->getPathLabel();
        try {
            $tracking = is_array($tracking) ? $tracking : explode('|', $tracking);

            $receipt = array(
                "codigosObjeto" => $tracking,
                //"numeroCartaoPostagem" => $this->credentials['post_card'],
                "tipoRotulo" => "P", // Tipo de Rótulo: P (padrão) ou R (reduzido)
                "formatoRotulo" => "ET" // Formato de Rótulo: ET (Etiqueta) ou EV (Envelope)
            );
            $response = $this->request('POST', "/prepostagem/v1/prepostagens/rotulo/assincrono/pdf", array('json' => $receipt));
            $contentReceipt = Utils::jsonDecode($response->getBody()->getContents());

            $receipt_id = $contentReceipt->idRecibo;

            $response = $this->request('GET', "/prepostagem/v1/prepostagens/rotulo/download/assincrono/$receipt_id");
            $contentLabel = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            $erro_message = $exception->getMessage();
            $message = "Erro para gerar a etiqueta agrupada. Etiqueta(s)=" . implode(',', $tracking) . ". $erro_message";
            get_instance()->log_data(__CLASS__,  __FUNCTION__, $message);
            throw new InvalidArgumentException($erro_message, $exception->getCode());
        }

        $pdf_base64 = $contentLabel->dados;

        $decoded = base64_decode($pdf_base64);
        $path = FCPATH . "$pathEtiquetas/P_T_{$plp}_A4.pdf";
        if (file_exists($path)) {
            unlink($path);
        }
        file_put_contents($path, $decoded);

        return [
            'link_etiquetas_a4' => base_url($pathEtiquetas . "/P_T_{$plp}_A4.pdf"),
//            'link_etiquetas_termica' => base_url($pathEtiquetas . "P_T_{$plp}_Termica.pdf")
        ];
    }

    /**
     * Consultar as ocorrências do rastreio para Correios
     *
     * @param array $tracking_codes Códigos de rastreio.
     * @throws  Exception
     */
    public function tracking(array $tracking_codes): void
    {
        $max_codes_per_request = 50;

        // Divide os códigos em lotes de 50 e processa cada lote separadamente
        $tracking_code_batches = array_chunk($tracking_codes, $max_codes_per_request);

        while (true) {
            $batch = array_shift($tracking_code_batches); // Pega o próximo lote

            if (empty($batch)) {
                break; // Todos os lotes foram processados
            }

            $options = array(
                'query' => array(
                    'resultado' => 'T',
                    'codigosObjetos' => implode(',', $batch)
                )
            );

            $response = $this->request('GET', "/srorastro/v1/objetos", $options);
            $contentTracking = Utils::jsonDecode($response->getBody()->getContents());
            $objects = $contentTracking->objetos ?? array();

            foreach ($objects as $item) {
                if (property_exists($item, 'mensagem')) {
                    echo "ERRO Tracking ($item->codObjeto) - " . $item->mensagem . "\n";
                    continue;
                }

                if (!property_exists($item, 'eventos')) {
                    echo "ERRO Tracking ($item->codObjeto) - " . json_encode($item) . "\n";
                    continue;
                }

                $events = array_reverse($item->eventos);

                $trackingCode = $item->codObjeto;

                $freights = $this->model_freights->getOrderByCodeTracking($trackingCode);

                foreach ($freights as $freight) {
                    foreach ($events as $event) {
                        // valida se não existe dados.
                        if (!property_exists($event, 'dtHrCriado') || !property_exists($event, 'descricao')) {
                            echo "Foi encontrado erro de informação no recebimento de informação $trackingCode frete $freight[freight_id].  Retorno:" . json_encode($event, true) . "\n";
                            continue;
                        }

                        $nameOccurrence = $event->descricao;
                        $dateOccurrence = dateFormat($event->dtHrCriado, DATETIME_INTERNATIONAL);

                        if (
                            $nameOccurrence == "Objeto em transferência - por favor aguarde" &&
                            property_exists($event, 'unidadeDestino') &&
                            property_exists($event->unidadeDestino, 'endereco')
                        ) {
                            $nameOccurrence = "Em trânsito para {$event->unidadeDestino->tipo} - {$event->unidadeDestino->endereco->cidade}/{$event->unidadeDestino->endereco->uf}";
                        } elseif (
                            $event->descricao == "Objeto em transferência - por favor aguarde" &&
                            property_exists($event, 'unidade') &&
                            property_exists($event->unidade, 'endereco')
                        ) {
                            $nameOccurrence = "Em trânsito de {$event->unidade->tipo} - {$event->unidade->endereco->cidade}/{$event->unidade->endereco->uf}";
                        }

                        $dataOccurrence = array(
                            'name'              => $nameOccurrence,
                            'description'       => $event->detalhe ?? $nameOccurrence,
                            'code'              => $event->codigo,
                            'code_name'         => $nameOccurrence,
                            'type'              => $event->tipo ?? '0',
                            'date'              => $dateOccurrence,
                            'statusOrder'       => $freight['paid_status'],
                            'freightId'         => $freight['freight_id'],
                            'orderId'           => $freight['id'],
                            'trackingCode'      => $trackingCode,
                            'address_place'     => $event->unidade->tipo                 ?? ($event->unidadeDestino->tipo ?? null),
                            'address_name'      => $event->unidade->endereco->logradouro ?? ($event->unidadeDestino->endereco->logradouro ?? null),
                            'address_number'    => $event->unidade->endereco->numero     ?? ($event->unidadeDestino->endereco->numero     ?? null),
                            'address_zipcode'   => $event->unidade->endereco->cep        ?? ($event->unidadeDestino->endereco->cep        ?? null),
                            'address_neigh'     => $event->unidade->endereco->bairro     ?? ($event->unidadeDestino->endereco->bairro     ?? null),
                            'address_city'      => $event->unidade->endereco->cidade     ?? ($event->unidadeDestino->endereco->localidade ?? null),
                            'address_state'     => $event->unidade->endereco->uf         ?? ($event->unidadeDestino->endereco->uf         ?? null)
                        );

                        $this->setNewRegisterOccurrence($dataOccurrence);
                    }
                }
            }
        }
    }

    public function generatePlp(): string
    {
        return uniqid();
        //throw new InvalidArgumentException("Feature não implementada.");
    }

    /**
     * Adiciona o telefone nos dados do remetente/destinatário.
     *
     * @param   array       $correios   Dados da PPN.
     * @param   array|null  $phone      Dados do telefone para validação e adição.
     * @param   string      $type       Tipo de telefone (remetente/destinatario).
     * @return  array
     */
    private function addPhoneToHire(array $correios, ?array $phone, string $type): array
    {
        if (!empty($phone)) {
            if (strlen($phone['phone']) === 8 && empty($correios[$type]['telefone'])) {
                $correios[$type]['dddTelefone']   = $phone['ddd'];
                $correios[$type]['telefone']      = $phone['phone'];
            } else if (strlen($phone['phone']) === 9 && empty($correios[$type]['celular'])) {
                $correios[$type]['dddCelular']    = $phone['ddd'];
                $correios[$type]['celular']       = $phone['phone'];
            }
        }

        return $correios;
    }

    /**
     * @throws Exception
     */
    public function validateCredentials(array $credentials)
    {
        $endpoint = 'https://api.correios.com.br';
        $this->setEndpoint($endpoint);

        try {
            $request = $this->request('POST', '/token/v1/autentica/cartaopostagem', [
                'auth' => array(
                    $credentials['user'],
                    $credentials['password']
                ), 'json' => array(
                    'numero' => $credentials['post_card']
                )
            ]);


            $response = json_decode($request->getBody()->getContents());

            if ($credentials['contract'] != $response->cartaoPostagem->contrato) {
                throw new Exception("Contrato informado, não corresponde ao contrato no Correios", 400);
            }
        } catch (InvalidArgumentException | GuzzleException | BadResponseException $exception) {
            $message = $exception->getMessage();
            $code = $exception->getCode();
            if ($code == 400) {
                $message_decode = json_decode($message);

                if (!empty($message_decode->msgs) && is_array($message_decode->msgs)) {
                    throw new Exception(json_encode($message_decode->msgs, JSON_UNESCAPED_UNICODE), $code);
                }
            }
            throw new Exception($message, $code);
        }
    }
}