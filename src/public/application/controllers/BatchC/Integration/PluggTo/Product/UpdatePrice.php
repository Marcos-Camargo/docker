<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration/PluggTo/Product/UpdatePrice run
 *
 */

require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";
require APPPATH . "controllers/BatchC/Integration/PluggTo/Product/Product.php";

class UpdatePrice extends Main
{
    private $product;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
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
        echo "Buscando produtos para atualizar o preço \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        // Grava a última execução
        $this->saveLastRun();

        // Recupera os produtos
        $this->getProducts();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

     /**
     * Recupera os produtos
     *
     * @return bool
     */
    public function getProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";            
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $arrDadosJobIntegration = $this->model_integrations->getJobForJobAndStore('UpdatePrice', $this->store);

        $dtLastRun = $arrDadosJobIntegration['last_run'];
        if ($dtLastRun) {
            $dtLastRun = date_format(new DateTime($dtLastRun), 'Y-m-d');
        }

        $supplier_id = $this->getIDuserSellerByStore($this->store);

        // começando a pegar os produtos para criar
        $this->getListProducts($dtLastRun, $supplier_id);
    }

    /**
     * Recupera a lista para atualização do produto/variação
     *
     * @return bool
     */
    public function getListProducts(?string $dtLastRun, int $supplier_id): bool
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $ult_prod = null;
        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";            
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        if ($supplier_id == null || $supplier_id == "") {
            echo "Id conta plugg to nao encontrado. \n";
            //$this->log_data('batch', $log_name, "Id conta plugg to nao encontrado.", "W");
            return false;
        }

        $haveProductList = true;
        $access_token = $this->getToken();

        while ($haveProductList) {

            $today = date("Y-m-d");
            // Começando a pegar os produtos para criar
            $url = "https://api.plugg.to/products?access_token=$access_token&limit=100&supplier_id=$supplier_id";
            if ($dtLastRun) {
                $url .= "&modified={$dtLastRun}to$today";
            }
            if (!empty($ult_prod)) {
                $url .= "&next=$ult_prod";
            }
            $dataProducts = json_decode(json_encode($this->sendREST($url)));
            //dd($dataProducts);
            if ($dataProducts->httpcode != 200) {
                echo "Erro para buscar a lista de produto\n$url\n";
                $haveProductList = false;
                continue;
            }

            $prods = json_decode($dataProducts->content);
            if ($prods->total <= 0) {
                echo "Lista de produtos vazia.\n$url\n".json_encode($dataProducts)."\n";
                $haveProductList = false;
                continue;
            }
            //echo $prods->total;
            $arrPriceUpdate = array();

            if ($dataProducts->httpcode != 200) {
                echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
                $haveProductList = false;
                continue;
            }

            $lastIdProduct = "";

            foreach ($prods->result as $product) {
                $prod = $product->Product;
                $skuProduct = $prod->sku;
                $precoProd = $prod->special_price ? $prod->special_price : $prod->price;
                $idProduct = $prod->id;
                $lastIdProduct = $idProduct;

                $this->setUniqueId($idProduct); // define novo unique_id

                // Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($idProduct);


                // Não encontrou o produto pelo código da PluggTo
                if (empty($verifyProduct)) {
                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct));
                }

                // existe o sku na loja, mas não esá com o registro do id da PluggTo
                if (!empty($verifyProduct)) {

                    if ($verifyProduct['status'] != 1) {
                        echo "Produto não está ativo\n";
                        continue;
                    }

                    // produto com o mesmo preço, não será atualizado
                    //echo "atualizou código PluggTo para o produto sku={$skuProduct}, código PluggTo={$idProduct}...\n";

                    if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                        if ($precoProd > $arrPriceUpdate[$skuProduct]['price']) {
                            $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                        }
                    } else {
                        $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                    }

                } else {  // encontrou o produto pelo código da PluggTo, atualizar

                    if ($verifyProduct['status'] != 1) {
                        echo "Produto não está ativo\n";
                        continue;
                    }

                    // if ($skuProduct != $verifyProduct['sku']) {
                    //     echo "Produto encontrado pelo código PluggTo, mas com o sku diferente do sku cadastrado ID_PluggTo={$idProduct}, SKU={$skuProduct} \n";
                    //     $this->log_data('batch', $log_name, "Produto encontrado pelo código PluggTo, mas com o sku diferente do sku cadastrado ID_PluggTo={$idProduct}, SKU={$skuProduct}", "W");
                    //     $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Produto recebido e encontrado pelo código PluggTo, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$skuProduct}<br><strong>SKU Na Conecta Lá</strong>: {$verifyProduct['sku']}<br><strong>ID PluggTo<strong>: {$idProduct}", "E");
                    //     continue;
                    // }

                    // Adiciona preço para atualizar o preço do produto pai
                    if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                        if ($precoProd > $arrPriceUpdate[$skuProduct]['price']) {
                            $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                        }
                    } else {
                        $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                    }


                    //verifica se produto tem variação
                    if (isset($prod->variations) && (count($prod->variations) > 0)) {
                        foreach ($prod->variations as $prodvar) {
                            $verifyProduct = $this->product->getVariationForSku($prodvar->sku);
                            if (!empty($verifyProduct)) {
                                // Adiciona preço para atualizar o preço do produto filho

                                $preco_produto = $prodvar->price;
                                if (isset($prodvar->special_price) && ($prodvar->special_price > 0)) {
                                    $preco_produto = $prodvar->special_price;
                                }

                                $skuProduct = $prodvar->sku;
                                $precoProd = $preco_produto;
                                $idProduct = $prodvar->id;

                                if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                                    if ($precoProd > $arrPriceUpdate[$skuProduct]['price']) {
                                        $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                                    }
                                } else {
                                    $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                                }
                            }
                        }
                    }
                }
            }

            // fim lista
            // Atualiza produtos
            // É feita por fora, pois precisa comparar com o valores
            // das variações e recuperar o maior valor. Não temos
            // preços diferente para variações
            // $this->db->trans_begin();
            foreach ($arrPriceUpdate as $sku => $product) {
                $price = number_format($product['price'], 2, ".", "");
                $idProduct = $product['id'];

                $verifyProduct = $this->product->getProductForSku($sku);
                if (!$verifyProduct) {
                    //se não for produto pai, procura por variações
                    $verifyProduct = $this->product->getVariationForSku($sku);
                }

                // produto com o mesmo preço, não será atualizado
                if ($price == 0) {
                    echo "produto {$sku} com preço de venda zerado, preço={$price}\n";
                    $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>Valor de venda igual a R$0,00 é preciso informar um valor maior que zero.</li></ul> <strong>SKU</strong>: {$sku}<br><strong>ID PluggTo</strong>: {$idProduct}", "W");
                    continue;
                }

                // produto com o mesmo preço, não será atualizado
                if ($verifyProduct['price'] == $price) {
                    continue;
                }

                $productUpdate = $this->product->updatePrice($sku, $price);

                if (!$productUpdate) {
                    echo "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro\n";
                    $this->log_data('batch', $log_name, "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro, dados_item_lista=" . json_encode($product), "W");
                    $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>SKU: {$sku}</li><li>ID PluggTo: {$idProduct}</li></ul>", "E");
                    continue;
                }

                $this->log_data('batch', $log_name, "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price} ID={$idProduct}" . json_encode($product), "I");
                echo "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price}\n";
                $this->log_integration("Preço do produto {$sku} atualizado", "<h4>O preço do produto {$sku} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$price}", "S");
            }

            if ($lastIdProduct) {
                $ult_prod = $lastIdProduct;
            }
        }

        /*if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
        }*/

        // $this->db->trans_commit();
        // $this->getListProducts($dtLastRun, $supplier_id, $lastIdProduct);
        return true;
    }


}