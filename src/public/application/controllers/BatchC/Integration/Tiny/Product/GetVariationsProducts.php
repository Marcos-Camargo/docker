<?php

/**
 * Class GetVariationsProducts
 *
 * php index.php BatchC/Tiny/Product/GetVariationsProducts run null 20
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

class GetVariationsProducts extends Main
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

        $this->setJob('GetVariationsProducts');
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            echo "Parametros informados incorretamente. ID={$id} - STORE={$store}\n";
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando produtos... \n";

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
            return false;
        }

        // começando a pegar os produtos para criar
        $this->getListProducts();
    }

    /**
     * Recupera a lista para cadastro do produto/variação
     *
     * @return bool
     */
    public function getListProducts()
    {
        $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
        $data   = $this->listPrice ? "&idListaPreco={$this->listPrice}" : '';
        $dataProducts = json_decode($this->sendREST($url, $data));

        if ($dataProducts->retorno->status != "OK") {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            return false;
        }

        $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;

        $pages = $dataProducts->retorno->numero_paginas;

        $arrProducts = array();

        for ($page = 1; $page <= $pages; $page++) {
            if ($page != 1) {
                $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
                $data = $this->listPrice ? "&idListaPreco={$this->listPrice}&pagina={$page}" : "&pagina={$page}";
                $dataProducts = json_decode($this->sendREST($url, $data));

                if ($dataProducts->retorno->status != "OK") {
                    echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    continue;
                }

                $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;
            }

            foreach ($regListProducts as $registro) {

                $id_produto = $this->listPrice ? $registro->registro->id_produto : $registro->produto->id;
                $this->setUniqueId($id_produto); // define novo unique_id
                echo "Pegando {$id_produto}...\n";

                $url    = "https://api.tiny.com.br/api2/produto.obter.php";
                $data   = "&id={$id_produto}";
                $dataProduct = json_decode($this->sendREST($url, $data));

                if ($dataProduct->retorno->status != "OK") {
                    echo "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=".json_encode($registro)." retorno=" . json_encode($dataProduct) . "\n";
                    continue;
                }

                $product = $dataProduct->retorno->produto; // Dados produto/variação

                $tipoVariacao   = $product->tipoVariacao;
                $skuVar         = $product->codigo;

                /**
                 * UM PRODUTO QUE CONTEM VARIAÇÕES, SERÃO MOSTRADOS APENAS NO PAYLOAD
                 * DA LISTA DE PREÇOS AS SUAS VARIAÇÕES, O PRODUTO PAI NÃO SERÁ MOSTRADO,
                 * DEVERÁ FAZER UM GET NA VARIAÇÃO E OBTER O PRODUTO PAI PARA CADASTRO
                 */

                // Recupera o código do produto pai
                $idProduct = $tipoVariacao == "V" ? $product->idProdutoPai : $product->id;

                if($tipoVariacao == "V") {

                    if ($this->listPrice) {

                    } else {
                        if (array_key_exists($idProduct, $arrProducts)) {
                            array_push($arrProducts[$idProduct], array(
                                'sku'   => $skuVar,
                                'grade' => $product->grade
                            ));
                        } else {

                            // Pesquisa o produto pai
                            $url    = "https://api.tiny.com.br/api2/produto.obter.php";
                            $data   = "&id={$idProduct}";
                            $dataProductPai = json_decode($this->sendREST($url, $data));

                            if ($dataProductPai->retorno->status != "OK") {
                                echo "Produto tiny com ID={$idProduct} encontrou um erro, dados_item_lista=".json_encode($registro)." retorno=" . json_encode($dataProductPai) . "\n";
                                continue;
                            }

                            $productPai = $dataProductPai->retorno->produto; // Dados produto/variação
                            $skuProduct = $productPai->codigo;


                            $arrProducts[$idProduct]['skuPai'] = $skuProduct;
                            array_push($arrProducts[$idProduct], array(
                                'sku'   => $skuVar,
                                'grade' => $product->grade
                            ));
                        }
                    }

                }
            }
        }
        echo "\n\n\n";

        echo json_encode($arrProducts);
    }
}