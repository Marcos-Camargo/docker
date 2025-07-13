<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Integration/Vtex/Product/UpdateStock run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Vtex/Main.php";
require APPPATH . "controllers/BatchC/Integration/Vtex/Product/Product.php";

class UpdateStock extends Main
{
    private $product;
    private $skusAlreadyRead = array();

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('UpdateStock');
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando produtos para atualizar estoque \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        // Recupera os produtos
        $this->getProducts();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Recupera os produtos
     *
     * @return bool
     */
    public function getProducts(): bool
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $perPage = 200;
        $regStart = 0;
        $regEnd = $perPage;
        $haveProductList = true;

        while ($haveProductList) {
            $products = $this->model_products->getProductsByStore($this->store, $regStart, $perPage);

            if (count($products) === 0) {
                $haveProductList = false;
                continue;
            }

            foreach ($products as $productDB) {

                /*if (empty($productDB['product_id_erp'])) {
                    echo "Produto (id={$productDB['id']} | sku={$productDB['sku']}) não foi integrado pela VTEX\n";
                }*/

                if ($productDB['status'] == 3) {
                    echo "Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                    continue;
                }

                $skus = array();

                // existe variação
                if (!empty($productDB['has_variants'])) {
                    $existVariation = true;
                    $variations = $this->model_products->getVariants($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = [
                            'sku' => $variation['sku'],
                            'qty' => $variation['qty']
                        ];
                    }
                } else {
                    $existVariation = false;
                    $skus[] = [
                        'sku' => $productDB['sku'],
                        'qty' => $productDB['qty']
                    ];
                }

                foreach ($skus as $sku_) {

                    $sku = $sku_['sku'];
                    $stockReal = $sku_['qty'];

                    echo "SKU={$sku}. Variação: " . json_encode($existVariation) . "...\n";

                    $url = "api/catalog_system/pub/products/search?fq=skuId:{$sku}";
                    $dataProducts = $this->sendREST($url);

                    if (!in_array($dataProducts['httpcode'], [200, 206])) {
                        echo "Erro para buscar o sku {$sku} de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                        $this->log_data('batch', $log_name, "Erro para buscar o produto {$sku} de url={$url}, retorno=" . json_encode($dataProducts), "E");
                        continue;
                    }

                    $product = json_decode($dataProducts['content'])[0] ?? false;

                    // não encontrou o sku precisa zera o estoque
                    if ($product === false) {
                        echo "Não encontrou sku {$sku}, zerando estoque do sku\n";
                        //echo "=========> esperar 10s";sleep(10);
                        if ($stockReal != 0) {
                            if ($existVariation) {
                                $this->product->updateStockVariation($sku, $productDB['sku'], 0);
                            } else {
                                $this->product->updateStockProduct($sku, 0);
                            }
                            $this->log_integration("Estoque do produto/variação {$sku} atualizado", "<h4>O estoque do produto/variação {$sku} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$stockReal}<br><strong>Estoque alterado:</strong> 0", "S");
                        }

                        continue;
                    }

                    $id_produto = $product->productId;

                    $this->setUniqueId($id_produto); // define novo unique_id

                    //echo "=========> esperar 10s";sleep(10);
                    $this->updateStockProduct($existVariation, $product);
                    echo "------------------------------------------------------------\n";
                }
            }

            echo "\n##### FIM PÁGINA: ({$regStart} até {$regEnd})\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }

    /**
     * Validação para cadastro do produto
     *
     * @param  bool     $existVariation Existe variação?
     * @param  object   $product        Dados do produto, vindo do ERP
     * @return void                     Retorna estado da criação do produto
     */
    private function updateStockProduct(bool $existVariation, object $product): void
    {
        $skuProductPai  = $product->productId;
        $log_name = $this->typeIntegration . '/' . __CLASS__ . '/' . __FUNCTION__;

        for ($countSku = 0; $countSku < count($product->items); $countSku++) {
            $skuProduct = $product->items[$countSku]->itemId;
            // sku já foi lido
            if (in_array($skuProduct, $this->skusAlreadyRead)) {
                continue;
            }
            $this->skusAlreadyRead[] = $skuProduct;

            $verifyProduct = $existVariation ? $this->product->getVariationForIdErp($product->items[$countSku]->itemId) : $this->product->getProductForSku($product->items[$countSku]->itemId);
            if (!$verifyProduct)
                echo "Produto {$product->items[$countSku]->itemId} não encontrado\n";
            else { // encontrou o produto pelo código da vtex

                $updateStock = $this->getStockERP($product->items[$countSku]->itemId);

                if (isset($updateStock['success']) && $updateStock['success'] == false) {
                    echo $updateStock['message'];
                    $this->log_integration("Erro para atualizar o estoque do produto SKU {$product->items[$countSku]->itemId}", "<h4>Não foi possível atualizar o estoque do produto {$product->items[$countSku]->itemId}</h4><ul><li>{$updateStock['message'][0]}</li></ul>", "W");
                    continue;
                }
                
                // comparar estoque
                $getStockReal = $this->product->getStockForIdErp($existVariation ? 'P_'.$skuProductPai : $updateStock['sku'], $existVariation ? $updateStock['sku'] : null);

                if (!$getStockReal) {
                    echo "ocorreu um problema para objet os dados do produto {$updateStock['sku']}\n";
                    continue;
                }

                $stockReal    = $getStockReal['qty'];

                if($stockReal == $updateStock['stock']) {
                    echo "Estoque do produto={$updateStock['sku']} igual ao do banco, não será modificado\n";
                    continue;
                }

                if ($existVariation)
                    $update = $this->product->updateStockVariation($updateStock['sku'], 'P_'.$skuProductPai, $updateStock['stock']);
                else
                    $update = $this->product->updateStockProduct($updateStock['sku'], $updateStock['stock']);

                if (!$update) {
                    echo "ocorreu um problema para atualizar o estoque do produto {$updateStock['sku']}\n";
                    continue;
                }

                $this->log_integration("Estoque do produto {$updateStock['sku']} atualizado", "<h4>O estoque do produto {$updateStock['sku']} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$stockReal}<br><strong>Estoque alterado:</strong> {$updateStock['stock']}", "S");
                echo "Estoque do produto={$updateStock['sku']} atualizado com sucesso. old_stock=$stockReal | new_stock={$updateStock['stock']}\n";
                $this->log_data('batch', $log_name, "Estoque do produto {$updateStock['sku']} atualizado. estoque_anterior={$stockReal} estoque_atualizado={$updateStock['stock']}", "I");

            }
        }

    }

    /**
     * Recupera estoque de um produto ou variação
     *
     * @param   string  $skuProduct     SKU do produto (Normal ou Pai)
     * @return  array                   Retorna os dados do estoque do produto
     */
    public function getStockERP($skuProduct)
    {
        $stock  = $this->product->getStock($skuProduct);

        if ($stock === false) return array('success' => false, 'message' => 'SKU não localizado.');

        return array(
            'sku'       => $skuProduct,
            'stock'     => $stock,
            'variation' => null
        );
    }
}