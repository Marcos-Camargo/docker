<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/UpdatePrice run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/vtex/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\vtex\ToolsProduct;

class UpdatePriceStock extends BatchBackground_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

    private $checkSkuLost = array();
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
            $this->getProductToUpdatePriceStock($prdUnit);
        } catch (Exception $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
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
     * @param  string|null  $prdUnit    SKU do produto
     * @return bool
     * @throws Exception
     */
    public function getProductToUpdatePriceStock(string $prdUnit = null): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        $perPage            = 200;
        $regStart           = 0;
        $regEnd             = $perPage;
        $urlGetProduct      = "api/catalog_system/pub/products/search";
        $queryGetProduct    = array('query' => array('fq' => "skuId:0"));

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage, $prdUnit);

            // não foi mais encontrado produtos
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {

                $skus           = array();
                $checkSkuLost   = array();
                $this->checkSkuLost = array();

                // produto está na lixeira não precisa atualizar o preço e estoque
                if ($productDB['status'] == 3) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                    continue;
                }

                // existe variação, vou criar o array buscando os skus da variação
                if (!empty($productDB['has_variants'])) {
                    $existVariation = !$productDB['is_variation_grouped'];
                    $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = [
                            'sku'       => $variation['sku'],
                            'qty'       => $variation['qty'],
                            'id_int'    => $variation['variant_id_erp']
                        ];
                        $checkSkuLost[] = $variation['sku'];
                    }
                }
                // não existe variação, vou criar o array buscando o sku do produto
                else {
                    $existVariation = false;
                    $skus[] = [
                        'sku'       => $productDB['sku'],
                        'qty'       => $productDB['qty'],
                        'id_int'    => $productDB['product_id_erp']
                    ];
                    $checkSkuLost[] = $productDB['sku'];
                }

                // novos valores de filtro para pegar mais produtos
                $queryGetProduct['query']['fq'] = "skuId:{$skus[0]['sku']}";
                $queryGetProduct['query']['sc'] = $this->toolProduct->credentials->sales_channel_vtex;

                // consulta a lista de produtos
                try {
                    $request = $this->toolProduct->request('GET', $urlGetProduct, $queryGetProduct);
                } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException | GuzzleException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                    $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
                    continue;
                }

                $product = Utils::jsonDecode($request->getBody()->getContents());
                $product = $product[0] ?? false;

                // não encontrou o sku precisa zerar o estoque e manter o preço como está, de todos os skus
                if ($product === false) {
                    foreach ($skus as $skuWithoutStock) {
                        $sku            = $skuWithoutStock['sku'];
                        $stockReal      = $skuWithoutStock['qty'];
                        $idIntegration  = $skuWithoutStock['id_int'];

                        if ($idIntegration) {
                            echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou sku $sku, zerando estoque do sku, caso não esteja zerado e mantém o preço como está\n";

                            if ($stockReal != 0) {
                                if ($existVariation) {
                                    if ($this->toolProduct->updateStockVariation($sku, $productDB['sku'], 0)) {
                                        echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque da variação ({$productDB['sku']}) do produto ($sku) atualizado com sucesso.\n";
                                    }
                                } else {
                                    if ($this->toolProduct->updateStockProduct($sku, 0)) {
                                        echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque do produto ($sku) atualizado com sucesso.\n";
                                    }
                                }
                            }
                        }
                    }
                    echo "------------------------------------------------------------\n";
                    continue;
                }

                // Verifica se todos os skus foram integrados pela integradora
                $skusIntegrated = false;
                foreach ($skus as $key_sku => $skuWithoutStock) {
                    $idIntegration  = $skuWithoutStock['id_int'];
                    $sku            = $skuWithoutStock['sku'];

                    if ($idIntegration) {
                        $skusIntegrated = true;
                    } elseif ($this->toolProduct->store_uses_catalog) {
                        if ($existVariation) {
                            $this->toolProduct->updateProductIdIntegration($productDB['sku'], $sku, $sku);
                        } else {
                            $this->toolProduct->updateProductIdIntegration($sku, $sku);
                        }
                        $skus[$key_sku]['id_int'] = $sku;
                        $skusIntegrated = true;
                    }
                }

                if (!$skusIntegrated) {
                    continue;
                }

                // atualiza preço e estoque
                $this->updatePriceStockProduct($existVariation, $product);
                
                // se existir alguma variação no produto que não esteja disponível, o estoque será zerado
                $skusLost = array_diff($this->checkSkuLost, $checkSkuLost);
                if (count($skusLost)) {
                    echo "[PROCESS][LINE:".__LINE__."] SKUs perdidos (".implode(',', $skusLost).")\n";
                    foreach ($skusLost as $skuLost) {
                        $stockReal = 0;

                        foreach ($skus as $sku) {
                            if ($sku['sku'] == $skuLost) {
                                $stockReal = $sku['qty'];
                                break;
                            }
                        }

                        if ($stockReal != 0) {
                            if ($existVariation) {
                                if ($this->toolProduct->updateStockVariation($skuLost, $productDB['sku'], 0)) {
                                    echo "[SUCCESS][LINE:".__LINE__."] Estoque da variação ({$productDB['sku']}) do produto ($skuLost) atualizado com sucesso.\n";
                                }
                            } else {
                                if ($this->toolProduct->updateStockProduct($skuLost, 0)) {
                                    echo "[SUCCESS][LINE:".__LINE__."] Estoque do produto ($skuLost) atualizado com sucesso.\n";
                                }
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
    private function updatePriceStockProduct(bool $existVariation, object $product)
    {
        $productId  = $product->productId;

        for ($countSku = 0; $countSku < count($product->items); $countSku++) {

            $sku = $product->items[$countSku]->itemId;
            $this->toolProduct->setUniqueId($sku);

            echo "[PROCESS][LINE:".__LINE__."][VARIATION: ".Utils::jsonEncode($existVariation)."] SKU($sku) PRODUCT_ID($productId)\n";

            // add os skus aqui para validar se existe algum sku fora essa variavel
            $this->checkSkuLost[] = $sku;

            // existe variação então verifico se a variação existe
            if ($existVariation) {
                $verifyProduct = $this->toolProduct->getVariationForSkuAndSkuVar("P_$productId", $sku);
            }
            // não existe variação então verifico se o produto simples existe
            else {
                $verifyProduct = $this->toolProduct->getProductForSku($sku);
            }

            // não encontrou o sku na base
            if (!$verifyProduct && !$this->toolProduct->store_uses_catalog) {
                echo "[PROCESS][LINE:".__LINE__."] SKU ($sku) não encontrado na loja\n";
                continue;
            }

            // stock_product
            // stock_variation
            // price_product
            // listPrice_product
            // price_variation
            //listPrice_variation
            $priceStockIntegration = $this->toolProduct->getPriceStockErp($sku);

            // sku não disponível, precisa zerar o estoque a manter o preço como está
            if ($priceStockIntegration === null) {
                if ($existVariation) {
                    $this->toolProduct->updateStockVariation($sku, "P_$productId", 0);
                } else {
                    $this->toolProduct->updateStockProduct($sku, 0);
                }
                echo "[PROCESS][LINE:".__LINE__."] SKU ($sku) não disponível, precisa zerar o estoque a manter o preço como está\n";
                continue;
            }

            // sku não disponível, precisa zerar o estoque a manter o preço como está
            if ($priceStockIntegration === false) {
                $this->toolProduct->log_integration(
                    "Erro para consultar o estoque e preço do SKU $sku",
                    "<h4>Não foi possível consultar o estoque do SKU $sku na integradora</h4><p>Um dos motivos pode ser alguma instabilidade</p>",
                    "W"
                );
                continue;
            }

            // atualiza o preço e estoque da variação
            if ($existVariation) {
                if ($this->toolProduct->updatePriceVariation($sku, "P_$productId", $priceStockIntegration['price_product'], $priceStockIntegration['listPrice_product'])) {
                    echo "[SUCCESS][LINE:".__LINE__."] Preço da variação ($sku) do produto (P_$productId) atualizado com sucesso.\n";
                }
                if ($this->toolProduct->updateStockVariation($sku, "P_$productId", $priceStockIntegration['stock_product'])) {
                    echo "[SUCCESS][LINE:".__LINE__."] Estoque da variação ($sku) do produto (P_$productId) atualizado com sucesso.\n";
                }
            }
            // atualiza o preço e estoque do produto
            else {
                if ($this->toolProduct->updatePriceProduct($sku, $priceStockIntegration['price_product'], $priceStockIntegration['listPrice_product'])) {
                    echo "[SUCCESS][LINE:".__LINE__."] Preço do produto ($sku) atualizado com sucesso.\n";
                }
                if ($this->toolProduct->updateStockProduct($sku, $priceStockIntegration['stock_product'])) {
                    echo "[SUCCESS][LINE:".__LINE__."] Estoque do produto ($sku) atualizado com sucesso.\n";
                }
            }
        }
    }
}