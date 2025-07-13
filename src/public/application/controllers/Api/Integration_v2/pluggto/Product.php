<?php

require APPPATH . "libraries/Integration_v2/pluggto/ToolsProduct.php";

use Integration\Integration_v2\pluggto\ToolsProduct;

class Product
{
    /**
     * Realiza a criação de produto.
     *
     * @param   object      $webhook    Dados recebido na notificação.
     * @param   int         $store      Código da loja.
     * @throws  Exception
     */
    public static function create(object $webhook, int $store)
    {
        $toolProduct  = new ToolsProduct();
        
        $toolProduct->setUniqueId($webhook->id);
        
        try {
            $toolProduct->startRun($store);
        } catch (InvalidArgumentException $exception) {
            if ($toolProduct->store) {
                $toolProduct->log_integration(
                    "Erro para receber notificação",
                    "<h4>Não foi possível receber a notificação de produto</h4> <p>{$exception->getMessage()}</p>",
                    "E"
                );
            }

            throw new Exception($exception->getMessage());
        }

        $product = $toolProduct->getDataProductIntegrationById($webhook->id);

        if (!$product) {
            throw new Exception("Produto ($webhook->id) não localizado.");
        }

        $skuProductPai  = trim($product->sku);
        $idProductPai   = trim($product->id);

        $verifyProduct = $toolProduct->getProductForSku($product->sku);

        $dataProductFormatted = $toolProduct->getDataFormattedToIntegration($product);

        // Loja usa catálogo.
        if ($toolProduct->store_uses_catalog) {
            if (count($dataProductFormatted['variations']['value'])) {
                foreach ($dataProductFormatted['variations']['value'] as $variation) {
                    $verifyVariation = $toolProduct->getVariationForSkuAndSkuVar($skuProductPai, $variation['sku']);
                    if ($verifyVariation) {
                        // Variação atualizada com código da integradora.
                        if ($verifyVariation['variant_id_erp'] != $variation['id']) {
                            $toolProduct->updateProductIdIntegration($skuProductPai, $variation['id'], $variation['sku']);
                        }
                    }
                }
            } else {
                $toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);
            }
            return;
        }

        if (empty($verifyProduct)) {
            try {
                $toolProduct->sendProduct($dataProductFormatted, true);

                // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                // muitas vezes o produto chegará não categorizado então esse cenário não acontecerá
                $verifyProduct = $toolProduct->getProductForSku($skuProductPai);
                $attributes = $toolProduct->getAttributeProduct($verifyProduct["id"], $skuProductPai);
                if (!empty($attribute)) {
                    $toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                }

                $toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);

                if (count($product->variations)) {
                    foreach ($product->variations as $variation) {
                        $toolProduct->updateProductIdIntegration($skuProductPai, $variation->id, $variation->sku);
                    }
                }
            } catch (InvalidArgumentException $exception) {
                throw new Exception($exception->getMessage());
            }
        }
        // sku do produto pai encontrado na loja, precisa ver se todos os skus estão cadastrados nas variações
        else {
            // ler todos os skus, para saber se todas as variações estão cadastradas
            foreach ($dataProductFormatted['variations']['value'] as $variation) {
                $verifyVariation = $toolProduct->getVariationForSkuAndSkuVar($skuProductPai, $variation['sku']);
                // variação não localizada cadastrada no produto pai
                if (!$verifyVariation) {
                    try {
                        $toolProduct->sendVariation($dataProductFormatted, $variation['sku'], $skuProductPai);
                        $toolProduct->updateProductIdIntegration($skuProductPai, $variation['id'], $variation['sku']);
                    } catch (InvalidArgumentException $exception) {
                        throw new Exception($exception->getMessage());
                    }
                }
                // sku localizada, cadastrada como variação no produto
                else {
                    // Variação atualizada com código da integradora.
                    if ($verifyVariation['variant_id_erp'] != $variation['id']) {
                        $toolProduct->updateProductIdIntegration($skuProductPai, $variation['id'], $variation['sku']);
                    }
                }
            }
        }
    }

    /**
     * Atualização do produto.
     *
     * @param   object      $webhook    Dados recebido na notificação.
     * @param   int         $store      Código da loja.
     * @throws  Exception
     */
    public static function update(object $webhook, int $store)
    {
        $toolProduct  = new ToolsProduct();
        
        $toolProduct->setUniqueId($webhook->id);

        try {
            $toolProduct->startRun($store);
        } catch (InvalidArgumentException $exception) {
            if ($toolProduct->store) {
                $toolProduct->log_integration(
                    "Erro para receber notificação",
                    "<h4>Não foi possível receber a notificação de produto</h4> <p>{$exception->getMessage()}</p>",
                    "E"
                );
            }

            throw new Exception($exception->getMessage());
        }

        $product = $toolProduct->getDataProductIntegrationById($webhook->id);

        if (!$product) {
            throw new Exception("Produto ($webhook->id) não localizado.");
        }

        $verifyProduct = $toolProduct->getProductForSku($product->sku);

        if (empty($verifyProduct)) {
            try {
                Product::create($webhook, $store);
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }
            return;
        }

        // Loja usa catálogo.
        if ($toolProduct->store_uses_catalog) {
            $skuProductPai  = trim($product->sku);
            $idProductPai   = trim($product->id);

            if (!empty($product->variations)) {
                foreach ($product->variations as $variation) {
                    $verifyVariation = $toolProduct->getVariationForSkuAndSkuVar($skuProductPai, $variation->sku);
                    if ($verifyVariation) {
                        // Variação atualizada com código da integradora.
                        if ($verifyVariation['variant_id_erp'] != $variation->id) {
                            $toolProduct->updateProductIdIntegration($skuProductPai, $variation->id, $variation->sku);
                        }
                    }
                }
            } else {
                $toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);
            }
            return;
        }

        $changeStatus   = $webhook->changes->status ?? false; // não inativamos o produto por enquanto
        $changePrice    = $webhook->changes->price ?? false;
        $changeStock    = $webhook->changes->stock ?? false;

        $skuProd = $product->sku;

        if (count($product->variations)) {
            foreach ($product->variations as $variation) {
                $stock  = $variation->quantity;
                $price  = empty($variation->special_price ?? null) ? $variation->price : $variation->special_price;
                $skuVar = $variation->sku;

                if ($changePrice) {
                    $toolProduct->updatePriceVariation($skuVar, $skuProd, $price, $variation->price);
                }
                if ($changeStock) {
                    $toolProduct->updateStockVariation($skuVar, $skuProd, $stock);
                }
                // não é preço, estoque ou estoque, atualizar o sku.
                if (!$changePrice && !$changeStock && !$changeStatus) {
                    $dataProductFormatted = $toolProduct->getDataFormattedToIntegration($product);
                    foreach ($dataProductFormatted['variations']['value'] as $variation_check) {
                        if ($variation_check['sku'] != $skuVar) {
                            continue;
                        }
                        $toolProduct->updateVariation($variation_check, $skuProd);
                    }
                }
            }
        } else {
            $stock  = $product->quantity;
            $price  = empty($product->special_price ?? null) ? $product->price : $product->special_price;

            if ($changePrice) {
                $toolProduct->updatePriceProduct($skuProd, $price, $product->price);
            }
            if ($changeStock) {
                $toolProduct->updateStockProduct($skuProd, $stock);
            }
            // não é preço, estoque ou estoque, atualizar o sku.
            if (!$changePrice && !$changeStock && !$changeStatus) {
                $dataProductFormatted = $toolProduct->getDataFormattedToIntegration($product);
                $toolProduct->updateProduct($dataProductFormatted);
            }
        }
    }
}