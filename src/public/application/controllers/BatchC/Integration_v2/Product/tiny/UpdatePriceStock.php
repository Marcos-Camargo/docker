<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/tiny/UpdatePrice run {ID} {STORE}
 *
 */

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/tiny/BaseProductTinyBatch.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

class UpdatePriceStock extends BaseProductTinyBatch
{

    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @param string|null $prdUnit SKU do produto
     * @param int|null $page Paginação da consulta
     * @return bool                         Estado da execução
     */
    public function run($id = null, int $store = null, string $prdUnit = null, $page = null): bool
    {
        return parent::run($id, $store, $prdUnit, $page);
    }

    protected function handler(array $args = []): bool
    {
        $prdUnit = trim(strtolower($args[2])) === 'null' ? null : ($args[2] ?? null);
        return $this->getProductToUpdatePriceStock($prdUnit);
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque
     *
     * @param string|null $prdUnit SKU do produto
     * @return bool
     * @throws Exception
     */
    public function getProductToUpdatePriceStock(string $prdUnit = null): bool
    {
        $perPage = 100;
        if ($prdUnit == null && $this->pagination == null) {
            $countProds = $this->toolProduct->countProductsByInterval() ?? 0;
            if ($countProds > 0) {
                $this->updateJobSchedules([
                    'module_path' => 'Integration_v2/Product/tiny/UpdatePriceStock',
                ], (int)ceil($countProds / $perPage), 10);
            }
        }

        $regStart = (int)(((int)$this->pagination ?? 0) * $perPage);
        $regEnd = $perPage;

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage, $prdUnit);

            // não foi mais encontrado produtos
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {

                try {
                    $skus = array();

                    // produto está na lixeira não precisa atualizar o preço e estoque
                    if ($productDB['status'] == 3) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                        continue;
                    }
                    $this->toolProduct->setUniqueId($productDB['sku']);

                    // existe variação, vou criar o array buscando os skus da variação
                    if (!empty($productDB['has_variants'])) {
                        $existVariation = !$productDB['is_variation_grouped'];
                        $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                        foreach ($variations as $variation) {
                            $skus[] = [
                                'skuProd'   => $productDB['is_variation_grouped'] ? null : $productDB['sku'],
                                'sku'       => $variation['sku'],
                                'qty'       => $variation['qty'],
                                'id_int'    => $variation['variant_id_erp']
                            ];
                        }
                    } // não existe variação, vou criar o array buscando o sku do produto
                    else {
                        $existVariation = false;
                        $skus[] = [
                            'sku' => $productDB['sku'],
                            'qty' => $productDB['qty'],
                            'id_int' => $productDB['product_id_erp']
                        ];
                    }

                    // Verifica se todos os skus foram integrados pela integradora
                    $skusIntegrated = false;
                    foreach ($skus as $skuWithoutStock) {
                        $idIntegration = $skuWithoutStock['id_int'];
                        if ($idIntegration) {
                            $skusIntegrated = true;
                        }
                    }

                    foreach ($skus as $key_sku => $sku) {
                        if ($this->toolProduct->store_uses_catalog && !$sku['id_int']) {
                            $data_product_integration = $this->toolProduct->getDataProductIntegrationBySku($sku['sku']);
                            if ($data_product_integration) {
                                $this->toolProduct->updateProductIdIntegration(
                                    $existVariation ? $productDB['sku'] : $sku['sku'],
                                    $data_product_integration->id,
                                    $existVariation ? $sku['sku'] : null
                                );

                                $skus[$key_sku]['id_int'] = $data_product_integration->id;
                            }
                        }
                    }

                    // atualiza preço e estoque
                    $this->updatePriceStockProduct($existVariation, $skus);
                    echo "------------------------------------------------------------\n";
                } catch (Throwable $e) {
                    echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
                }
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) " . date('H:i:s') . "\n";
            if ($this->pagination != null) {
                break;
            }
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }

    /**
     * Validação para atualização de preço e estoque do produto e variação
     *
     * @param bool $existVariation Existe variação?
     * @param array $skus Dados sobre o SKU
     */
    private function updatePriceStockProduct(bool $existVariation, array $skus)
    {
        $urlGetProduct = "produto.obter.php";
        $queryGetProduct = array('query' => array('id' => null));

        foreach ($skus as $sku) {
            // consulta a lista de produtos
            try {
                $queryGetProduct['query']['id'] = $sku['id_int'];
                $request = $this->toolProduct->request('GET', $urlGetProduct, $queryGetProduct);
                $product = Utils::jsonDecode($request->getBody()->getContents());
                $product = $product->retorno->produto ?? false;
            } catch (InvalidArgumentException | GuzzleException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
                continue;
            }
            
            $updatedPrice = false;
            $updatedStock = false;

            $this->toolProduct->setUniqueId($sku['sku']);
            
            $precoPromocional = property_exists($product, 'precoPromocional') ? (float)$product->precoPromocional : (float)$product->preco_promocional;
            $precoPromocional = $precoPromocional > 0 ? (float)$precoPromocional : null;
            $price      = empty($precoPromocional ?? null) ? ((float)$product->preco ?? 0) : $precoPromocional;
            $list_price = (float)($product->preco > 0 ? $product->preco : $precoPromocional);

            $dataPrice  = $this->toolProduct->getPriceErp($sku['id_int'], $price, $list_price);
            $price      = $dataPrice['price_product'] ?? false;
            $list_price = $dataPrice['listPrice_product'] ?? false;
            $dataStock  = $this->toolProduct->getStockErp($sku['id_int']);
            $stock      = $dataStock['stock_product'] ?? false;

            if ($existVariation) {
                if ($price !== false && $updatedPrice = $this->toolProduct->updatePriceVariation($sku['sku'], $sku['skuProd'], $price, $list_price)) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Preço da variação ({$sku['sku']}) do produto ({$sku['skuProd']}) atualizado com sucesso.\n";
                }
                if ($stock !== false && $updatedStock = $this->toolProduct->updateStockVariation($sku['sku'], $sku['skuProd'], $stock)) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque da variação ({$sku['sku']}) do produto ({$sku['skuProd']}) atualizado com sucesso.\n";
                }
            } else {
                if ($price !== false && $updatedPrice = $this->toolProduct->updatePriceProduct($sku['sku'], $price, $list_price)) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Preço do produto ({$sku['sku']}) atualizado com sucesso.\n";
                }
                if ($stock !== false && $updatedStock = $this->toolProduct->updateStockProduct($sku['sku'], $stock)) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque do produto ({$sku['sku']}) atualizado com sucesso.\n";
                }
            }

            if (!$updatedPrice) {
                echo "[PROCESS][LINE:" . __LINE__ . "] Preço do SKU ({$sku['sku']}) não sofreu alteração.\n";
            }
            if (!$updatedStock) {
                echo "[PROCESS][LINE:" . __LINE__ . "] Estoque do SKU ({$sku['sku']}) não sofreu alteração.\n";
            }
        }
    }

}