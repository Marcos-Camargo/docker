<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/anymarket/UpdatePriceStock run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/anymarket/ToolsProduct.php";

use GuzzleHttp\Utils;
use Integration\Integration_v2\anymarket\ApiException;
use Integration\Integration_v2\anymarket\ToolsProduct;

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
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @param string|null $prdUnit SKU do produto
     * @return bool                         Estado da execução
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
     * Recupera os produtos e variações para atualização de preço e estoque
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

        while (true) {
            $products_queue = $this->db->select()
                ->where(array(
                    'integration_id' => $this->toolProduct->integrationData['id'],
                    'checked' => 0
                ))
                ->limit($perPage, $regStart)
                ->order_by('id', 'desc')
                ->get('anymarket_queue')
                ->result_array();

            // não foi mais encontrado produtos
            if (count($products_queue) === 0) {
                break;
            }

            foreach ($products_queue as $product_queue) {
                $data_prd_integration = $this->toolProduct->getIdIntegrationByIdIntegration($product_queue['idSku']);
                if (!$data_prd_integration) {
                    $data_prd_integration = $this->toolProduct->getIdIntegrationByIdIntegration($product_queue['idSku'], true);
                    if (!$data_prd_integration) {
                        $data_prd_integration = $this->toolProduct->getIdIntegrationByIdIntegration($product_queue['idProduct']);
                        if (!$data_prd_integration) {
                            $data_prd_integration = $this->toolProduct->getIdIntegrationByIdIntegration($product_queue['idProduct'], true);
                        }
                    }
                }

                if (!$data_prd_integration) {
                    echo "SKU não encontrado idSku=$product_queue[idSku] | idProduct=$product_queue[idProduct]\n";
                    $this->toolProduct->setCheckedAnymarketQueue($product_queue);
                    continue;
                }

                $productDB = $data_prd_integration;
                if (array_key_exists('prd_id', $data_prd_integration)) {
                    $productDB = $this->toolProduct->getProductById($data_prd_integration['prd_id']);
                }

                $skus = array();

                // produto está na lixeira não precisa atualizar o preço e estoque
                if ($productDB['status'] == Model_products::DELETED_PRODUCT) {
                    echo "[PROCESS][LINE:" . __LINE__ . "] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                    $this->toolProduct->setCheckedAnymarketQueue($product_queue);
                    continue;
                }

                // existe variação, vou criar o array buscando os skus da variação
                if (!empty($productDB['has_variants'])) {
                    $existVariation = !$productDB['is_variation_grouped'];
                    $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = [
                            'sku' => $variation['sku'],
                            'qty' => $variation['qty'],
                            'id_int' => $variation['variant_id_erp']
                        ];
                    }
                } // não existe variação, criarei o array buscando o sku do produto.
                else {
                    $existVariation = false;
                    $skus[] = [
                        'sku' => $productDB['sku'],
                        'qty' => $productDB['qty'],
                        'id_int' => $productDB['product_id_erp']
                    ];
                }

                foreach ($skus as $skuCheck) {
                    $sku = $skuCheck['sku'];

                    if ($this->toolProduct->store_uses_catalog && empty($skuCheck['id_int'])) {
                        try {
                            $data_product_integration = $this->toolProduct->getProductByPartnerId($sku);
                            $skuCheck['id_int'] = $existVariation ? $data_product_integration->id : $data_product_integration->product->id;

                            if (!$existVariation) {
                                $this->toolProduct->updateProductIdIntegration($sku, $skuCheck['id_int']);
                            } else {
                                $this->toolProduct->updateProductIdIntegration($productDB['sku'], $skuCheck['id_int'], $sku);
                            }
                        } catch (ApiException $exception) {
                            continue;
                        }
                    }

                    $stockReal = $skuCheck['qty'];
                    $idIntegration = $skuCheck['id_int'];

                    $this->db->select()
                        ->from('anymarket_queue')
                        ->where('integration_id', $this->toolProduct->integrationData['id']);

                    if ($this->toolProduct->store_uses_catalog) {
                        $this->db->group_start()
                            ->like('idSku', $skuCheck['id_int'])
                            ->or_like('idProduct', $skuCheck['id_int'])
                        ->group_end();
                    } else {
                        $this->db->where(($existVariation ? 'idSku' : 'idProduct'), $skuCheck['id_int']);
                    }

                    $queueProd = (array)$this->db->order_by('id', 'desc')
                        ->limit(1)
                        ->get()->row();

                    if ($this->toolProduct->store_uses_catalog && !$queueProd) {
                        try {
                            $data_product_integration = $this->toolProduct->getProductByPartnerId($sku);
                            if ($data_product_integration->product->id != $skuCheck['id_int']) {
                                $this->toolProduct->updateProductIdIntegration($sku, $data_product_integration->product->id);
                            }
                        } catch (ApiException $exception) {
                            continue;
                        }
                    }
                    
                    $receiveData = json_decode($queueProd["received_body"] ?? '{}', true);
                    $receiveData['idSkuMarketplace'] = $receiveData['idSkuMarketplace'] ?? $queueProd['idSkuMarketplace'] ?? null;
                    if (empty($receiveData['idSkuMarketplace'])) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Dados do produto $sku, não localizados na fila de integração.\n";
                        continue;
                    }

                    $product = null;
                    try {
                        try {
                            $this->toolProduct->setUniqueId($receiveData['idSkuMarketplace']);
                            $bodyRequest = $this->toolProduct->getDataProductIntegration($receiveData['idSkuMarketplace']);
                            $bodyRequest->{'status'} = $bodyRequest->status;
                            $bodyRequest->{'idSku'} = $bodyRequest->sku->id;
                            $bodyRequest->{'availableAmount'} = $bodyRequest->stock->availableAmount;
                            $parsedProduct = $this->toolProduct->getFormattedProductFieldsToUpdate($bodyRequest, $idIntegration);
                            $normalizedProduct = $this->toolProduct->normalizedFormattedData($parsedProduct);
                            if ($existVariation && !empty($normalizedProduct['variations'])) {
                                $sku_exists = false;
                                foreach ($normalizedProduct['variations'] as $variation) {
                                    if ($idIntegration == $variation['_variant_id_erp']) {
                                        $sku_exists = true;
                                        break;
                                    }
                                }
                                if (!$sku_exists) {
                                    echo "[PROCESS][LINE:" . __LINE__ . "] Os dados do produto não correspondem ao produto na plataforma [$sku].\n";
                                    continue;
                                }
                            } else if (!$existVariation) {
                                if ($idIntegration != ($normalizedProduct['variations'][0]['_variant_id_erp'] ?? $normalizedProduct['_product_id_erp'] ?? null)) {
                                    echo "[PROCESS][LINE:" . __LINE__ . "] Os dados do produto não correspondem ao produto na plataforma [$sku].\n";
                                    continue;
                                }
                            } else {
                                echo "[PROCESS][LINE:" . __LINE__ . "] Os dados do produto não correspondem ao produto na plataforma [$sku].\n";
                                continue;
                            }
                            $product = $normalizedProduct;
                        } catch (Throwable $e) {
                            if (in_array($e->getCode(), [404])) {
                                $this->db->delete('anymarket_queue', [
                                        'id' => $queueProd['id']
                                    ]
                                );
                            }
                            echo "[PROCESS][LINE:" . __LINE__ . "] Ocorreu um erro ao processar os dados do produto $sku: {$e->getMessage()} ({$e->getCode()})\n";
                            continue;
                        }

                        if ($idIntegration) {
                            if ($product === null) {
                                echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou sku $sku, zerando estoque do sku, caso não esteja zerado e mantém o preço como está\n";
                                if ($stockReal != 0) {
                                    if ($existVariation) {
                                        $variation = current($product['variations']);
                                        if (!in_array($variation['status'] ?? '', [
                                            Model_products::ACTIVE_PRODUCT, Model_products::BLOCKED_PRODUCT
                                        ])) {
                                            if ($this->toolProduct->updateStockVariation($sku, $productDB['sku'], 0)) {
                                                echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque da variação ({$productDB['sku']}) do produto ($sku) atualizado com sucesso.\n";
                                            }
                                        }
                                    } else {
                                        if (!in_array($product['status'] ?? '', [
                                            Model_products::ACTIVE_PRODUCT, Model_products::BLOCKED_PRODUCT
                                        ])) {
                                            if ($this->toolProduct->updateStockProduct($sku, 0)) {
                                                echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque do produto ($sku) atualizado com sucesso.\n";
                                            }
                                        }
                                    }
                                }
                                echo "------------------------------------------------------------\n";
                                continue;
                            }

                            $this->updatePriceStockProduct($existVariation, $product);
                        }
                    } catch (Throwable $e) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Ocorreu um erro ao processar os dados do produto $sku: {$e->getMessage()} ({$e->getCode()})\n";
                        continue;
                    }
                }
                $this->toolProduct->setCheckedAnymarketQueue($product_queue);
                echo "------------------------------------------------------------\n";
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) " . date('H:i:s') . "\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }

    /**
     * Validação para atualização de preço e estoque do produto e variação
     *
     * @param bool $existVariation Existe variação?
     * @param object|array $product Dados do produto, vindo do ERP
     */
    private function updatePriceStockProduct(bool $existVariation, $product): void
    {
        $product = (array)$product;
        $sku = $product['sku'];

        echo "[PROCESS][LINE:" . __LINE__ . "][VARIATION: " . Utils::jsonEncode($existVariation) . "] SKU($sku)\n";

        if ($existVariation) {
            $variation = (array)current($product['variations']);
            $verifyProduct = $this->toolProduct->getVariationForSkuAndSkuVar($product['sku'], $variation['sku']);
        } else {
            $verifyProduct = $this->toolProduct->getProductForSku($sku);
        }

        if (!$verifyProduct) {
            echo "[PROCESS][LINE:" . __LINE__ . "] SKU ($sku) não encontrado na loja\n";
            return;
        }
        $price = $product['price'];
        $list_price = $product['list_price'];
        if ($existVariation || $verifyProduct['is_variation_grouped']) {
            $variation = (array)current($product['variations']);
            if ($this->toolProduct->updatePriceVariation($variation['sku'], $product['sku'], $variation['price'],$variation['list_price'])) {
                echo "[SUCCESS][LINE:" . __LINE__ . "] Preço da variação ($variation[sku]) do produto ($product[sku]) atualizado com sucesso.\n";
            }
            if ($this->toolProduct->updateStockVariation($variation['sku'], $product['sku'], $variation['qty'])) {
                echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque da variação ($variation[sku]) do produto ($product[sku]) atualizado com sucesso.\n";
            }
        } else {
            if ($this->toolProduct->updatePriceProduct($product['sku'], $price,$list_price)) {
                echo "[SUCCESS][LINE:" . __LINE__ . "] Preço do produto ($sku) atualizado com sucesso.\n";
            }
            if (isset($product['stock']) && $this->toolProduct->updateStockProduct($product['sku'], $product['stock'])) {
                echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque do produto ($sku) atualizado com sucesso.\n";
            }
        }
    }
}