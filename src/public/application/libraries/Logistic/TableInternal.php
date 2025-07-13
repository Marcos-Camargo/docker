<?php

use GuzzleHttp\Utils;

class TableInternal extends Logistic
{
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
        $this->authRequest = array();
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
        $settings_enable_table_regions  = $this->dbReadonly->get_where('settings', array('name' => 'enable_table_shipping_regions'));
        $row_enable_table_regions       = $settings_enable_table_regions->row_array();
        $enable_table_regions           = false;

        if ($row_enable_table_regions && $row_enable_table_regions['status'] == 1) {
            $enable_table_regions = true;
        }

        $time = microtime(true) * 1000;
        $shippingCompanies = null;
        $key_redis_shipping_companies = "$this->sellerCenter:shipping_companies:$this->store";
        if ($this->redis && $this->redis->is_connected) {
            $data_redis = $this->redis->get($key_redis_shipping_companies);
            if ($data_redis !== null) {
                $shippingCompanies = json_decode($data_redis);
            }
        }
        if ($shippingCompanies === null) {
            $shippingCompanies = $this->dbReadonly
                ->select('p.*, t.id_type')
                ->join('shipping_company AS p', 'p.id = pts.provider_id')
                ->join('type_table_shipping AS t', 't.id_provider = p.id', 'left')
                ->where(['pts.store_id' => $this->store, 'p.active' => true])
                ->get('providers_to_seller pts')
                ->result_object();

            if ($this->redis && $this->redis->is_connected) {
                $this->redis->setex($key_redis_shipping_companies, 3600, json_encode($shippingCompanies, JSON_UNESCAPED_UNICODE));
            }
        }

        $zipcode = str_pad($dataQuote['zipcodeRecipient'], 8, "0", STR_PAD_LEFT);
        $services = array();
        $data_shipping_company_table = [];
        $data_product = [];

        $table_shipping_to_quote = null;

