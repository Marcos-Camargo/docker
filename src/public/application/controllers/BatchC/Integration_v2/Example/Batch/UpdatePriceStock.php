<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/tiny/UpdatePrice run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/NEW_INTEGRATION/ToolsProduct.php";

use Integration\Integration_v2\NEW_INTEGRATION\ToolsProduct;

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
     * @param  string|int|null  $id         Código do job (job_schedule.id).
     * @param  int|null         $store      Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params).
     * @param  string|null      $prdUnit    SKU do produto.
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
        $perPage    = 200;
        $regStart   = 0;
        $regEnd     = $perPage;

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
                    $existVariation = true;
                    $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = array(
                            'id_int' => $variation['variant_id_erp']
                        );
                    }
                }
                // não existe variação, criarei o vetor buscando o sku do produto.
                else {
                    $existVariation = false;
                    $skus[] = array(
                        'id_int' => $productDB['product_id_erp']
                    );
                }

                // Verifica se todos os skus foram integrados pela integradora.
                $skusIntegrated = false;
                foreach ($skus as $skuWithoutStock) {
                    $idIntegration  = $skuWithoutStock['id_int'];
                    if ($idIntegration) {
                        $skusIntegrated = true;
                    }
                }

                if (!$skusIntegrated) {
                    continue;
                }

                // atualiza preço e estoque
                try {
                    $this->updatePriceStockProduct($existVariation, $productDB['sku']);
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                }
                echo "------------------------------------------------------------\n";
            }
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
    private function updatePriceStockProduct(bool $existVariation, string $skuProd)
    {
        // consulta a lista de produtos
        try {
            $product = $this->toolProduct->getDataProductIntegration($skuProd);
        } catch (InvalidArgumentException $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
            throw new InvalidArgumentException("Não foi possível obter o SKU $skuProd.");
        }

        if ($existVariation) {
            foreach ($product->variations as $variation) {
                $stock  = $variation->quantity;
                $price  = empty($variation->special_price ?? null) ? $variation->price : $variation->special_price;
                $skuVar = $variation->sku;

                if ($this->toolProduct->updatePriceProduct($skuProd, $price, $skuVar)) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Preço da variação ($skuVar) do produto ($skuProd) atualizado com sucesso.\n";
                }
                if ($this->toolProduct->updateStockProduct($skuProd, $stock, $skuVar)) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque da variação ($skuVar) do produto ($skuProd) atualizado com sucesso.\n";
                }
            }
        } else {
            $stock  = $product->quantity;
            $price  = empty($product->special_price ?? null) ? $product->price : $product->special_price;

            if ($this->toolProduct->updatePriceProduct($skuProd, $price)) {
                echo "[SUCCESS][LINE:" . __LINE__ . "] Preço do produto ($skuProd) atualizado com sucesso.\n";
            }
            if ($this->toolProduct->updateStockProduct($skuProd, $stock)) {
                echo "[SUCCESS][LINE:" . __LINE__ . "] Estoque do produto ($skuProd) atualizado com sucesso.\n";
            }
        }
    }
}