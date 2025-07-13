<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/bling_v3/UpdatePrice run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/bling_v3/ToolsProduct.php";

use GuzzleHttp\Utils;
use Integration\Integration_v2\bling_v3\ToolsProduct;

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
     * @param  string|int|null  $id         Código do job (job_schedule.id)
     * @param  int|null         $store      Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @param  string|null      $prdUnit    SKU do produto
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
            $this->toolProduct->setLastRun();
            $this->getProductToUpdatePriceStock($prdUnit === 'null' ? null : $prdUnit);
        } catch (Exception $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
        }

        $date_last_job = $this->toolProduct->dateLastJob;
        $this->toolProduct->setLastRun();

        if ($date_last_job == $this->toolProduct->dateLastJob) {
            // Grava a última execução
            $this->toolProduct->saveLastRun();
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque
     *
     * @param  string|null  $prdUnit    SKU do produto
     * @return bool
     * @throws Exception
     */
    public function getProductToUpdatePriceStock(string $prdUnit = null): bool
    {
        $perPage            = 200;
        $regStart           = 0;
        $regEnd             = $perPage;

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage, $prdUnit);

            // não foi mais encontrado produtos
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {
                $skus = array();

                // existe variação, vou criar o array buscando os skus da variação
                if (!empty($productDB['has_variants'])) {
                    $existVariation = true;
                    $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = [
                            'sku'       => $variation['sku'],
                            'qty'       => $variation['qty'],
                            'id_int'    => $variation['variant_id_erp']
                        ];
                    }
                }
                // não existe variação, criarei o array buscando o sku do produto.
                else {
                    $existVariation = false;
                    $skus[] = [
                        'sku'       => $productDB['sku'],
                        'qty'       => $productDB['qty'],
                        'id_int'    => $productDB['product_id_erp']
                    ];
                }

                // consulta o sku
                $product = null;
                if (!empty($productDB['product_id_erp'])) {
                    $product = $this->toolProduct->getDataProductIntegration($productDB['product_id_erp']);
                }

                foreach ($skus as $key => $skuCheck) {
                    $sku            = $skuCheck['sku'];
                    $stockReal      = $skuCheck['qty'];
                    $idIntegration  = $skuCheck['id_int'];

                    $this->toolProduct->setUniqueId($sku);
                    // só atualiza se o sku realmente existe na integradora
                    if ($idIntegration) {
                        // O primeiro job vai comparar o código do bling para atualizar.
                        if (!$this->toolProduct->dateLastJob) {
                            $product_check = $this->toolProduct->getDataProductIntegrationBySku($sku);
                            if ($product_check) {
                                $product_id = $product_check->id;

                                // Existe variação, vou atualizar o id do produto pai também
                                if ($existVariation && $product_check->idProdutoPai != $productDB['product_id_erp']) {
                                    $this->toolProduct->updateProductIdIntegration(
                                        $productDB['sku'],
                                        $product_check->idProdutoPai
                                    );
                                    echo "[SUCCESS][LINE:".__LINE__."] Código da integradora atualizada para o sku ($productDB[sku]).\n";
                                }

                                // Se o novo product_id for diferente.
                                if ($product_id != $skuCheck['id_int']) {
                                    // Atualizar id do produto/variação.
                                    $this->toolProduct->updateProductIdIntegration(
                                        $existVariation ? $productDB['sku'] : $sku,
                                        $product_id,
                                        $existVariation ? $sku : null
                                    );
                                    echo "[SUCCESS][LINE:".__LINE__."] Código da integradora atualizada para o sku ($sku).\n";
                                }

                                if (is_null($product)) {
                                    if ($existVariation) {
                                        $product = $this->toolProduct->getDataProductIntegration($product_check->idProdutoPai);
                                    } else {
                                        $product = $this->toolProduct->getDataProductIntegration($product_id);
                                    }
                                }

                                $skuCheck['id_int'] = $product_id;
                            } else {
                                echo "[ERRO][LINE:".__LINE__."] Produto ($sku) não encontrado para associar o ID!\n";
                            }
                        }

                        if (!$existVariation && !empty($product->variacoes)) {
                            echo "[ERRO][LINE:".__LINE__."] Produto ($sku) não tem variação, mas na integradora tem!\n";
                            $this->toolProduct->log_integration(
                                "Erro para atualizar o produto ($sku)",
                                "<h4>Não foi possível atualizar o produto ($sku)</h4> <p>Produto não tem variação no seller center, mas na integradora tem!</p>",
                                "E"
                            );
                            continue;
                        }

                        // não encontrou o sku precisa zerar o estoque e manter o preço como está, de todos os skus
                        if ($productDB['status'] == 2 || $product === null || $product->situacao != "A") {
                            echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou sku $sku ou inativo.\n";

                            /*if ($stockReal != 0) {
                                if ($existVariation) {
                                    if ($this->toolProduct->updateStockVariation($sku, $productDB['sku'], 0)) {
                                        echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque da variação ({$productDB['sku']}) do produto ($sku) atualizado com sucesso.\n";
                                    }
                                } else {
                                    if ($this->toolProduct->updateStockProduct($sku, 0)) {
                                        echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque do produto ($sku) atualizado com sucesso.\n";
                                    }
                                }
                            }*/
                            echo "------------------------------------------------------------\n";
                            continue;
                        }

                        // atualiza preço e estoque
                        $this->updatePriceStockProduct($existVariation, $product, $skuCheck);
                    //} elseif ($this->toolProduct->store_uses_catalog) {
                    } elseif ($this->toolProduct->store_uses_catalog || !$this->toolProduct->dateLastJob) {
                        $product = $this->toolProduct->getDataProductIntegrationBySku($sku);
                        if ($product) {
                            $product_id = $product->id;
                            $this->toolProduct->updateProductIdIntegration(
                                $existVariation ? $productDB['sku'] : $sku,
                                $product_id,
                                $existVariation ? $sku : null
                            );
                        }

                        // Quando for a primeira variação e existe o idProdutoPai, atualiza o pai também
                        if (
                            $existVariation &&
                            $key == 0 &&
                            !empty($product) &&
                            property_exists($product, 'idProdutoPai') &&
                            !empty($product->idProdutoPai)
                        ) {
                            if ($product->idProdutoPai != $productDB['product_id_erp']) {
                                $this->toolProduct->updateProductIdIntegration(
                                    $productDB['sku'],
                                    $product->idProdutoPai
                                );
                            }
                        }
                    }
                }
                echo "------------------------------------------------------------\n";
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }

    /**
     * Validação para atualização de preço e estoque do produto e variação
     *
     * @param  bool     $existVariation Existe variação?
     * @param  object   $product        Dados do produto, vindo do ERP
     */
    private function updatePriceStockProduct(bool $existVariation, object $product, array $skuCheck): void
    {
        $sku_product = $product->codigo;
        $sku = $skuCheck['sku'];
        $this->toolProduct->setUniqueId($sku);

        echo "[PROCESS][LINE:".__LINE__."][VARIATION: ".Utils::jsonEncode($existVariation)."] SKU($sku)\n";

        // existe variação então verifico se a variação existe
        if ($existVariation) {
            $verifyProduct = $this->toolProduct->getVariationForSkuAndSkuVar($sku_product, $sku);
            $product = getArrayByValueIn($product->variacoes, $sku, 'codigo');

            if (!$product) {
                echo "[ERROR][LINE:".__LINE__."] SKU($sku) não encontrado no sku pai ($sku_product)\n";
                return;
            }
        }
        // não existe variação então verifico se o produto simples existe
        else {
            $verifyProduct = $this->toolProduct->getProductForSku($sku);
        }

        // não encontrou o sku na base
        if (!$verifyProduct) {
            echo "[PROCESS][LINE:".__LINE__."] SKU ($sku) não encontrado na loja\n";
            return;
        }

        // Produto não está na multiloja
        $product_loja = null;
        if (!empty($this->toolProduct->credentials->loja_bling)) {
            $product_loja = $this->toolProduct->getProductIntegrationByLojaAndProduct($product->id);
            if (!$product_loja) {
                echo "[PROCESS][LINE: " . __LINE__ . "] Produto $product->codigo não está na multiloja\n";
                return;
            }
        }

        $price      = $product->preco;
        $list_price = $product->preco;

        if (!empty($product_loja)) {
            $price      = $product_loja->precoPromocional == 0 ? $product_loja->preco : $product_loja->precoPromocional;
            $list_price = $product_loja->preco;
        }

        $stock = $this->toolProduct->getStockErp($product->id);
        $stock = $stock['stock_product'] ?? 0;

        // atualiza o preço e estoque da variação
        if ($existVariation) {
            if ($this->toolProduct->updatePriceVariation($sku, $sku_product, $price, $list_price)) {
                echo "[SUCCESS][LINE:".__LINE__."] Preço da variação ($sku) do produto ($sku_product) atualizado com sucesso.\n";
            }
            if ($this->toolProduct->updateStockVariation($sku, $sku_product, $stock)) {
                echo "[SUCCESS][LINE:".__LINE__."] Estoque da variação ($sku) do produto ($sku_product) atualizado com sucesso.\n";
            }
        }
        // atualiza o preço e estoque do produto
        else {
            if ($this->toolProduct->updatePriceProduct($sku, $price, $list_price)) {
                echo "[SUCCESS][LINE:".__LINE__."] Preço do produto ($sku) atualizado com sucesso.\n";
            }
            if ($this->toolProduct->updateStockProduct($sku, $stock)) {
                echo "[SUCCESS][LINE:".__LINE__."] Estoque do produto ($sku) atualizado com sucesso.\n";
            }
        }
    }
}