        foreach ($shippingCompanies as $shippingCompany) {
            // Não encontrou o tipo de tabela da transportadora.
            if (!property_exists($shippingCompany, 'id_type')) {
                continue;
            }

            // Se o valor da variável "$freight_calculation_standard" é "1", o frete está configurado para usar o peso como base 
            // do cálculo, em vez de usar o modo padrão de cálculo, que é pelo volume (e marcado como "0" na tabela "shipping_company").
            // Estas informações são relevantes somente quando o frete usa as tabelas internas.
            $freight_calculation_standard = $shippingCompany->freight_calculation_standard;
            $max_weight = null;

            $dataFreight = array();
            $countLabelSGP = 0;
            $peso_validate = 0;
            $dataFinalFreight = array();

            if ($shippingCompany->id_type == 1) {
                foreach ($dataQuote['items'] as $product_check) {
                    $qtyLabel   = 0;
                    $rate       = $product_check['valor'] / $product_check['quantidade'];
                    $lengthItem = $product_check['comprimento'] * 100;
                    $widthItem  = $product_check['largura'] * 100;
                    $heightItem = $product_check['altura'] * 100;
                    $sku_mkt    = $product_check['sku'];

                    $product_id = $dataQuote['dataInternal'][$product_check['sku']]['prd_id'];
                    $qtyPackageItem = null;

                    $key_redis_products_package_product = "$this->sellerCenter:products:$product_id:products_package";
                    if ($this->redis && $this->redis->is_connected) {
                        $qtyPackageItem = $this->redis->get($key_redis_products_package_product);
                    }
                    if ($qtyPackageItem === null) {
                        if (array_key_exists($product_id, $data_product)) {
                            $queryPackage = $data_product[$product_id];
                        } else {
                            $queryPackage = $this->dbReadonly->select('products_package')->where('id', $product_id)->get('products')->row_object();
                            $data_product[$product_id] = $queryPackage;
                        }

                        $qtyPackageItem = $queryPackage->products_package ?? 1;

                        if ($this->redis && $this->redis->is_connected) {
                            $this->redis->setex($key_redis_products_package_product, 3600, $qtyPackageItem);
                        }
                    }

                    // Quantidade de itens de um produto do pedido.
                    $qtyPerPackage = ceil($product_check['quantidade'] / $qtyPackageItem);

                    if (is_null($table_shipping_to_quote)) {
                        $table_shipping_to_quote = $this->getTableShippingQuote($zipcode, $enable_table_regions);
                    }


                    // Tabela interna com informações do arquivo CSV carregado.
                    for ($qtyItem = 1; $qtyItem <= $qtyPerPackage; $qtyItem++) {
                        $qtyFinalItem = ($product_check['quantidade'] - ($qtyItem * $qtyPackageItem));

                        if ($qtyFinalItem >= 0) {
                            $qtyFinalItem = $qtyPackageItem;
                        } else {
                            $qtyFinalItem = $product_check['quantidade'] - (($qtyItem - 1) * $qtyPackageItem);
                        }

                        $productQuote = (float)str_replace(",", ".", $product_check['peso']) * $qtyFinalItem;

                        //Verifica se a transportadora usa frete cubado, se sim, calcula o peso cubado.
                        if ($shippingCompany->slc_tipo_cubage){
                            $productDimensions = $product_check["largura"] * $product_check["altura"] * $product_check["comprimento"] * $qtyFinalItem;
                            //$cubedWeight = $productDimensions * $shippingCompany->cubage_factor;
                            $cubedWeight = $productDimensions * floatval($shippingCompany->cubage_factor);

                            //Se o peso cubado for maior que o peso bruto, utiliza o peso cubado.
                            if ($cubedWeight > $productQuote) {
                                $productQuote = $cubedWeight;
                            }
                        }

                        $peso_validate += $productQuote;

                        // Frete calculado por peso.
                        if ($freight_calculation_standard == 1) {
                            /*
                            Distribuição do peso por volume: caso o peso total de um produto seja maior do que o peso máximo
                            permitido, é necessário distribuir o produto em múltiplos volumes.
                            */
                            if ($max_weight === null) {
                                $max_weight = $this->dbReadonly
                                    ->select('weight_maximum')
                                    ->where(
                                        array(
                                            'status' => true,
                                            'idproviders_to_seller' => $shippingCompany->id,
                                            'cep_start <=' => $dataQuote['zipcodeRecipient'],
                                            'cep_end >=' => $dataQuote['zipcodeRecipient']
                                        )
                                    )
                                    ->order_by('shipping_price', 'DESC') // caso dê algum problema, retorna o mais barato
                                    ->limit(1)
                                    ->get($table_shipping_to_quote)
                                    ->row_object();

                                if (!isset($max_weight->weight_maximum)) {
                                    break;
                                }
                                $max_weight = (float)$max_weight->weight_maximum;
                            }

                            if ($peso_validate > $max_weight) {
                                $peso_validate = $productQuote;
                                $countLabelSGP++;
                            }
                        } else {
                            $countLabelSGP++;
                        }

                        $rate_item = $rate * $qtyFinalItem;

                        if (isset($dataFreight[$countLabelSGP][$sku_mkt])) {
                            $rate_item += $dataFreight[$countLabelSGP][$sku_mkt]['rate'];
                            $productQuote += $dataFreight[$countLabelSGP][$sku_mkt]['weight'];

                            $qtyLabel += $qtyFinalItem;
                        } else {
                            $qtyLabel = $qtyFinalItem;
                        }

                        $dataFreight[$countLabelSGP][$sku_mkt] = array(
                            'product_id'    => $product_id,
                            'height'        => $heightItem,
                            'width'         => $widthItem,
                            'length'        => $lengthItem,
                            'weight'        => $productQuote,
                            'quantity'      => $qtyLabel,
                            "rate"          => (float)$rate_item
                        );
                    }
                }

                foreach ($dataFreight as $skus) {
                    $max_height = getMaxValueInArray($skus, 'height');
                    $max_width  = getMaxValueInArray($skus, 'width');
                    $max_length = getMaxValueInArray($skus, 'length');
                    $max_weight = array_reduce($skus, function($carry, $item) {
                        return $carry + $item['weight'];
                    });

                    $priceDeadline = null;
                    $key_redis_table_shipping = "$this->sellerCenter:table_shipping:$table_shipping_to_quote:$shippingCompany->id:$max_weight:{$dataQuote['zipcodeRecipient']}";
                    if ($this->redis && $this->redis->is_connected) {
                        $data_redis = $this->redis->get($key_redis_table_shipping);
                        if ($data_redis !== null) {
                            $priceDeadline = json_decode($data_redis);
                        }
                    }
                    if ($priceDeadline === null) {
                        if (array_key_exists("$shippingCompany->id:$max_weight:$dataQuote[zipcodeRecipient]", $data_shipping_company_table)) {
                            $priceDeadline = $data_shipping_company_table["$shippingCompany->id:$max_weight:$dataQuote[zipcodeRecipient]"];
                        } else {
                            $priceDeadline = $this->dbReadonly
                                ->select('shipping_price, qtd_days')
                                ->where(
                                    array(
                                        'status' => true,
                                        'idproviders_to_seller' => $shippingCompany->id,
                                        'weight_minimum <='     => (float) $max_weight,
                                        'weight_maximum >='     => (float) $max_weight,
                                        'cep_start <='          => $dataQuote['zipcodeRecipient'],
                                        'cep_end >='            => $dataQuote['zipcodeRecipient']
                                    )
                                )
                                ->order_by('shipping_price', 'DESC') // caso dê algum problema, retorna o mais barato
                                ->get("$table_shipping_to_quote use index (index_by_idproviders_to_seller_status_cep_weight)")
                                ->row_object();

                            if ($this->redis && $this->redis->is_connected) {
                                $this->redis->setex($key_redis_table_shipping, 3600, json_encode($priceDeadline, JSON_UNESCAPED_UNICODE));
                            }

                            $data_shipping_company_table["$shippingCompany->id:$max_weight:$dataQuote[zipcodeRecipient]"] = $priceDeadline;
                        }
                    }

                    $adValorem = 0;
                    $gris = 0;
                    $toll = 0;
                    $shippingRevenue = 0;

                    if ($priceDeadline) {
                        $deadline = $priceDeadline->qtd_days + $dataQuote['crossDocking'];

                        //Calculando Ad Valorem.
                        if ($shippingCompany->ad_valorem <> (null || "0")){
                            $adValorem = $shippingCompany->ad_valorem/100;
                            $adValorem = $priceDeadline->shipping_price * $adValorem;
                        }

                        //Calculando Gris.
                        if ($shippingCompany->gris <> (null || "0")){
                            $gris = $shippingCompany->gris/100;
                            $gris = $priceDeadline->shipping_price * $gris;
                        }

                        //Calculando o pedagio, soma o valor a cada 100kg.
                        if ($shippingCompany->toll <> (null || "0")){
                            $toll = $max_weight;
                            $toll_by_kg = floor($toll);
                            $toll = $shippingCompany->toll * ($toll_by_kg + 1);
                        }

                        //Calculando a receita de frete.
                        if ($shippingCompany->shipping_revenue <> (null || "0")){
                            $shippingRevenue = $shippingCompany->shipping_revenue/100;
                            $shippingRevenue = $priceDeadline->shipping_price * $shippingRevenue;
                        }

                        $priceDeadline->shipping_price = $priceDeadline->shipping_price + $adValorem + $gris + $toll + $shippingRevenue;
                        $priceDeadline->shipping_price = round($priceDeadline->shipping_price,2);

                        //print_r([$priceDeadline->shipping_price, $deadline, $max_weight]);

                        if (!array_key_exists($shippingCompany->id, $dataFinalFreight)) {
                            $dataFinalFreight[$shippingCompany->id] = array(
                                'quote_id'     => null,
                                'method_id'    => (int)$shippingCompany->id,
                                'value'        => $priceDeadline->shipping_price,
                                'deadline'     => $deadline,
                                'method'       => $shippingCompany->name,
                                'provider'     => $shippingCompany->name,
                                'provider_cnpj'=> onlyNumbers($shippingCompany->cnpj),
                                'shipping_id'  => $shippingCompany->id
                            );
                        } else {
                            $dataFinalFreight[$shippingCompany->id]['value'] += $priceDeadline->shipping_price;
                            if ($deadline > $dataFinalFreight[$shippingCompany->id]['deadline']) {
                                $dataFinalFreight[$shippingCompany->id]['deadline'] = $deadline;
                            }
                        }

                    }
                }

                if (array_key_exists($shippingCompany->id, $dataFinalFreight)) {
                    $count_products = count($dataQuote['items']);
                    $service = $dataFinalFreight[$shippingCompany->id];

                    foreach ($dataQuote['items'] as $key => $sku) {
                        $value = $service['value'];

                        $product_id = $dataQuote['dataInternal'][$sku['sku']]['prd_id'];

                        if ($count_products > 1) {
                            $value = roundDecimal($service['value'] / $count_products);

                            if ($key !== 0 && ($key + 1) == $count_products) {
                                $value = $service['value'] - ($value * $key);
                            }
                        }

                        $services[] = array_merge($service, array(
                            'value'     => $value,
                            'skumkt'    => $sku['sku'],
                            'prd_id'    => (int)$product_id,
                        ));
                    }
                }


            } else {
                foreach ($dataQuote['items'] as $product) {
                    try {
                        $dataRecipient = $this->zipCodeQuery($zipcode);
                    } catch (InvalidArgumentException $exception) {
                        continue;
                    }
                    $state = $this->dbReadonly->where('Uf', $dataRecipient->state)->get('states')->row_object();
                    // Tabela simplificada de frete.

                    $taxSimplifiers = null;
                    $key_redis_frete_regiao_provider = "$this->sellerCenter:frete_regiao_provider:$shippingCompany->id:$state->Regiao";
                    if ($this->redis && $this->redis->is_connected) {
                        $data_redis = $this->redis->get($key_redis_frete_regiao_provider);
                        if ($data_redis !== null) {
                            $taxSimplifiers = json_decode($data_redis);
                        }
                    }
                    if ($taxSimplifiers === null) {
                        if (array_key_exists("$shippingCompany->id:$state->Regiao:$state->idEstado", $data_shipping_company_table)) {
                            $taxSimplifiers = $data_shipping_company_table["$shippingCompany->id:$state->Regiao:$state->idEstado"];
                        } else {
                            $taxSimplifiers = $this->dbReadonly->where(
                                    array(
                                        'id_provider' => $shippingCompany->id,
                                        'id_regiao' => $state->Regiao,

                                    )
                                )
                                ->group_start()
                                ->where('id_estado', $state->idEstado)
                                ->or_where('id_estado IS NULL', NULL, FALSE)
                                ->group_end()
                                ->order_by('id_estado', 'DESC')
                                ->get('frete_regiao_provider')
                                ->result_object();

                            if ($this->redis && $this->redis->is_connected) {
                                $this->redis->setex($key_redis_frete_regiao_provider, 3600, json_encode($taxSimplifiers, JSON_UNESCAPED_UNICODE));
                            }

                            $data_shipping_company_table["$shippingCompany->id:$state->Regiao:$state->idEstado"] = $taxSimplifiers;
                        }
                    }

                    foreach ($taxSimplifiers as $taxSimplifier) {
                        /**
                         * Se for capital e o prazo de entrega pra capital for 0 dias, e o id do estado for
                         * diferente de null, o estado não é atendido.
                         */
                        if (
                            $dataRecipient->capital == 1 &&
                            $taxSimplifier->capital_qtd_dias == 0 &&
                            $taxSimplifier->id_estado !== null
                        ) {
                            continue;

                            /**
                             * Se não for capital e o prazo de entrega pro interior for 0 dias, e o id do estado for
                             * diferente de null, o estado não é atendido.
                             */
                        } else if (
                            $dataRecipient->capital != 1 &&
                            $taxSimplifier->interior_qtd_dias == 0 &&
                            $taxSimplifier->id_estado !== null
                        ) {
                            continue;

                            /**
                             * Se não for estado e o prazo de entrega da região for 0 dias,
                             * a região não é atendida.
                             */
                        } else if (
                            $taxSimplifier->id_estado === null &&
                            $taxSimplifier->qtd_dias == 0
                        ) {
                            continue;
                        }

                        if ($dataRecipient->capital == 1 && $taxSimplifier->id_estado !== null) {
                            $priceQuote     = $taxSimplifier->capital_valor;
                            $deadlineQuote  = (int)$taxSimplifier->capital_qtd_dias + $dataQuote['crossDocking'];
                        } else if ($dataRecipient->capital != 1 && $taxSimplifier->id_estado !== null) {
                            $priceQuote     = $taxSimplifier->interior_valor;
                            $deadlineQuote  = (int)$taxSimplifier->interior_qtd_dias + $dataQuote['crossDocking'];
                        } else if ($taxSimplifier->id_estado === null) {
                            $priceQuote     = $taxSimplifier->valor;
                            $deadlineQuote  = (int)$taxSimplifier->qtd_dias + $dataQuote['crossDocking'];
                        } else {
                            continue;
                        }

                        $priceQuote *= ($product['quantidade'] ?? 1);

                        $quoteFlash = array(
                            'skumkt'       => $product['sku'],
                            'prd_id'       => (int)$dataQuote['dataInternal'][$product['sku']]['prd_id'],
                            'quote_id'     => null,
                            'method_id'    => (int)$shippingCompany->id,
                            'value'        => $priceQuote,
                            'deadline'     => $deadlineQuote,
                            'method'       => $shippingCompany->name,
                            'provider'     => $shippingCompany->name,
                            'provider_cnpj'=> onlyNumbers($shippingCompany->cnpj),
                            'shipping_id'  => $shippingCompany->id
                        );

                        $services[] = $quoteFlash;
                        break;
                    }
                }
            }
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }

    /**
     * Consulta a tabela de cotação do estado para o CEP.
     *
     * @param string $zipcode
     * @return array|null
     */
    private function getTableShippingQuote(string $zipcode, bool $enable_table_regions): string
    {
        if (!$enable_table_regions) {
            return 'table_shipping';
        }

        $key_redis = "$this->sellerCenter:shipping:table_shipping_regions:$zipcode";

        if ($this->redis && $this->redis->is_connected) {
            $data_redis = $this->redis->get($key_redis);
            if ($data_redis !== null) {
                return $data_redis;
            }
        }

        $table = $this->dbReadonly
            ->select('table')
            ->where([
                'zipcode_start <='  => $zipcode,
                'zipcode_end >='    => $zipcode,
                'status'            => true
            ])
            ->get('table_shipping_regions')
            ->row_array();

        if (!$table) {
            throw new InvalidArgumentException("Não encontrado a tabela de regiões para o CEP $zipcode");
        }

        if ($this->redis && $this->redis->is_connected) {
            $this->redis->setex($key_redis, 86400, $table['table']);
        }

        return $table['table'];
    }
}
