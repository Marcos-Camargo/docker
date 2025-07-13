<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration/Bseller/Product/UpdateProducts run
 *
 */
require APPPATH . "controllers/BatchC/Integration/Bseller/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bseller/Product/Product.php";

class UpdateProduct extends Main {

    private $product;

    public function __construct() {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens
        $this->load->model('model_settings');

        $this->product = new Product($this);

        $this->setJob('UpdateProduct');
    }

    public function run($id = null, $store = null) {
        die('metodo descontinuado, favor usar o método de criar produtos que faz um update');
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Atualizando lista de produtos \n";

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
    public function getProducts() {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        // começando a pegar os produtos para criar
        $this->getListProducts();
    }

    /**
     * Recupera a lista para cadastro do produto
     *
     * @return bool
     */
    public function getListProducts() {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            $this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $BSELLER_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $BSELLER_URL = $credentials->url_bseller;
        }
        /* faz o que o job precisa fazer */

        $url = $BSELLER_URL . 'api/itens/massivo?tipoInterface='.$this->interface.'&maxRegistros=100';
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        $arrayProductErroCheck = array();
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;

        if ($dataProducts->httpcode == 404) {
            echo "Sistema atualizado";
            return false;
        }

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->httpcode != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
            }
            return false;
        }

        $regProducts = json_decode($dataProducts->content);

        if ($dataProducts->httpcode != 200) {
            if ($dataProducts->httpcode == 999 && $countlimiteRequestBlock <= $limiteRequestBlock) {
                echo "aguardo 1 minuto para testar novamente. (Tentativas: {$countlimiteRequestBlock}/{$limiteRequestBlock})\n";
                sleep(60);
                $countlimiteRequestBlock++;
            }

            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
        }

        if ($regProducts->totalElements <= 0) {
            echo "Lista de produtos vazia\n";
            $this->log_data('batch', $log_name, "Lista de produtos vazia, retorno = " . json_encode($dataProducts), "W");
            return false;
        }

        $arrayProductErroCheck = array();



        foreach ($regProducts->content as $product) {

            // Inicia transação
            $this->db->trans_begin();
            $hasVariation = false;
            $id_produto = $product->codigoTerceiro;
            $this->setUniqueId($id_produto); // define novo unique_id

            $idProduct = $product->codigoTerceiro;
            $skuProduct = $product->codigoFornecedor;

            if (in_array($idProduct, $arrayProductErroCheck)) {
                echo "Já tentou atualizar o ID={$idProduct} e deu erro\n";
                $this->db->trans_rollback();
                continue;
            }

            $verifyProduct = $this->product->getProductForSku($skuProduct);

            // existe o sku na loja, mas não esá com o registro do id da bseller
            if (!empty($verifyProduct)) {
                if ($verifyProduct['status'] != 1) {
                    echo "Produto não está ativo\n";
                    $this->db->trans_rollback();
                    continue;
                }


                $dirImage = $verifyProduct['image'];

                //atualiza imagens
                if ($this->uploadproducts->countImagesDir($dirImage) == 0) {
                    // upload de imagens, so é permitido 6 imagens;
                    $imgArray = array();
                    $qtdImage = 0;
                    foreach ($product->imagens as $img) {
                        if ($qtdImage >= $limite_imagens_aceitas_api) {
                            continue;
                        }
                        $linkImg = $img->link;
                        $imgArray[] = $linkImg;
                        $qtdImage++;
                    }
                    if (count($imgArray) > 0) {
                        $product->anexos = $imgArray;
                    }
                }

                if (isset($product->variacoes) && (count($product->variacoes) > 0)) {

                    $hasVariation = true;

                    $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
                    $BSELLER_URL = '';
                    if ($dataIntegrationStore) {
                        $credentials = json_decode($dataIntegrationStore['credentials']);
                        $BSELLER_URL = $credentials->url_bseller;
                    }

                    $urlEsp = $BSELLER_URL . 'api/itens/' . $idProduct . '/filhos?tipoInterface='.$this->interface;
                    $dataEsp = '';
                    $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

                    if ($dataProduct->httpcode == 200) {
                        $filhos = json_decode($dataProduct->content);

                        foreach ($filhos as $filho) {
                            //pegar os dados completo do produto
                            $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
                            $BSELLER_URL = '';
                            if ($dataIntegrationStore) {
                                $credentials = json_decode($dataIntegrationStore['credentials']);
                                $BSELLER_URL = $credentials->url_bseller;
                            }

                            $codigoItemFilho = $filho->codigoTerceiro;

                            // pegar o estoque de cada filho
                            $urlEsp = $BSELLER_URL . 'api/itens/' . $codigoItemFilho . '/estoque?tipoInterface='.$this->interface;
                            $data = '';
                            $dataProdEstoqueFilho = json_decode(json_encode($this->sendREST($urlEsp, $data)));

                            if ($dataProdEstoqueFilho->httpcode == 200) {

                                $estoqueFilho = json_decode($dataProdEstoqueFilho->content);
                                $stock = $estoqueFilho->estoqueEstabelecimento[0]->quantidade;

                                //Pegar o preço de cada produto filho
                                $url = $BSELLER_URL . 'api/itens/' . $codigoItemFilho . 'tipoInterface='.$this->interface;
                                $data = '';
                                $dataProd = json_decode(json_encode($this->sendREST($url, $data)));

                                $regProd = json_decode($dataProd->content);

                                if ($dataProd->httpcode == 200) {

                                    if (empty($regProd->preco[0]->precoPor)) {
                                        $precoProd = $regProd->preco[0]->precoDe;
                                    } else {
                                        $precoProd = $regProd->preco[0]->precoPor;
                                    }
                                    $list_price =$regProd->preco[0]->precoDe;                                    

                                    $productUpdate = $this->product->updateVariation($filho, $id_produto, $precoProd, $stock, $list_price);


                                    if ($productUpdate['success'] === false) {
                                        echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($productUpdate) . "\n";
                                        $this->db->trans_rollback();
                                        $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID bseller
                                        // adiciono no array para não consultar mais esse produto pai
                                        if (!in_array($idProduct, $arrayProductErroCheck)) {
                                            array_push($arrayProductErroCheck, $idProduct);
                                        }

                                        continue;
                                    }

                                    if ($productUpdate['success'] === null) {
                                        continue;
                                    }


                                    if ($productUpdate['success'] === true) {
                                        $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi atualizado com sucesso</li></ul><br><strong>ID Bseller</strong>: {$product->codigoItem}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Nome do produto</strong>: {$product->nome}", "S");
                                        $this->log_data('batch', $log_name, "Produto atualizado!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), "I");
                                        echo "Produto SKU={$skuProduct} atualizado com sucesso\n";
                                    }
                                }
                            }
                        }
                    }
                }


                $productUpdate = $this->product->updateProduct($product);


                if ($productUpdate['success'] === false) {
                    echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($productUpdate) . "\n";
                    $this->db->trans_rollback();
                    $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID bseller
                    // adiciono no array para não consultar mais esse produto pai
                    if (!in_array($idProduct, $arrayProductErroCheck)) {
                        array_push($arrayProductErroCheck, $idProduct);
                    }

                    continue;
                }
                if ($productUpdate['success'] === null) {
                    continue;
                }

                if ($productUpdate['success'] === true) {
                    $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi atualizado com sucesso</li></ul><br><strong>ID Bseller</strong>: {$product->codigoItem}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Nome do produto</strong>: {$product->nome}", "S");
                    $this->log_data('batch', $log_name, "Produto atualizado!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), "I");
                    echo "Produto SKU={$skuProduct} atualizado com sucesso\n";
                }
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                echo "ocorreu um erro\n";
            }

            $this->db->trans_commit();
        }
    }

}
