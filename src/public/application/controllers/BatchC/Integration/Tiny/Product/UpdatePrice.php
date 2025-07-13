<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Tiny/Product/UpdatePrice run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

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

    /**
     * Recupera a lista para atualização do produto/variação
     *
     * @return bool
     */
    public function getListProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $url = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
        $data = $this->listPrice ? "&idListaPreco={$this->listPrice}" : '';
        $dataProducts = json_decode($this->sendREST($url, $data));

        if ($dataProducts->retorno->status != "OK") {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->retorno->codigo_erro != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                $this->log_integration("Erro para atualizar o preço", "<h4>Não foi possível consultar a listagem de produtos!</h4>", "E");
            }
            return false;
        }

        $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;

        $pages = $dataProducts->retorno->numero_paginas;

        $arrPrices = array();


        $arrPriceUpdate = array();
        for ($page = 1; $page <= $pages; $page++) {
            // Consultar nova página
            if ($page != 1) {
                $url = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
                $data = $this->listPrice ? "&idListaPreco={$this->listPrice}&pagina={$page}" : "&pagina={$page}";
                $dataProducts = json_decode($this->sendREST($url, $data));

                if ($dataProducts->retorno->status != "OK") {
                    echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    if ($dataProducts->retorno->codigo_erro != 99) {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                        $this->log_integration("Erro para atualizar o preço do produto", "<h4>Não foi possível consultar a listagem de produtos!</h4>", "E");
                    }
                    continue;
                }

                $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;
            }

            foreach ($regListProducts as $registro) {

                $id_produto = $this->listPrice ? $registro->registro->id_produto : $registro->produto->id;
                $precoProd  = $this->listPrice ? $registro->registro->preco : $registro->produto->preco;
                $this->setUniqueId($id_produto); // define novo unique_id
                
                echo "Ver se precisa atualizar o produto: {$id_produto}...\n";

                // Verifica se o ID é um produto, para não fazer requisição na tiny
                $idProduct = $id_produto;
                $verifyProduct = $this->product->getProductForIdErp($idProduct);
                $tipoVariacao = "N";
                $skuProduct = false;

                // Não encontrou o produto pelo código da tiny
                if (empty($verifyProduct)) {

                    $url = "https://api.tiny.com.br/api2/produto.obter.php";
                    $data = "&id={$id_produto}";
                    $dataProduct = json_decode($this->sendREST($url, $data));

                    if ($dataProduct->retorno->status != "OK") {
                        echo "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct) . "\n";
                        if ($dataProduct->retorno->codigo_erro != 99) {
                            $this->log_data('batch', $log_name, "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
//                            $this->log_integration("Erro para atualizar o preço do produto - ID Tiny {$id_produto}", "<h4>Não foi possível obter informações do produto!</h4> <strong>ID</strong>: {$id_produto}", "E");
                        }
                        continue;
                    }

                    $product = $dataProduct->retorno->produto; // Dados produto/variação

                    $tipoVariacao = $product->tipoVariacao;
                    $skuProduct = $product->codigo;
                    $idVar = $product->id;
                    $idProduct = $tipoVariacao == "V" ? $product->idProdutoPai : $product->id;

                    if ($tipoVariacao == "V") {
                        echo "É variação...\n";
                        // Consulta o produto pai para pegar o sku
                        $url = "https://api.tiny.com.br/api2/produto.obter.php";
                        $data = "&id={$idProduct}";
                        $dataProduct = json_decode($this->sendREST($url, $data));

                        if ($dataProduct->retorno->status != "OK") {
                            echo "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct) . "\n";
                            if ($dataProduct->retorno->codigo_erro != 99) {
                                $this->log_data('batch', $log_name, "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                                $this->log_integration("Erro para atualizar o preço  do produto - ID Tiny {$idProduct}", "<h4>Não foi possível obter informações do produto PAI de uma variação!</h4> <strong>ID</strong>: {$idProduct}", "E");
                            }
                            continue;
                        }

//                    $precoProd  = $dataProduct->retorno->produto->preco;
                        $skuProduct = $dataProduct->retorno->produto->codigo;
                    }

//                  if ($this->listPrice) {
//                      echo "Usa lista, vou pegar o preço da lista...\n";
//                      $getPrice = $this->product->getPriceVariationListPrice($idVar);
//                      // Ocorreu um problema para recuperar o preço da variação
//                      if (!$getPrice['success']) {
//                          echo "Erro na consulta do preço da variação";
//                          continue;
//                      }
//
//                      $precoProd = $getPrice['value'];
//                  }
                }

                if (array_key_exists($idProduct, $arrPrices)) {
                    if ($arrPrices[$idProduct] < $precoProd)
                        $arrPrices[$idProduct] = $precoProd;
                    else
                        $precoProd = $arrPrices[$idProduct];
                } else
                    $arrPrices[$idProduct] = $precoProd;

                // Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($idProduct);

                // Não encontrou o produto pelo código da tiny
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da tiny
                    if (!empty($verifyProduct)) {

//                        if ($verifyProduct['status'] != 1) {
//                            echo "Produto não está ativo\n";
//                            continue;
//                        }

                        // produto com o mesmo preço, não será atualizado
//                        if ($verifyProduct['price'] == $precoProd) {
//                            echo "produto com o mesmo preço, não será atualizado, sku={$skuProduct}, recebi={$precoProd}\n";
//                            $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID Tiny
//                            echo "atualizou código tiny para o produto sku={$skuProduct}, código tiny={$idProduct}\n";
//                            continue;
//                        }
                        $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID Tiny
                        echo "atualizou código tiny para o produto sku={$skuProduct}, código tiny={$idProduct}...\n";

                        if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                            if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                                $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                        } else {
                            $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                        }
                    }
                    else { // produto ainda não cadastrado, mostrar erro
                        echo "Produto não encontrado, não será possível atualizar o preço do produto ID_TINY={$idProduct}, SKU={$skuProduct} \n";
                        //$this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o preço do produto ID_TINY={$idProduct}, SKU={$skuProduct}", "W");
                        //$this->log_integration("Alerta para atualizar o preço do produto {$skuProduct}", "<h4>Não foi possível localizar o produto para atualizar seu preço, em breve será cadastrado e poderá ser atualizado.</h4> <strong>SKU</strong>: {$skuProduct}<br><strong>ID</strong>: {$idProduct}", "W");
                        continue;
                    }

                } else {  // encontrou o produto pelo código da tiny, atualizar

//                    if ($verifyProduct['status'] != 1) {
//                        echo "Produto não está ativo\n";
//                        continue;
//                    }

                    if ($skuProduct !== false) {
                        if ($skuProduct != $verifyProduct['sku']) {
                            echo "Produto encontrado pelo código Tiny, mas com o sku diferente do sku cadastrado ID_TINY={$idProduct}, SKU={$skuProduct} \n";
                            $this->log_data('batch', $log_name, "Produto encontrado pelo código Tiny, mas com o sku diferente do sku cadastrado ID_TINY={$idProduct}, SKU={$skuProduct}", "W");
                            $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Produto recebido e encontrado pelo código Tiny, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$skuProduct}<br><strong>SKU Na Conecta Lá</strong>: {$verifyProduct['sku']}<br><strong>ID Tiny<strong>: {$idProduct}", "E");
                            continue;
                        }
                    }
                    elseif ($skuProduct === false) {
                        $skuProduct = $verifyProduct['sku'];
                    }

                    // Adiciona preço para atualizar o preço do produto pai
                    if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                        if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                            $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                    } else {
                        $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                    }

                }
            }
        } // fim lista

        // Atualiza produtos
        // É feita por fora, pois precisa comparar com o valores
        // das variações e recuperar o maior valor. Não temos
        // preços diferente para variações
        foreach ($arrPriceUpdate as $sku => $product) {

            $price      = $product['price'];
            $idProduct  = $product['id'];

            $verifyProduct = $this->product->getProductForSku($sku);

            // produto com o mesmo preço, não será atualizado
            if ($price == 0) {
                echo "produto {$sku} com preço de venda zerado, preço={$price}\n";
                $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>Valor de venda igual a R$0,00 é preciso informar um valor maior que zero.</li></ul> <strong>SKU</strong>: {$sku}<br><strong>ID Tiny</strong>: {$idProduct}", "W");
                continue;
            }

            // produto com o mesmo preço, não será atualizado
            if ($verifyProduct['price'] == $price) {
                echo "produto {$sku} com o mesmo preço, não será atualizado, preço={$price}\n";
                continue;
            }

            $productUpdate = $this->product->updatePrice($sku, $price);

            if (!$productUpdate) {
                echo "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro\n";
                $this->log_data('batch', $log_name, "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product), "W");
                $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>SKU: {$sku}</li><li>ID Tiny: {$idProduct}</li></ul>", "W");
                continue;
            }

            $this->log_data('batch', $log_name, "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price} ID={$idProduct}" . json_encode($product), "I");

            echo "atualizado com sucesso SKU={$sku}\n";
            $this->log_integration("Preço do produto {$sku} atualizado", "<h4>O preço do produto {$sku} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$price}", "S");

        }
    }
}