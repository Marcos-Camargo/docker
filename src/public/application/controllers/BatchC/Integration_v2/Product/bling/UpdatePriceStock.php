<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/UpdatePrice run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/bling/ToolsProduct.php";

use GuzzleHttp\Utils;
use Integration\Integration_v2\bling\ToolsProduct;

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

                foreach ($skus as $skuCheck) {
                    $sku            = $skuCheck['sku'];
                    $stockReal      = $skuCheck['qty'];
                    $idIntegration  = $skuCheck['id_int'];

                    // consulta o sku
                    $product = $this->toolProduct->getDataProductIntegration($sku);

                    if (!$product) {
                        echo "[ERRO][LINE:".__LINE__."] Produto ($sku) não localizado!\n";
                        continue;
                    }

                    if (!$existVariation && property_exists($product, 'variacoes')) {
                        $this->toolProduct->setUniqueId($sku);
                        echo "[ERRO][LINE:".__LINE__."] Produto ($sku) não tem variação, mas na integradora tem!\n";
                        $this->toolProduct->log_integration(
                            "Erro para atualizar o produto ($sku)",
                            "<h4>Não foi possível atualizar o produto ($sku)</h4> <p>Produto não tem variação no seller center, mas na integradora tem!</p>",
                            "E"
                        );
                        continue;
                    }

                    // só atualiza se o sku realmente existe na integradora
                    if ($idIntegration) {
                        // não encontrou o sku precisa zerar o estoque e manter o preço como está, de todos os skus
                        if ($product === null || $product->situacao != "Ativo") {
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
                            echo "------------------------------------------------------------\n";
                            continue;
                        }

                        // atualiza preço e estoque
                        $this->updatePriceStockProduct($existVariation, $product);
                    } elseif ($this->toolProduct->store_uses_catalog) {
                        $this->toolProduct->updateProductIdIntegration($sku, $sku);
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
    private function updatePriceStockProduct(bool $existVariation, object $product): void
    {
        $sku = $product->codigo;
        $this->toolProduct->setUniqueId($sku);

        echo "[PROCESS][LINE:".__LINE__."][VARIATION: ".Utils::jsonEncode($existVariation)."] SKU($sku)\n";

        // existe variação então verifico se a variação existe
        if ($existVariation) {
            if ($this->toolProduct->store_uses_catalog && empty($product->codigoPai)) {
                $verifyProduct = $this->toolProduct->getProductForSku($sku);
            } else {
                $verifyProduct = $this->toolProduct->getVariationForSkuAndSkuVar($product->codigoPai, $sku);
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

        if (!empty($this->toolProduct->credentials->loja_bling) && !property_exists($product, 'produtoLoja')) {
            return;
        }

        $price       = $product->preco;
        $list_price  = $product->preco;

        if (!empty($this->toolProduct->credentials->loja_bling)) {
            $parentPrice = $product->produtoLoja->preco;
            $price       = $parentPrice->precoPromocional == 0 ? $parentPrice->preco : $parentPrice->precoPromocional;
            $list_price  = $parentPrice->preco;
        }

        // consulta o estoque, caso a loja tenha configurado apenas um estoque
        $stock = $product->estoqueAtual;
        if (isset($this->toolProduct->credentials->stock_bling) && !empty($this->toolProduct->credentials->stock_bling)) {
            foreach ($product->depositos as $deposito) {
                $deposito = (array) $deposito->deposito;
                if (isset($deposito['nome']) && ($this->toolProduct->credentials->stock_bling == $deposito['nome'])) {
                    $stock = $deposito['saldo'];
                    break;
                }
            }
        }

        // atualiza o preço e estoque da variação
        if ($existVariation) {
            if ($this->toolProduct->updatePriceVariation($sku, $product->codigoPai, $price, $list_price)) {
                echo "[SUCCESS][LINE:".__LINE__."] Preço da variação ($sku) do produto ($product->codigoPai) atualizado com sucesso.\n";
            }
            if ($this->toolProduct->updateStockVariation($sku, $product->codigoPai, $stock)) {
                echo "[SUCCESS][LINE:".__LINE__."] Estoque da variação ($sku) do produto ($product->codigoPai) atualizado com sucesso.\n";
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