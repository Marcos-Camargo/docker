<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/lojaintegrada/UpdatePriceStock run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/lojaintegrada/ToolsProduct.php";

use Integration\Integration_v2\lojaintegrada\ToolsProduct;

class UpdatePriceStock extends BatchBackground_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolProduct = new ToolsProduct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->toolProduct->setJob(__CLASS__);
    }

    /**
     * Método responsável pelo "start" da aplicação.
     *
     * @param string|int|null $id Código do job (job_schedule.id).
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params).
     * @param string|null $prdUnit SKU do produto.
     * @return bool                         Estado da execução.
     */
    public function run($id = null, int $store = null, string $prdUnit = null): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        if (!$this->checkStartRun(
            $log_name,
            $this->router->directory,
            __CLASS__,
            $id,
            $store
        )) {
            return false;
        }

        // realiza algumas validações iniciais antes de iniciar a rotina
        try {
            $this->toolProduct->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $this->toolProduct->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            $this->gravaFimJob();
            return true;
        }

        // Recupera os produtos para atualizar preço e estoque
        try {
            $this->getProductToUpdatePriceStock($prdUnit);
        } catch (Exception $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        // Grava a última execução
        $this->toolProduct->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque.
     *
     * @param string|null $prdUnit SKU do produto
     * @return bool
     * @throws Exception
     */
    public function getProductToUpdatePriceStock(string $prdUnit = null): bool
    {
        $perPage = 200;
        $regStart = 0;
        $regEnd = $perPage;
        $existVariation = false;

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage, $prdUnit);

            // Não foi encontrado mais produtos. Fim da página.
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {
                $skus = array();

                // existe variação, criarei o vetor buscando os skus da variação.
                if (!empty($productDB['has_variants'])) {
                    $existVariation = !$productDB['is_variation_grouped'];
                    $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = [
                            'isVariation'   => !$productDB['is_variation_grouped'],
                            'sku'           => $productDB['is_variation_grouped'] ? $variation['sku'] : $productDB['sku'],
                            'varSku'        => $productDB['is_variation_grouped'] ? null : $variation['sku'],
                            'id_int'        => $variation['variant_id_erp']
                        ];
                    }
                } // não existe variação, criarei o vetor buscando o sku do produto.
                else {
                    $existVariation = false;
                    $skus[] = [
                        'isVariation' => false,
                        'sku' => $productDB['sku'],
                        'id_int' => $productDB['product_id_erp']
                    ];
                }
                if (empty($skus)) {
                    continue;
                }
                // atualiza preço e estoque
                try {
                    foreach ($skus as $key_sku => $sku) {
                        if ($this->toolProduct->store_uses_catalog && empty($sku['id_int'])) {
                            $data_product_integration = $this->toolProduct->getDataProductIntegrationBySku($sku['sku']);
                            if ($data_product_integration) {
                                if ($existVariation) {
                                    $this->toolProduct->updateProductIdIntegration($productDB['sku'], $data_product_integration->id, $sku['sku']);
                                } else {
                                    $this->toolProduct->updateProductIdIntegration($sku['sku'], $data_product_integration->id);
                                }
                                $sku['id_int'] = $data_product_integration->id;
                                $skus[$key_sku]['id_int'] = $data_product_integration->id;
                            }
                        }
                    }
                    $this->updatePriceStockProduct($skus);
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
                }
                echo "------------------------------------------------------------\n";
            }
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }

    /**
     * @param array $skus Lista de produtos/variações
     */
    private function updatePriceStockProduct(array $skus)
    {
        foreach ($skus as $sku) {
            $intStock = $this->toolProduct->fetchProductStock($sku['id_int']);
            if (isset($intStock['stock'])) {
                $stock = $intStock['stock'];
                if ($this->toolProduct->updateStockProduct($sku['sku'], $stock, $sku['varSku'] ?? null)) {
                    if (isset($sku['varSku']) && !empty($sku['varSku'])) {
                        echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque {$stock} da variação ({$sku['varSku']}) do produto ({$sku['sku']}) atualizado com sucesso.\n";
                    } else {
                        echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque {$stock} do produto ({$sku['sku']}) atualizado com sucesso.\n";
                    }
                }
            } else {
                echo "[WARNING][LINE:" . __LINE__ . "] Estoque do produto ({$sku['sku']}) não gerenciável.\n";
            }
            $intPrice = $this->toolProduct->fetchProductPrice($sku['id_int']);
            if (isset($intPrice['price']) && $intPrice['price'] > 0) {
                $price = $intPrice['price'];
                $listPrice = $intPrice['listPrice'] ?? $intPrice['price'];
                $listPrice = $listPrice > 0 ? $listPrice : $price;
                $price = $price > 0 ? $price : $listPrice;
                if ($this->toolProduct->updatePriceProduct($sku['sku'], $price, $listPrice ?? null, $sku['varSku'] ?? null)) {
                    if (isset($sku['varSku']) && !empty($sku['varSku'])) {
                        echo "[SUCCESS][LINE:" . __LINE__ . "] Preço {$price} e preço de lista {$listPrice} da variação ({$sku['varSku']}) do produto ({$sku['sku']}) atualizado com sucesso.\n";
                    } else {
                        echo "[SUCCESS][LINE:" . __LINE__ . "] Preço {$price} e preço de lista {$listPrice} do produto ({$sku['sku']}) atualizado com sucesso.\n";
                    }
                }
            } else {
                echo "[WARNING][LINE:" . __LINE__ . "] Preço do produto ({$sku['sku']}) zerado e não atualizado.\n";
            }
        }

    }
}