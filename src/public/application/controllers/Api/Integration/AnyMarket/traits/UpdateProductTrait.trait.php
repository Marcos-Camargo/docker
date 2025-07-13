<?php

if (!defined('UpdateProductTrait')) {
    define('UpdateProductTrait', '');
    require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/CheckIfActiveOnProduct.trait.php";
    require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/RequestRestAnymarket.trait.php";
    require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/ParserToArrayAnymaretImagens.trait.php";
    require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/SaveImagem.trait.php";
    require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/UpdateQtdProductByVariation.trait.php";
    require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/ValideMaiorPrazoOperacional.trait.php";
    require_once APPPATH . "libraries/Traits/CheckImageProduct.trait.php";
    trait UpdateProductTrait
    {
        protected $updateProductPriceStock = true;
        protected $updateProductCrossdocking = true;

        use CheckImageProduct;
        private function printInTerminal($string)
        {
            file_put_contents('php://stdout', print_r($string, true));
        }
        public function myEcho($var)
        {
            $this->printInTerminal($var . "\n");
        }
        private $PASTA_DE_IMAGEM = 'assets/images/product_image';
        private $updateAfterIntegrationFields = [
            'qty',
            'price',
            'prazo_operacional_extra',
            'status',
        ];
        use UpdatePrazoOperacional;
        use ValideMaiorPrazoOperacional;
        use RequestRestAnymarket;
        use ParserToArrayAnymaretImagens;
        use SaveImagem;
        use UpdateQtdProductByVariation;
        use CheckIfActiveOnProduct;
        private function updateProductTrait($request_body, $integration, $tmpProductData = [])
        {
            $credentials = json_decode($integration["credentials"], true);
            $this->updateProductPriceStock = $credentials['updateProductPriceStock'] ?? true;
            $this->updateProductCrossdocking = $credentials['updateProductCrossdocking'] ?? true;

            try {
                $idSkuMarketplace = $request_body["idSkuMarketplace"] ?? '0';
                $url = $this->url_anymerket . "skumarketplace/" . $idSkuMarketplace;
                $result = $this->sendREST($url);
                print_r($result['content']);
                if ($result['httpcode'] != 200) {
                    if ($result['httpcode'] == 404) {
                        return [
                            'error' => true,
                            'action' => 'remove'
                        ];
                    }
                    $this->errorConfirmation(
                        $request_body,
                        "Erro ao obter produto na AnyMarket."
                    );
                    echo ("Erro ao obter produto na AnyMarket.\n");
                    print_r($result['content']);
                    $this->log_integration(
                        "Erro ao obter produto na AnyMarket",
                        "{$result['content']}",
                        "E"
                    );
                    return false;
                }
                $this->setUniqueId($idSkuMarketplace);
                $body = json_decode($result['content'], true);
                $log_data = [
                    'endpoint' => 'UpdateProductTrait(getProduct)',
                    'body_received' => json_encode($body),
                    'store_id' => $integration['store_id'],
                ];
                $this->myEcho("Data obtida:\n" . json_encode($body) . "\n\n");
                $this->model_anymarket_log->create($log_data);

                $type = 'UpdateProducts';
                list($product, $variant) = $this->validator->validateProduct($body, $integration, $type, $body['fields']);
                $attributes = $product['attributes'] ?? [];
                unset($product['attributes']);
                if (isset($body['stock']['availableAmount'])) {
                    $product["qty"] = $body['stock']['availableAmount'];
                    $variant[0]['qty'] = $body['stock']['availableAmount'];
                }
                if (isset($body["discountPrice"])) {
                    $product['price'] = $body["discountPrice"];
                    $variant[0]['price'] = $body["discountPrice"];
                }
                $this->myEcho("Produto: " . $product['name'] . "");
                $product_saved = $this->model_products->getByProductIdErp($request_body["idProduct"]);
                if(!$product_saved) {
                    $prodSku = $product['sku'];
                    $productSkuStore = $this->model_products->getProductBySkuAndStore(
                        $prodSku,
                        $integration['store_id']
                    );
                    $product_saved = $productSkuStore['id'] > 0 && empty($productSkuStore['product_id_erp']) ? $productSkuStore : $product_saved;
                    if (!$product_saved) {
                        $prodSku = $body['sku']['partnerId'] ?? $product['sku'];
                        $productSkuStore = $this->model_products->getProductBySkuAndStore(
                            $prodSku,
                            $integration['store_id']
                        );
                        $product_saved = $productSkuStore['id'] > 0 && empty($productSkuStore['product_id_erp']) ? $productSkuStore : $product_saved;
                        if (!$product_saved) {
                            $prodSku = $variant[0]['sku'] ?? $product['sku'];
                            $productSkuStore = $this->model_products->getProductByVarSkuAndStore(
                                $prodSku,
                                $integration['store_id']
                            );
                            $product_saved = $productSkuStore['id'] > 0 && empty($productSkuStore['product_id_erp']) ? $productSkuStore : $product_saved;
                        }
                    }
                }
                if (!$product_saved) {
                    $this->myEcho("Novo produto.");
                    $product['status'] = 1;
                    if ($body['sku']['product']['hasVariations']) {
                        $update['sku'] = $product['sku'] . "-PRD";
                    } else {
                        $update['sku'] = $product['sku'];
                    }
                    list($imagens_product, $imagens_variant) = $this->parserImagensForArray($body['sku']);
                    $this->createFolderIfNotExist($this->PASTA_DE_IMAGEM . "", $product);
                    $this->createFolderIfNotExist($this->PASTA_DE_IMAGEM . '/' . $product['image'], $variant[0]);
                    if ($body['sku']['product']['hasVariations']) {
                        $product['sku'] = $product['sku'] . "-PRD";
                        try {
                            $this->validator->validateUpgradeableProduct([
                                'product_id' => 0,
                                'sku' => $product['sku'],
                                'store_id' => $integration['store_id'],
                                'product_id_erp' => $product['product_id_erp']
                            ]);
                        } catch (Exception $e) {
                            $this->log_integration("Erro na atualização de produto {$integration['sku']}", $e->getMessage(), "E");
                            throw $e;
                        }
                    } else {
                        $product['sku'] = $product['sku'];
                    }
                    if (empty($imagens_product)) {
                        $product['situacao'] = 1;
                    } else {
                        $product['principal_image'] = $this->saveImagem($imagens_product, '/' . $product['image'] . '/');
                        if (!empty($product['principal_image'])) {
                            $product['principal_image'] = base_url($product['principal_image']);
                        }
                        $product['situacao'] = 2;
                    }
                    $this->saveImagem($imagens_variant, '/' . $product['image'] . '/' . $variant[0]['image'] . "/");
                    if ($body['sku']['product']['hasVariations']) {
                        $variant[0]['ean'] = $product['ean'];
                        unset($product['ean']);
                    } else {
                        // para produto simples, pegar título da publicação
                        $contentReceived = $tmpProductData['adReceivedData'] ?? [];
                        $dataValidation = $tmpProductData['preValidationProdData'] ?? [];
                        $product['name'] = $contentReceived['title'] ?? $dataValidation['name'] ?? $product['name'];
                    }
                    $created = $this->model_products->create($product);
                    $this->setUniqueId($created);
                    $product_saved = $this->model_products->getByProductIdErp($request_body["idProduct"]);
                    if ($body['sku']['product']['hasVariations']) {
                        $variant[0]['variant'] = 0;
                        $variant[0]['prd_id'] = $created;
                        $created_var = $this->model_products->createvar($variant[0]);
                        $this->updateQtdProductByVariation($product_saved['id']);
                        $this->getAllAndSaveMaxPrazoOperacional($product['product_id_erp']);
                        $variant_save = $this->model_products->getVariantsByProd_idAndVariant_id_erp($created, $request_body['idSku']);
                        $this->sendConfirmation(
                            $request_body,
                            $variant_save['id'],
                            $product_saved,
                            $product_saved['sku'],
                            $variant[0]['qty'] > 0 ? ProductAnyMarketConst::STATUS_ACTIVE : ProductAnyMarketConst::STATUS_PAUSED
                        );
                    } else {
                        $this->sendConfirmation(
                            $request_body,
                            $product_saved['id'],
                            $product_saved,
                            $product_saved['sku'],
                            $product["qty"] > 0 ? ProductAnyMarketConst::STATUS_ACTIVE : ProductAnyMarketConst::STATUS_PAUSED
                        );
                    }
                    $this->log_integration("Produto {$product_saved['sku']} cadastrado", "<h4>Produto cadastrado - ID:{$product_saved['id']}</h4>", "S");
                } else {
                    $this->setUniqueId($product_saved['id']);
                    $this->myEcho("Produto existente.");
                    $update = [];
                    $update['status'] = $body['status'] == 'ACTIVE' ? 1 : 2;
                    list($imagens_product, $imagens_variant) = $this->parserImagensForArray($body['sku']);
                    if ($body['sku']['product']['hasVariations']) {
                        $product['sku'] = $product['sku'] . "-PRD";
                        try {
                            $this->validator->validateUpgradeableProduct([
                                'product_id' => $product_saved['id'],
                                'sku' => $product['sku'],
                                'store_id' => $integration['store_id'],
                                'product_id_erp' => $product['product_id_erp']
                            ]);
                        } catch (Exception $e) {
                            $this->log_integration("Erro na atualização de produto {$product_saved['sku']}", $e->getMessage(), "E");
                            throw $e;
                        }
                    } else {
                        // para produto simples, pegar título da publicação
                        $contentReceived = $tmpProductData['adReceivedData'] ?? [];
                        $dataValidation = $tmpProductData['preValidationProdData'] ?? [];
                        $product['name'] = $contentReceived['title'] ?? $dataValidation['name'] ?? $product['name'];
                    }
                    $this->model_products->update($update, $product_saved['id']);
                    if ($body['sku']['product']['hasVariations']) {
                        $this->myEcho("Produto já com variação.");
                        $variant_save = $this->model_products->getVariantsByProd_idAndVariant_id_erp($product_saved['id'], $variant[0]['variant_id_erp']);
                        if (!$variant_save) {
                            $productVarSku = $this->model_products->getVariantsByProd_idAndSku(
                                $product_saved['id'],
                                $variant[0]['sku']
                            );
                            $variant_save = $productVarSku['id'] > 0 && empty($productVarSku['variant_id_erp']) ? $productVarSku : $variant_save;
                            if (!$variant_save) {
                                $productVarSku = $this->model_products->getVariantsByProd_idAndSku(
                                    $product_saved['id'],
                                    $body['sku']['partnerId'] ?? $variant[0]['sku']
                                );
                                $variant_save = $productVarSku['id'] > 0 && empty($productVarSku['variant_id_erp']) ? $productVarSku : $variant_save;
                            }
                        }
                        if ($variant_save) {
                            $this->myEcho("Atualizando variação");
                            $update_var = [];
                            $update_var['price'] = $variant[0]['price'];
                            $update_var['qty'] = $variant[0]['qty'];
                            $update_var['variant_id_erp'] = $variant[0]['variant_id_erp'];
                            $update_var['status'] = 1;
                            $integralized = !empty($this->model_integrations->getPrdIntegration($product_saved['id']));
                            if ($this->updateProductPriceStock || $integralized) {

                            } else {
                                $update_var['ean'] = $product['ean'];
                                $update_var['name'] = $variant[0]['name'];
                                $this->saveImagem($imagens_variant, '/' . $product_saved['image'] . '/' . $variant_save['image'] . "/");
                            }
                            $updated = $this->model_products->updateVar($update_var, $variant_save['prd_id'], $variant_save['variant']);
                            $this->sendConfirmation(
                                $request_body,
                                $variant_save['id'],
                                $product_saved,
                                $variant_save['sku'],
                                $update_var["qty"] > 0 ? ProductAnyMarketConst::STATUS_ACTIVE : ProductAnyMarketConst::STATUS_PAUSED
                            );
                            $this->myEcho("Updated variação : {$updated}");
                        } else {
                            $this->myEcho("Criação somente de variação.");
                            $this->createFolderIfNotExist($this->PASTA_DE_IMAGEM . '/' . $product_saved['image'], $variant[0]);
                            $this->saveImagem($imagens_variant, '/' . $product_saved['image'] . '/' . $variant[0]['image'] . "/");
                            $variants = $this->model_products->getVariantsByProd_id($product_saved['id']);
                            $variant[0]['variant'] = count($variants);
                            $variant[0]['prd_id'] = $product_saved['id'];
                            $variant[0]['ean'] = $product['ean'];
                            $variant[0]['status'] = 1;
                            $created_var = $this->model_products->createvar($variant[0]);
                            $variant_save = $this->model_products->getVariantsByProd_idAndVariant_id_erp($product_saved['id'], $variant[0]['variant_id_erp']);
                            $this->sendConfirmation(
                                $request_body,
                                $variant_save['id'],
                                $product_saved,
                                $variant[0]['sku'],
                                $variant[0]["qty"] > 0 ? ProductAnyMarketConst::STATUS_ACTIVE : ProductAnyMarketConst::STATUS_PAUSED
                            );
                        }
                        $product['ean'] = '';
                        $prodIntegrations = $this->model_integrations->getPrdIntegration($product_saved['id']);
                        $integrated = !empty($prodIntegrations);
                        if ($integrated) {
                            $integrations = $this->model_integrations->getIntegrationsProductAll($product_saved['id']);
                            $intErrors = array_filter($integrations, function ($item) {
                                return $item['errors'];
                            });
                            if (count($prodIntegrations) == count($intErrors)) {
                                $integrated = false;
                            }
                        }
                        if ($this->updateProductPriceStock || $integrated) {
                            $this->myEcho("Atualização de produto integrado, só preço, estoque e crossdoking.");
                            $this->removeFieldDontUpdateAfterIntegration($product);
                            $this->model_products->update($product, $product_saved['id']);
                        } else {
                            $this->myEcho("Atualização geral de produto .");
                            $this->updateProductExistent($product_saved, $body, $product);
                            $this->model_products->update($product, $product_saved['id']);
                        }
                        $this->updateQtdProductByVariation($product_saved['id']);
                        $this->getAllAndSaveMaxPrazoOperacional($product_saved['product_id_erp']);
                        $this->checkIfActiveOnProduct($product_saved);
                    } else {
                        $this->myEcho("Atualização de produto existente.");
                        $this->updateProductExistent($product_saved, $body, $product);
                        $this->sendConfirmation(
                            $request_body,
                            $product_saved['id'],
                            $product_saved,
                            $product_saved['sku'],
                            $product["qty"] > 0 ? ProductAnyMarketConst::STATUS_ACTIVE : ProductAnyMarketConst::STATUS_PAUSED
                        );
                    }
                }
                $this->checkImageProduct($product_saved);
                try {
                    $this->setAttributeProduct($product_saved['id'], $attributes);
                } catch (Throwable $e) {

                }
                return true;
            } catch (Exception $e) {
                $this->log_integration("Produto não criado/atualizado", "<h4>{$e->getMessage()}</h4>", "E");
                $this->errorConfirmation(
                    $request_body,
                    $e->getMessage()
                );
                return false;
            } catch (Throwable $e) {
                $this->log_integration("Produto não criado/atualizado", "<h4>{$e->getMessage()}</h4>", "E");
                $this->errorConfirmation(
                    $request_body,
                    $e->getMessage()
                );
                return false;
            }
        }

        protected function setAttributeProduct($productId, $attributes)
        {
            // transforma as chaves em minúsculo para comparação
            $attributes = array_change_key_case($attributes, CASE_LOWER);

            $attributesMarketplace = $this->model_atributos_categorias_marketplaces->getAttributeMarketplaceByProductAndAttribute($productId, array_keys($attributes));
            foreach ($attributesMarketplace as $attributeMarketplace) {

                $valueSelected = null;

                // é uma listagem de valores, é preciso percorrer e encontrar se o valor está na lista
                if ($attributeMarketplace['tipo'] === 'list') {
                    // Descodificar o json para ver se existe valores definidos
                    $decodeValuesRequired = json_decode($attributeMarketplace['valor_obrigatorio']);
                    if (count($decodeValuesRequired)) {
                        foreach ($decodeValuesRequired as $valueRequired) {
                            // VTEX
                            if (
                                property_exists($valueRequired, 'Value') &&
                                property_exists($valueRequired, 'FieldValueId') &&
                                property_exists($valueRequired, 'IsActive') &&
                                property_exists($valueRequired, 'Position')
                            ) {
                                if (strtolower($valueRequired->Value) == strtolower($attributes[strtolower($attributeMarketplace['nome'])])) {
                                    $valueSelected = $valueRequired->FieldValueId;
                                    break;
                                }
                            } // Mercado Livre
                            elseif (
                                property_exists($valueRequired, 'id') &&
                                property_exists($valueRequired, 'name')
                            ) {
                                if (strtolower($valueRequired->name) == strtolower($attributes[strtolower($attributeMarketplace['nome'])])) {
                                    $valueSelected = $valueRequired->id;
                                    break;
                                }
                            } // Via Varejo
                            elseif (
                                property_exists($valueRequired, 'udaValueId') &&
                                property_exists($valueRequired, 'udaValue')
                            ) {
                                if (strtolower($valueRequired->udaValue) == strtolower($attributes[strtolower($attributeMarketplace['nome'])])) {
                                    $valueSelected = $valueRequired->udaValueId;
                                    break;
                                }
                            }
                        }
                    }
                } else { // tipo = string
                    $valueSelected = $attributes[strtolower($attributeMarketplace['nome'])] ?? null;
                }

                if ($valueSelected !== null) {

                    // atributo ainda não existe na tabela produtos_atributos_marketplaces, precisa criar
                    if ($attributeMarketplace['valor_atributo'] === null) {
                        $this->model_atributos_categorias_marketplaces->createProductAttributeMarketplace(array(
                            'id_product' => $productId,
                            'id_atributo' => $attributeMarketplace['id_atributo'],
                            'valor' => $valueSelected,
                            'int_to' => $attributeMarketplace['int_to']
                        ));
                        continue;
                    }

                    // update attribute
                    $this->model_atributos_categorias_marketplaces->updateProductAttributeMarketplace($valueSelected, array(
                        'id_product' => $productId,
                        'id_atributo' => $attributeMarketplace['id_atributo'],
                        'int_to' => $attributeMarketplace['int_to']
                    ));

                }
            }
        }

        private function updateProductExistent($product_saved, $body, $product)
        {
            $this->myEcho("Atualização de produto existente.");
            $prodIntegrations = $this->model_integrations->getPrdIntegration($product_saved['id']);
            $integrated = !empty($prodIntegrations);
            if ($integrated) {
                $integrations = $this->model_integrations->getIntegrationsProductAll($product_saved['id']);
                $intErrors = array_filter($integrations, function ($item) {
                    return $item['errors'];
                });
                if (count($prodIntegrations) == count($intErrors)) {
                    $integrated = false;
                }
            }
            if ($this->updateProductPriceStock || $integrated) {
                $this->myEcho("Atualização de produto integrado, só preço, estoque e crossdoking.");
                $this->log_integration("Atualização de produto integrado, só preço, estoque e crossdoking. ", "<h4>Atualização de produto integrado, só preço, estoque e crossdoking. - ID no seller center:{$product_saved['id']}</h4>", "W");
                $this->removeFieldDontUpdateAfterIntegration($product);
                $this->model_products->update($product, $product_saved['id']);
            } else {
                $this->myEcho("Atualização geral de produto 2.");
                $this->log_integration("Atualização de produto existente.", "<h4>Atualização completa do produto - ID no seller center:{$product_saved['id']}</h4>", "W");
                list($imagens_product, $imagens_variant) = $this->parserImagensForArray($body['sku']);
                if (empty($imagens_product)) {
                    $product['situacao'] = 1;
                    if (preg_match('/[Cor]/', $product["has_variants"]) && !empty($imagens_variant)) {
                        $product['situacao'] = 2;
                    }
                    $product['principal_image'] = null;
                } else {
                    $product['principal_image'] = $this->saveImagem($imagens_product, '/' . $product_saved['image'] . '/');
                    if (!empty($product['principal_image'])) {
                        // $product['principal_image'] = base_url($product['principal_image']);
                        $product['principal_image'] = base_url($product['principal_image']);
                    }
                    $product['situacao'] = 2;
                    // if(preg_match('/[Cor]/',$product["has_variants"]) && empty($imagens_variant)){
                    //     $product['situacao'] = 1;
                    // }
                }
                unset($product["sku"]);
                unset($product["product_id_erp"]);
                unset($product["store_id"]);
                unset($product["company_id"]);
                $this->model_products->update($product, $product_saved['id']);
            }
        }
        private function removeFieldDontUpdateAfterIntegration(&$product)
        {
            foreach ($product as $key => $value) {
                if (!in_array($key, $this->updateAfterIntegrationFields)) {
                    unset($product[$key]);
                }
            }
            if (!$this->updateProductCrossdocking) {
                unset($product['prazo_operacional_extra']);
            }
        }
        private function sendConfirmation($body, $variant_id, $product, $skuInMarketplace, $status = ProductAnyMarketConst::STATUS_ACTIVE)
        {
            $url_confirm = $this->url_anymerket . "skumarketplace/" . $body['idSkuMarketplace'] . "";
            $data_to_send = [
                'idInMarketplace' => $variant_id,
                'idInSite' => $product['id'],
                'skuInMarketplace' => $skuInMarketplace,
                'marketplaceStatus' => "ATIVO",
                'idSku' => $body['idSku'],
                'transmissionStatus' => "OK",
                'errorMsg' => '',
                'status' => ProductAnyMarketConst::STATUS_ACTIVE,
            ];
            $this->myEcho("Enviando confirmação: " . json_encode($data_to_send) . "\n");
            $res = $this->sendREST($url_confirm, json_encode($data_to_send), 'PUT');
            $this->myEcho("Retorno envio de confirmação: " . json_encode($res) . "\n");
        }
        private function errorConfirmation($body, $message, $status = ProductAnyMarketConst::STATUS_ACTIVE)
        {
            $url_confirm = $this->url_anymerket . "skumarketplace/" . $body['idSkuMarketplace'] . "";
            $data_to_send = [
                'idInMarketplace' => null,
                'idInSite' => null,
                'skuInMarketplace' => null,
                'marketplaceStatus' => "NÃO PUBLICADO",
                'idSku' => $body['idSku'],
                'transmissionStatus' => "ERROR",
                'errorMsg' => $message,
                'status' => $status,
            ];
            $res = $this->sendREST($url_confirm, json_encode($data_to_send), 'PUT');
        }
    }
}

final class ProductAnyMarketConst
{

    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_PAUSED = 'PAUSED';
    const STATUS_CLOSED = 'CLOSED';

}