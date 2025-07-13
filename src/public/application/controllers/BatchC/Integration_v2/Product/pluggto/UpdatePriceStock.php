<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/tiny/UpdatePrice run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/pluggto/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\pluggto\ToolsProduct;

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
     * Recupera os produtos e variações para atualização de preço e estoque.
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
                $variations = [];

                // produto está na lixeira não precisa atualizar o preço e estoque.
                if ($productDB['status'] == 3) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                    continue;
                }

                // existe variação, criarei o vetor buscando os skus da variação.
                if (!empty($productDB['has_variants'])) {
                    $existVariation = true;
                    $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = array(
                            'sku'    => $variation['sku'],
                            'id_int' => $variation['variant_id_erp']
                        );
                    }
                }
                // não existe variação, criarei o vetor buscando o sku do produto.
                else {
                    $existVariation = false;
                    $skus[] = array(
                        'sku'    => $productDB['sku'],
                        'id_int' => $productDB['product_id_erp']
                    );
                }

                // Verifica se todos os skus foram integrados pela integradora.
                $skusIntegrated = false;
                foreach ($skus as $key_sku => $skuWithoutStock) {
                    $idIntegration  = $skuWithoutStock['id_int'];
                    if (!$idIntegration) {
                        if (!$this->toolProduct->store_uses_catalog) {
                            continue;
                        }
                        $data_product_integration = $this->toolProduct->getDataProductIntegration($skuWithoutStock['sku']);
                        if ($data_product_integration) {
                            $product_integration_id = $data_product_integration->id;
                            $existIntegrationVariation = property_exists($data_product_integration, 'variations') && count($data_product_integration->variations);
                            if ($existIntegrationVariation) {
                                foreach ($data_product_integration->variations as $variation) {
                                    if ($variation->sku == $skuWithoutStock['sku']) {
                                        $product_integration_id = $variation->id;
                                    }
                                }
                            }
                            // Não encontrou o produto/variação.
                            if (!$product_integration_id) {
                                continue;
                            }
                            $this->toolProduct->updateProductIdIntegration(
                                $existVariation ? $productDB['sku'] : $skuWithoutStock['sku'],
                                $product_integration_id,
                                $existVariation ? $skuWithoutStock['sku'] : null
                            );
                            $skus[$key_sku]['id_int'] = $product_integration_id;
                        } else {
                            continue;
                        }
                    }
                    $skusIntegrated = true;
                }

                if (!$skusIntegrated) {
                    continue;
                }

                // atualiza preço e estoque
                try {
                    if ($productDB['is_variation_grouped']) {
                        foreach ($skus as $sku) {
                            $this->updatePriceStockProduct(false, $sku['sku']);
                        }
                    } else {
                        $this->updatePriceStockProduct($existVariation, $productDB['sku'], $skus);
                    }
                    $this->toolProduct->setUniqueId($productDB['sku']);
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
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
     * Validação para atualização de preço e estoque do produto e variação.
     *
     * @param  bool     $existVariation Existe variação?
     * @param  string   $skuProd        SKU do produto
     */
    private function updatePriceStockProduct(bool $existVariation, string $skuProd, array $skus = array())
    {
        // consulta a lista de produtos
        $product = null;
        try {
            $request = $this->toolProduct->request('GET', "skus/$skuProd");
            $product = Utils::jsonDecode($request->getBody()->getContents());
            $product = $product->Product ?? false;
        } catch (InvalidArgumentException | GuzzleException $exception) {
            if ($this->toolProduct->store_uses_catalog && !empty($skus)) {
                foreach ($skus as $sku) {
                    try {
                        $this->updatePriceStockProduct(!$existVariation, $sku['sku']);
                    } catch (InvalidArgumentException $exception) {
                        echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
                        continue;
                    }
                }
                return;
            }
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
            throw new InvalidArgumentException("Não foi possível obter o SKU $skuProd.");
        }

        // Não conseguiu recuperar os dados do produto.
        if ($product === false) {
            throw new InvalidArgumentException("Não foi possível obter o SKU $skuProd.");
        }

        $updatedPrice = false;
        $updatedStock = false;

        if ($existVariation) {
            foreach ($product->variations as $variation) {
                $stock  = $variation->quantity;
                $price  = empty($variation->special_price ?? null) ? $variation->price : $variation->special_price;
                $skuVar = $variation->sku;

                if ($this->toolProduct->updatePriceVariation($skuVar, $skuProd, $price,$variation->price)) {
                    $updatedPrice = true;
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Preço da variação ($skuVar) do produto ($skuProd) atualizado com sucesso.\n";
                }
                if ($this->toolProduct->updateStockVariation($skuVar, $skuProd, $stock)) {
                    $updatedStock = true;
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque da variação ($skuVar) do produto ($skuProd) atualizado com sucesso.\n";
                }
            }
        } else {
            $stock  = $product->quantity;
            $price  = empty($product->special_price ?? null) ? $product->price : $product->special_price;

            if ($this->toolProduct->updatePriceProduct($skuProd, $price,$product->price)) {
                $updatedPrice = true;
                echo "[SUCCESS][LINE:" . __LINE__ . "] Preço do produto ($skuProd) atualizado com sucesso.\n";
            }
            if ($this->toolProduct->updateStockProduct($skuProd, $stock)) {
                $updatedStock = true;
                echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque do produto ($skuProd) atualizado com sucesso.\n";
            }
        }

        if (!$updatedPrice) {
            echo "[PROCESS][LINE:" . __LINE__ . "] Preços do SKU ($skuProd) não sofreu alteração.\n";
        }
        if (!$updatedStock) {
            echo "[PROCESS][LINE:" . __LINE__ . "] Estoque do SKU ($skuProd) não sofreu alteração.\n";
        }
    }
}