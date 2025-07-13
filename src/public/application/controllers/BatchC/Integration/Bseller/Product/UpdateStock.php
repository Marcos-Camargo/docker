<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Integration/Bseller/Product/UpdateStock run null 51
 *
 */
require APPPATH . "controllers/BatchC/Integration/Bseller/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bseller/Product/Product.php";

class UpdateStock extends Main
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
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Buscando produtos com estoque alterado\n\n";

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

    public function getProducts()
    {
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

    public function getListProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
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

        $url = $BSELLER_URL . 'api/itens/estoque/massivo?tipoInterface=' . $this->interface . '&maxRegistros=100';
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

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

        $batchNumber = (int) $regProducts->batchNumber;

        //----------------------------------------------------------------------
        //limpa a lista de produtos com estoque alterados
        //----------------------------------------------------------------------
        if ($batchNumber > 0) {
            $urlUpdate = $BSELLER_URL . 'api/itens/estoque/massivo/' . $batchNumber;
            $dataUpdate = '';
            $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));

            if ($dataProductUpdateResult->httpcode != 200) {
                echo "Não foi possível confirmar o recebimento de lote enviado, retorno = " . json_encode($dataProductUpdateResult) . "\n";
            }
        }
        $produtosComEstoqueAlteradoVariacao = array();
        foreach ($regProducts->content as $registro) {
            // Inicia transação
            $this->db->trans_begin();
            $hasVariation = false;
            $idProduct = $registro->codigoItem;
            $stock = 0;
            foreach ($registro->estoqueEstabelecimento as $estoqueEstabelecimento) {
                $stock = $stock + $estoqueEstabelecimento->quantidade;
            }

            //Buscar o Produto pelo itemProduto e recuperar o sku
            $url = $BSELLER_URL . 'api/itens/' . $idProduct . '?tipoInterface=' . $this->interface;
            $data = '';
            $dataProd = json_decode(json_encode($this->sendREST($url, $data)));

            if ($dataProd->httpcode != 200) {
                echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProd) . "\n";
                if ($dataProducts->httpcode != 99) {
                    $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProd), "W");
                }
                continue;
            }

            $regProd = json_decode($dataProd->content);

            $skuProduct = $regProd->codigoFornecedor;

            $this->setUniqueId($idProduct); // define novo unique_id            
            $verifyProduct = $this->product->getProductForIdErp($idProduct);


            if (!empty($verifyProduct)) {
                // verifica se sku já existe
                $verifyProduct = $this->product->getProductForSku($skuProduct);

                // existe o sku na loja, mas não esá com o registro do id da Bseller
                if (!empty($verifyProduct)) {
                    if ($verifyProduct['status'] != 1) {
                        echo "Produto não está ativo\n";
                        $this->db->trans_rollback();
                        continue;
                    }

                    //verifica se produto tem variação
                    if (isset($regProd->variacoes) && (count($regProd->variacoes) > 0)) {
                        $verifyProduct = $this->product->getVariationForSku($regProd->codigoFornecedor);
                        

                        if (!empty($verifyProduct)) {

                            $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
                            $BSELLER_URL = '';
                            if ($dataIntegrationStore) {
                                $credentials = json_decode($dataIntegrationStore['credentials']);
                                $BSELLER_URL = $credentials->url_bseller;
                            }
                            //------------------------------------------------
                            //consulta se existem filhos
                            //------------------------------------------------
                            $urlEsp = $BSELLER_URL . 'api/itens/' . $idProduct . '/filhos?tipoInterface=' . $this->interface;
                            $dataEsp = '';
                            $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

                            if ($dataProduct->httpcode == 200) {
                                $filhos = json_decode($dataProduct->content);

                                foreach ($filhos as $filho) {

                                    $verifyProduct = $this->product->getVariationForIdErp($filho->codigoTerceiro);
                                    if (empty($verifyProduct)) {

                                        //pegar o estoque de cada variação
                                        $codigoItemFilho = $filho->codigoTerceiro;
                                        $urlEsp = $BSELLER_URL . 'api/itens/' . $codigoItemFilho . '/estoque?tipoInterface=' . $this->interface;
                                        $dataEsp = '';
                                        $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));
                                        //-----------
                                        if ($dataProduct->httpcode == 200) {

                                            $itemFilho = json_decode($dataProduct->content);
                                            $estoqueItemFilho = $itemFilho->estoqueEstabelecimento[0]->quantidade;

                                            $skuProductVar = $filho->codigoFornecedor;
                                            $idProductVar = $filho->codigoTerceiro;
                                            $stockNewVar = $estoqueItemFilho ?? 0; //$prodvar->quantity ?? 0;

                                            $updateStock = $this->updateStock($idProduct, $skuProductVar, $stockNewVar, $skuProduct);

                                            if ($updateStock[0] === false) {
                                                echo "Erro para atualizar o estoque da variação SKU {$skuProductVar}\n";
                                                $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProductVar} ID={$idProductVar}, dados_item_lista=" . json_encode($dataProduct) . " retorno=" . json_encode($updateStock), "E");
                                                $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProductVar}", "<h4>Não foi possível atualizar o estoque do produto {$skuProductVar}</h4>", "E");
                                                continue;
                                            }
                                            if ($updateStock[0] === null) {
                                                $this->db->trans_rollback();
                                                continue;
                                            }

                                            //atualizou com sucesso
                                            if ($updateStock[0] === true) {
                                                if (!in_array($idProduct, $produtosComEstoqueAlteradoVariacao)) {
                                                    $produtosComEstoqueAlteradoVariacao[] = $skuProduct;
                                                }
                                                $this->log_integration("Estoque da variação {$skuProductVar} atualizado", "<h4>O estoque do produto {$skuProductVar} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                                                echo "Estoque do produto {$skuProductVar} atualizado com sucesso\n";
                                                $this->log_data('batch', $log_name, "Estoque do produto {$skuProductVar} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }


                    $stockNew = $stock;

                    $updateStock = $this->updateStock($idProduct, $skuProduct, $stock);

                    if ($updateStock[0] === false) {
                        echo "Erro para atualizar o estoque do produto SKU {$skuProduct}\n";
                        $this->db->trans_rollback();
                        $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($updateStock), "E");
                        $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>", "E");
                        continue;
                    }
                    if ($updateStock[0] === null) {
                        $this->db->trans_rollback();
                        continue;
                    }

                    //atualizou com sucesso
                    if ($updateStock[0] === true) {
                        $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                        echo "Estoque do produto={$skuProduct} atualizado com sucesso\n";
                        $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                        $this->db->trans_commit();
                        continue;
                    }
                } else {
                    echo "Produto não encontrado, não será possível atualizar o estoque do produto ID_Bseller={$idProduct}, SKU={$skuProduct} \n";
                    $this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o estoque do produto ID_Bseller={$idProduct}, SKU={$skuProduct}", "E");
                    $this->log_integration("Erro para atualizar o estoque do produto {$skuProduct}", "<h4>Não foi possível localizar o produto para atualizar seu estoque, em breve será cadastrado e poderá ser atualizado.</h4> <strong>SKU</strong>: {$skuProduct}<br><strong>ID_Bseller</strong>: {$idProduct}", "E");
                    $this->db->trans_rollback();
                    continue;
                }
            } else {
                //verifica se é uma variação de um produto
                if ($regProd->codigoItemPai != '') {
                    $produtoPai = $regProd->codigoItemPai;
                    
                    $urlEsp = $BSELLER_URL . "api/itens/$produtoPai?tipoInterface=" . $this->interface;
                    $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, '')));
                    $dataProd=json_decode($dataProd->content);
                    $produto = $this->model_products->getByProductIdErp($dataProd->codigoItem);
                    $id_produto = $produto['id'];
                    if ($dataProduct->httpcode != 200) {
                        echo "Produto Bseller com ID={$produtoPai} encontrou um erro, retorno=" . json_encode($dataProduct) . "\n";
                        $this->db->trans_rollback();
                        if ($dataProduct->httpcode != 99) {
                            $this->log_data('batch', $log_name, "Produto Bseller com ID={$produtoPai} encontrou um erro, retorno=" . json_encode($dataProduct), "W");
                            $this->log_integration("Erro para integrar produto - ID Bseller {$produtoPai}", "Não foi possível obter informações do produto! <br> <strong>ID Bseller</strong>:{$produtoPai}", "E");
                        }
                        continue;
                    }

                    $produtoCompleto = json_decode($dataProduct->content);

                    // Recupera o código do produto pai
                    $skuProduct = $produtoCompleto->codigoFornecedor;

                    //------------------------------------------------
                    //consulta se existem filhos
                    //------------------------------------------------
                    $urlEsp = $BSELLER_URL . 'api/itens/' . $produtoPai . '/filhos?tipoInterface=' . $this->interface;
                    $dataEsp = '';
                    $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

                    if ($dataProduct->httpcode == 200) {
                        $filhos = json_decode($dataProduct->content);

                        foreach ($filhos as $filho) {
                            if ($regProd->codigoItem == $filho->codigoItem) {

                                $verifyProduct = $this->product->getVariationForIdErp($filho->codigoTerceiro);
                                if (!empty($verifyProduct)) {

                                    //pegar o estoque de cada variação
                                    $codigoItemFilho = $filho->codigoTerceiro;
                                    $urlEsp = $BSELLER_URL . 'api/itens/' . $codigoItemFilho . '/estoque?tipoInterface=' . $this->interface;
                                    $dataEsp = '';
                                    $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));
                                    //-----------
                                    if ($dataProduct->httpcode == 200) {

                                        $itemFilho = json_decode($dataProduct->content);
                                        $estoqueItemFilho = $itemFilho->estoqueEstabelecimento[0]->quantidade;

                                        $skuProductVar = $filho->codigoFornecedor;
                                        $idProductVar = $filho->codigoTerceiro;
                                        $stockNewVar = $estoqueItemFilho ?? 0; //$prodvar->quantity ?? 0;



                                        $updateStock = $this->updateStock($idProduct, $skuProductVar, $stockNewVar, $skuProduct);

                                        if ($updateStock[0] === false) {
                                            echo "Erro para atualizar o estoque da variação SKU {$skuProductVar}\n";
                                            $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProductVar} ID={$idProductVar}, dados_item_lista=" . json_encode($dataProduct) . " retorno=" . json_encode($updateStock), "E");
                                            $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProductVar}", "<h4>Não foi possível atualizar o estoque do produto {$skuProductVar}</h4>", "E");
                                            continue;
                                        }
                                        if ($updateStock[0] === null) {
                                            $this->db->trans_rollback();
                                            continue;
                                        }

                                        //atualizou com sucesso
                                        if ($updateStock[0] === true) {
                                            if (!in_array($idProduct, $produtosComEstoqueAlteradoVariacao)) {
                                                $produtosComEstoqueAlteradoVariacao[] = $skuProduct;
                                            }
                                            $this->log_integration("Estoque da variação {$skuProductVar} atualizado", "<h4>O estoque do produto {$skuProductVar} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                                            echo "Estoque da variação {$skuProductVar} atualizado com sucesso\n";
                                            $this->log_data('batch', $log_name, "Estoque do produto {$skuProductVar} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                echo "ocorreu um erro\n";
            }

            $this->db->trans_commit();
        }
    }

    /**
     * Atualiza estoque de um produto ou variação, caso esteja diferente
     * @param   string  $skuProduct     SKU do produto (Normal ou Pai)
     * @param   int     $idProduct      ID do produto Bseller
     * @return  array                   Retorna o estado da atualização | null=Está com o mesmo estoque, true=atualizou, false=deu problema
     */
    public function updateStock($idProductPai, $skuProduct, $stockNew, $skuPai = null)
    {

        if (!empty($skuPai)) {

            $stock = $stockNew;
            $stockReal = $this->product->getStockVariationForSku($skuProduct, $skuPai) ?? 0;

            if ($stock == (int) $stockReal)
                return array(null);
            $prdPai = $this->product->getProductForSku($skuPai);

            if ($stockNew <= 0)
                return array(false);

            return array($this->product->updateStockVariation($skuProduct, $skuPai, $stock), $stockReal, $stock);
        }

        $stock = $stockNew;
        $stockReal = $this->product->getStockForSku($skuProduct) ?? 0;
        if ($stock == (int) $stockReal)
            return array(null);
        return array($this->product->updateStockProduct($skuProduct, $stock, $idProductPai), $stockReal, $stock);
    }
}
