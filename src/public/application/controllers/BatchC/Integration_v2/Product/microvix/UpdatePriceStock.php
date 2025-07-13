<?php

require APPPATH . "libraries/Integration_v2/microvix/ToolsProduct.php";

use Integration\Integration_v2\microvix\ToolsProduct;
use GuzzleHttp\Utils;

class UpdatePriceStock extends BatchBackground_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

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

                // produto está na lixeira não precisa atualizar o preço e estoque.
                if ($productDB['status'] == 3) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                    continue;
                }

                $skus[] = [
                    'sku'       => $productDB['sku'],
                    'qty'       => $productDB['qty'],
                    'id_int'    => $productDB['product_id_erp']
                ];

                // consulta o sku
                $product = null;
                if (!empty($productDB['product_id_erp'])) {
                    $product = $this->toolProduct->getDataProductIntegration($productDB['product_id_erp']);
                }

                foreach ($skus as $key => $skuCheck) {
                    $sku            = $skuCheck['sku'];
                    $idIntegration  = $skuCheck['id_int'];

                    $this->toolProduct->setUniqueId($sku);
                    // só atualiza se o sku realmente existe na integradora
                    if ($idIntegration) {
                        // O primeiro job vai comparar o código do microvix para atualizar.
                        if (!$this->toolProduct->dateLastJob) {
                            $product_check = $this->toolProduct->getDataProductIntegration($sku);
                            if ($product_check) {
                                $product_id = $product_check['codigoproduto'];

                                // Se o novo product_id for diferente.
                                if ($product_id != $skuCheck['id_int']) {
                                    // Atualizar id do produto/variação.
                                    $this->toolProduct->updateProductIdIntegration(
                                        $sku,
                                        $product_id,
                                         null
                                    );
                                    echo "[SUCCESS][LINE:".__LINE__."] Código da integradora atualizada para o sku ($sku).\n";
                                }

                                if (is_null($product)) {
                                    $product = $this->toolProduct->getDataProductIntegration($product_id);
                                }

                                $skuCheck['id_int'] = $product_id;
                            } else {
                                echo "[ERRO][LINE:".__LINE__."] Produto ($sku) não encontrado para associar o ID!\n";
                            }
                        }

                        // não encontrou o sku precisa zerar o estoque e manter o preço como está, de todos os skus
                        if ($productDB['status'] == 2 || $product === null) {
                            echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou sku $sku ou inativo.\n";
                            echo "------------------------------------------------------------\n";
                            continue;
                        }

                        // atualiza preço e estoque
                        $this->updatePriceStockProduct($product, $skuCheck);
                        //} elseif ($this->toolProduct->store_uses_catalog) {
                    } elseif ($this->toolProduct->store_uses_catalog || !$this->toolProduct->dateLastJob) {
                        $product = $this->toolProduct->getDataProductIntegration($sku);
                        if ($product) {
                            $product_id = $product['codigoproduto'];
                            $this->toolProduct->updateProductIdIntegration(
                                $sku,
                                $product_id
                            );
                        }

                        // Quando for a primeira variação e existe o idProdutoPai, atualiza o pai também
                        if (
                            $key == 0 &&
                            !empty($product) &&
                            array_search('codigoproduto', $product) &&
                            !empty($product['codigoproduto'])
                        ) {
                            if ($product['codigoproduto'] != $productDB['product_id_erp']) {
                                $this->toolProduct->updateProductIdIntegration(
                                    $productDB['sku'],
                                    $product['codigoproduto']
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
     * @param  object   $product        Dados do produto, vindo do ERP
     */
    private function updatePriceStockProduct(array $product, array $skuCheck): void
    {
        $sku = $skuCheck['sku'];
        $this->toolProduct->setUniqueId($sku);

        $verifyProduct = $this->toolProduct->getProductForSku($sku);

        // não encontrou o sku na base
        if (!$verifyProduct) {
            echo "[PROCESS][LINE:".__LINE__."] SKU ($sku) não encontrado na loja\n";
            return;
        }

        $priceProduct = $this->toolProduct->getPriceErp($product['codigoproduto']);
        if (!empty($priceProduct)) {
            $price      = $priceProduct['price_product'];
            $list_price = $priceProduct['listPrice_product'];

            if ($this->toolProduct->updatePriceProduct($sku, $price, $list_price)) {
                echo "[SUCCESS][LINE:".__LINE__."] Preço do produto ($sku) atualizado com sucesso.\n";
            }
        } else {
            echo "[ERRO][LINE:".__LINE__."] Preço do produto ($sku) não foi atualizado com sucesso. Existem algumas pendências no cadastro do produto, para corrigir na integradora\n";
        }

        $stock = $this->toolProduct->getStockErp($product['codigoproduto']);
        if (!empty($stock)) {
            $stock = $stock['stock_product'] ?? 0;
            if ($this->toolProduct->updateStockProduct($sku, $stock)) {
                echo "[SUCCESS][LINE:".__LINE__."] Estoque do produto ($sku) atualizado com sucesso.\n";
            }
        } else {
            echo "[ERRO][LINE:".__LINE__."] Estoque do produto ($sku) não foi atualizado com sucesso. Existem algumas pendências no cadastro do produto, para corrigir na integradora\n";
        }
    }
}