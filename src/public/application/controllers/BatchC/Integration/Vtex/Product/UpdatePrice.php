<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration/Vtex/Product/UpdatePrice run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Vtex/Main.php";
require APPPATH . "controllers/BatchC/Integration/Vtex/Product/Product.php";

class UpdatePrice extends Main
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

        $this->setJob('UpdatePrice');
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
        echo "Pegando produtos para atualizar preço \n";

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
     * @throws Exception
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
                            'sku'   => $variation['sku'],
                            'price' => $variation['price']
                        ];
                    }
                } else {
                    $existVariation = false;
                    $skus[] = [
                        'sku'   => $productDB['sku'],
                        'price' => $productDB['price']
                    ];
                }

                foreach ($skus as $sku_) {

                    $sku = $sku_['sku'];
                    //$priceReal = $sku_['price'];

                    echo "SKU={$sku}. Variação: " . json_encode($existVariation) . "...\n";

                    $url = "api/catalog_system/pub/products/search?fq=skuId:{$sku}";
                    $dataProducts = $this->sendREST($url);

                    if (!in_array($dataProducts['httpcode'], [200, 206])) {
                        echo "Erro para buscar o sku {$sku} de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                        $this->log_data('batch', $log_name, "Erro para buscar o produto {$sku} de url={$url}, retorno=" . json_encode($dataProducts), "E");
                        continue;
                    }

                    $product = json_decode($dataProducts['content'])[0] ?? false;

                    // não encontrou o sku mantém o preço
                    if ($product === false) {
                        echo "Não encontrou sku {$sku}, mentem o preço do sku\n";
                        continue;
                    }

                    $id_produto     = $product->productId;
                    //$skuProductPai  = $product->productId;

                    /*if ($existVariation) {
                        $skuProductPai = "P_{$skuProductPai}";
                    }*/

                    $this->setUniqueId($id_produto); // define novo unique_id

                    //echo "=========> esperar 10s";sleep(10);
                    $this->updatePriceProduct($existVariation, $product);
                    echo "------------------------------------------------------------\n";
                }
            }

            echo "\n##### FIM PÁGINA: ({$regStart} até {$regEnd}) em ".date('H:i:s')."\n\n";
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
    private function updatePriceProduct(bool $existVariation, object $product): void
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

                $updatePrice = $this->getPriceERP($product->items[$countSku]->itemId);

                if (isset($updatePrice['success']) && $updatePrice['success'] == false) {
                    echo "SKU {$product->items[$countSku]->itemId} não localizado para atualizar o preço. {$updatePrice['message']}\n";
                    continue;
                }

                // comparar preço
                $getPriceReal = $this->product->getPriceForSku($existVariation ? 'P_'.$skuProductPai : $updatePrice['sku'], $existVariation ? $updatePrice['sku'] : null);

                if (!$getPriceReal) {
                    echo "ocorreu um problema para objet os dados do produto {$updatePrice['sku']}\n";
                    continue;
                }

                $priceReal = $getPriceReal['price'];

                if($priceReal == $updatePrice['price']) {
                    echo "Preço do produto={$updatePrice['sku']} igual ao do banco, não será modificado price=$priceReal\n";
                    continue;
                }

                if ($existVariation)
                    $update = $this->product->updatePriceVariation($updatePrice['sku'], 'P_'.$skuProductPai, $updatePrice['price']);
                else
                    $update = $this->product->updatePrice($updatePrice['sku'], $updatePrice['price']);

                if (!$update) {
                    echo "ocorreu um problema para atualizar o preço do produto {$updatePrice['sku']}\n";
                    continue;
                }

                $this->log_integration("Preço do produto {$updatePrice['sku']} atualizado", "<h4>O preço do produto {$updatePrice['sku']} foi atualizado com sucesso.</h4><strong>Preço anterior:</strong> {$priceReal}<br><strong>Preço alterado:</strong> {$updatePrice['price']}", "S");
                echo "Preço do produto={$updatePrice['sku']} atualizado com sucesso. old_price=$priceReal | new_price={$updatePrice['price']}\n";
                $this->log_data('batch', $log_name, "Preço do produto {$updatePrice['sku']} atualizado. preço_anterior={$priceReal} preço_atualizado={$updatePrice['price']}", "I");

            }
        }
    }

    /**
     * Recupera preço de um produto ou variação
     *
     * @param   string  $skuProduct     SKU do produto (Normal ou Pai)
     * @return  array                   Retorna os dados do preço do produto
     */
    public function getPriceERP(string $skuProduct): array
    {
        $price  = $this->product->getPriceErp($skuProduct);

        if ($price === false) return array('success' => false, 'message' => 'SKU não localizado.');

        return array(
            'sku'       => $skuProduct,
            'price'     => $price,
            'variation' => null
        );
    }
}