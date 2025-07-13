<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration/Bseller/Product/UpdatePrice run null 51
 *
 */
require APPPATH . "controllers/BatchC/Integration/Bseller/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bseller/Product/Product.php";

class UpdatePrice extends Main {

    private $product;

    public function __construct() {
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

    public function run($id = null, $store = null) {
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
        echo "Pegando produtos para atualizar o preço \n";

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
     * Recupera a lista para atualização do produto/variação
     *
     * @return bool
     */
    public function getListProducts() {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
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

        $url = $BSELLER_URL . 'api/itens/precos?tipoInterface='.$this->interface.'&maxRegistros=100';
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->httpcode != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
            }
            return false;
        }

        $regProducts = json_decode($dataProducts->content);

        $haveProductList = true;
        $arrPriceUpdate = array();
        $page = 1;
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;
        $batchNumber = (int) $regProducts->batchNumber;


        if ($dataProducts->httpcode != 200) {
            if ($dataProducts->httpcode == 999 && $countlimiteRequestBlock <= $limiteRequestBlock) {
                echo "aguardo 1 minuto para testar novamente. (Tentativas: {$countlimiteRequestBlock}/{$limiteRequestBlock})\n";
                sleep(60);
                $countlimiteRequestBlock++;
            }

            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
            $haveProductList = false;
        }
        $itens = $regProducts->content;

        if (is_array($itens) > 0) {
            foreach ($regProducts->content as $registro) {

                $idProduct = $registro->codigoItem;
                //Buscar o Produto pelo itemProduto e recuperar o sku
                $url = $BSELLER_URL . 'api/itens/' . $idProduct . '?tipoInterface='.$this->interface;

                if (empty($registro->preco->precoPor)) {
                    $precoProd = $registro->preco->precoDe;
                } else {
                    $precoProd = $registro->preco->precoPor;
                }
                $list_priceProd = $registro->preco->precoDe;

                $data = '';
                $dataProd = json_decode(json_encode($this->sendREST($url, $data)));

                if ($dataProd->httpcode != 200) {
                    $listaPreco = json_decode($dataProd->content);
                    echo $listaPreco->message . "\n";
                    if ($dataProducts->httpcode != 99) {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProd), "W");
                    }
                    continue;
                }

                $regProd = json_decode($dataProd->content);
                $skuProduct = $regProd->codigoFornecedor;

                $this->setUniqueId($idProduct); // define novo unique_id
                // Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($idProduct);

                // Não encontrou o produto pelo código da Bseller
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da Bseller
                    if (!empty($verifyProduct)) {

                        if ($verifyProduct['status'] != 1) {
                            echo "Produto não está ativo\n";
                            continue;
                        }
                        // produto com o mesmo preço, não será atualizado
                        echo "atualizou código Bseller para o produto sku={$skuProduct}, código Bseller={$idProduct}...\n";

                        if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                            if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                                $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                            if ($list_priceProd > $arrPriceUpdate[$skuProduct]['list_price'])
                                $arrPriceUpdate[$skuProduct]['list_price'] = $list_priceProd;
                        } else {
                            $arrPriceUpdate[$skuProduct] = array('price' => $precoProd,'list_price' => $list_priceProd, 'id' => $idProduct);
                        }
                    } else {
                        //verifica se o sku é uma variação
                        $verifyProductVar = $this->product->getVariationForSku($skuProduct);
                        if (!empty($verifyProductVar)) {
                            // Adiciona preço para atualizar
                            if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                                if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                                    $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                                if ($list_priceProd > $arrPriceUpdate[$skuProduct]['list_price'])
                                    $arrPriceUpdate[$skuProduct]['list_price'] = $list_priceProd;
                                continue;
                            } else {
                                $arrPriceUpdate[$skuProduct] = array('price' => $precoProd,'list_price' => $list_priceProd, 'id' => $idProduct);
                                continue;
                            }
                        } else {
                            // produto ainda não cadastrado
                            //echo "Produto não encontrado, não será possível atualizar o preço do produto ID_Bseller={$idProduct}, SKU={$skuProduct} \n";
                            continue;
                        }
                    }
                } else {  // encontrou o produto pelo código da Bseller, atualizar
                    if ($verifyProduct['status'] != 1) {
                        echo "Produto não está ativo\n";
                        continue;
                    }

                    if ($skuProduct != $verifyProduct['sku']) {
                        echo "Produto encontrado pelo código Bseller, mas com o sku diferente do sku cadastrado ID_Bseller={$idProduct}, SKU={$skuProduct} \n";
                        $this->log_data('batch', $log_name, "Produto encontrado pelo código Bseller, mas com o sku diferente do sku cadastrado ID_Bseller={$idProduct}, SKU={$skuProduct}", "W");
                        $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Produto recebido e encontrado pelo código Bseller, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$skuProduct}<br><strong>SKU Na Conecta Lá</strong>: {$verifyProduct['sku']}<br><strong>ID Bseller<strong>: {$idProduct}", "E");
                        continue;
                    }

                    // Adiciona preço para atualizar o preço do produto pai
                    if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                        if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                            $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                        if ($list_priceProd > $arrPriceUpdate[$skuProduct]['list_price'])
                            $arrPriceUpdate[$skuProduct]['list_price'] = $list_priceProd;
                    } else {
                        $arrPriceUpdate[$skuProduct] = array('price' => $precoProd,'list_price' => $list_priceProd, 'id' => $idProduct);
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

                            // Adiciona preço para atualizar o preço do produto filho
                            $urlEsp = $BSELLER_URL . 'api/itens/' . $idProduct . '/filhos?tipoInterface='.$this->interface;
                            $dataEsp = '';
                            $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

                            if ($dataProduct->httpcode == 200) {

                                $filhos = json_decode($dataProduct->content);

                                foreach ($filhos as $filho) {

                                    $verifyProduct = $this->product->getVariationForIdErp($filho->codigoTerceiro);
                                    $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);

                                    $BSELLER_URL = '';
                                    if ($dataIntegrationStore) {
                                        $credentials = json_decode($dataIntegrationStore['credentials']);
                                        $BSELLER_URL = $credentials->url_bseller;
                                    }


                                    // if (empty($verifyProduct)) {
                                    //para cada filho pegar o preço
                                    $url = $BSELLER_URL . 'api/itens/' . $filho->codigoTerceiro . '?tipoInterface='.$this->interface;
                                    $data = '';
                                    $dataProdFilho = json_decode(json_encode($this->sendREST($url, $data)));

                                    if ($dataProdFilho->httpcode == 200) {
                                        $prodFilho = json_decode($dataProdFilho->content);

                                        if (empty($prodFilho->preco[0]->precoPor)) {
                                            $precoProdNew = $prodFilho->preco[0]->precoDe;
                                        } else {
                                            $precoProdNew = $prodFilho->preco[0]->precoPor;
                                        }
                                        $list_precoProdNew = $prodFilho->preco[0]->precoDe;

                                        $skuProduct = $prodFilho->codigoFornecedor;
                                        $idProduct = $prodFilho->codigoTerceiro;

                                        if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                                            if ($precoProdNew > $arrPriceUpdate[$skuProduct]['price'])
                                                $arrPriceUpdate[$skuProduct]['price'] = $precoProdNew;
                                            if ($list_precoProdNew > $arrPriceUpdate[$skuProduct]['list_price'])
                                                $arrPriceUpdate[$skuProduct]['list_price'] = $list_precoProdNew;
                                        } else {
                                            $arrPriceUpdate[$skuProduct] = array('price' => $precoProdNew,'list_price' => $list_precoProdNew, 'id' => $idProduct);
                                        }
                                    }
                                    // }
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
            $this->db->trans_begin();
            foreach ($arrPriceUpdate as $sku => $product) {
                $price = number_format($product['price'], 2, ".", "");
                $list_price = number_format($product['list_price'], 2, ".", "");
                $idProduct = $product['id'];
                $verifyProduct = $this->product->getProductForSku($sku);
                if (!$verifyProduct) {
                    //se não for produto pai, procura por variações
                    $verifyProduct = $this->product->getVariationForSku($sku);
                }

                // produto com o mesmo preço, não será atualizado
                if ($price == 0) {
                    echo "produto {$sku} com preço de venda zerado, preço={$price}\n";
                    $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>Valor de venda igual a R$0,00 é preciso informar um valor maior que zero.</li></ul> <strong>SKU</strong>: {$sku}<br><strong>ID Bseller</strong>: {$idProduct}", "W");
                    continue;
                }

                // produto com o mesmo preço, não será atualizado
                if ($verifyProduct['price'] == $price && $verifyProduct['list_price'] == $list_price) {
                    continue;
                }

                $productUpdate = $this->product->updatePrice($sku, $price,$list_price);
                // $variacao=$this->model_products->getVariantsForSku($verifyProduct["id"],'VAR'.$sku);
                $this->model_products->updateVarBySku(array('price'=>$price,'list_price'=>$list_price),$verifyProduct["id"],'VAR'.$sku);

                if (!$productUpdate) {
                    echo "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro\n";
                    $this->log_data('batch', $log_name, "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro, dados_item_lista=" . json_encode($product), "W");
                    $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>SKU: {$sku}</li><li>ID Bseller: {$idProduct}</li></ul>", "E");
                    continue;
                }
                $message_log ='';
                if ($verifyProduct['price'] <> $price) {
                    $message_log = "<br><strong>Preço Anterior</strong>:".$verifyProduct['price']." | <strong>Novo Preço</strong>:".$price;
                }
                if ($verifyProduct['list_price'] <> $list_price) {
                    $message_log .= "<br><strong>Preço de Tabela Anterior</strong>:".$verifyProduct['list_price']." | <strong>Novo Preço de Tabela</strong>:".$list_price;
                }
                
                $this->log_data('batch', $log_name, "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price} | preco_tabela_anterior={$verifyProduct['list_price']} novo_preco_tabela={$list_price} ID={$idProduct}" . json_encode($product), "I");
                echo "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price} | preco_tabela_anterior={$verifyProduct['list_price']} novo_preco_tabela={$list_price} \n";
                $this->log_integration("Preço do produto {$sku} atualizado", "<h4>O preço do produto {$sku} foi atualizado com sucesso</h4>{$message_log}", "S");
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                echo "ocorreu um erro\n";
            }

            $this->db->trans_commit();
        }

        //----------------------------------------------------------------------
        //limpa a lista de produtos com preço alterados
        //----------------------------------------------------------------------
        if($batchNumber > 0){
            $urlUpdate = $BSELLER_URL.'api/itens/precos/massivo/'.$batchNumber;
            $dataUpdate ='';
            $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));

            if ($dataProductUpdateResult->httpcode != 200) {
                echo "Não foi possível confirmar o recebimento de lote enviado, retorno = " . json_encode($dataProductUpdateResult) . "\n";
                $this->db->trans_rollback();
            } 
        }
    }

}
