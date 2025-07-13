<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Tiny/Product/UpdateStock run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

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
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

		echo "Essa rotina foi inativada\n";
		die; 
		
        /* faz o que o job precisa fazer */
        echo "Pegando produtos para cadastrar \n";

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
     * Recupera a lista para cadastro do produto
     *
     * @return bool
     */
    public function getListProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
        $data   = $this->listPrice ? "&idListaPreco={$this->listPrice}" : '';
        $dataProducts = json_decode($this->sendREST($url, $data));

        if ($dataProducts->retorno->status != "OK") {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->retorno->codigo_erro != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                $this->log_integration("Erro para atualizar o estoque do produto", "Não foi possível consultar a listagem de produtos!", "E");
            }
            return false;
        }

        $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;

        $pages = $dataProducts->retorno->numero_paginas;

        for ($page = 1; $page <= $pages; $page++) {
            if ($page != 1) {
                $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
                $data = $this->listPrice ? "&idListaPreco={$this->listPrice}&pagina={$page}" : "&pagina={$page}";
                $dataProducts = json_decode($this->sendREST($url, $data));

                if ($dataProducts->retorno->status != "OK") {
                    echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    if ($dataProducts->retorno->codigo_erro != 99) {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                        $this->log_integration("Erro para atualizar o estoque do produto", "Não foi possível consultar a listagem de produtos!", "E");
                    }
                    continue;
                }

                $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;
            }

            foreach ($regListProducts as $registro) {
                // Inicia transação
                //$this->db->trans_begin();

                $id_produto = $this->listPrice ? $registro->registro->id_produto : $registro->produto->id;
                $this->setUniqueId($id_produto); // define novo unique_id

                $url    = "https://api.tiny.com.br/api2/produto.obter.php";
                $data   = "&id={$id_produto}";
                $dataProduct = json_decode($this->sendREST($url, $data));

                if ($dataProduct->retorno->status != "OK") {
                    echo "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=".json_encode($registro)." retorno=" . json_encode($dataProduct) . "\n";
                    //$this->db->trans_rollback();
                    if ($dataProduct->retorno->codigo_erro != 99) {
                        $this->log_data('batch', $log_name, "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                        $this->log_integration("Erro para atualizar o estoque do produto ID Tiny {$id_produto}", "Não foi possível obter informações do produto! ID={$id_produto}", "E");
                    }
                    continue;
                }

                $product = $dataProduct->retorno->produto; // Dados produto/variação

                $tipoVariacao   = $product->tipoVariacao;
                $skuVar         = $product->codigo;
                $skuProduct     = $product->codigo;

                /**
                 * UM PRODUTO QUE CONTEM VARIAÇÕES, SERÃO MOSTRADOS APENAS NO PAYLOAD
                 * DA LISTA DE PREÇOS AS SUAS VARIAÇÕES, O PRODUTO PAI NÃO SERÁ MOSTRADO,
                 * DEVERÁ FAZER UM GET NA VARIAÇÃO E OBTER O PRODUTO PAI PARA CADASTRO
                 */

                // Recupera o código do produto pai
                $idProduct = $tipoVariacao == "V" ? $product->idProdutoPai : $product->id;
                $verifyProduct = $this->product->getProductForIdErp($idProduct);

                // se for variação busca o produto pai
                if($tipoVariacao == "V") {
                    // Consulta o produto pai
                    $url = "https://api.tiny.com.br/api2/produto.obter.php";
                    $data = "&id={$idProduct}";
                    $dataProduct = json_decode($this->sendREST($url, $data));

                    if ($dataProduct->retorno->status != "OK") {
                        echo "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct) . "\n";
                        //$this->db->trans_rollback();
                        if ($dataProduct->retorno->codigo_erro != 99) {
                            $this->log_data('batch', $log_name, "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                            $this->log_integration("Erro para atualizar o estoque do produto ID Tiny {$id_produto}", "Não foi possível obter informações do produto PAI de uma variação! ID={$idProduct}", "E");
                        }
                        continue;
                    }
                    $skuProduct = $dataProduct->retorno->produto->codigo;
                }

                // Não encontrou o produto pelo código da tiny
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da tiny
                    if (!empty($verifyProduct)) {

//                        if ($verifyProduct['status'] != 1) {
//                            echo "Produto não está ativo\n";
//                            $this->db->trans_rollback();
//                            continue;
//                        }

                        $updateStock = $this->updateStock($tipoVariacao, $skuProduct, $skuVar, $id_produto);
                        if ($updateStock[0] === false) {
                            echo "Alerta para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product) . " retorno=" . json_encode($updateStock) . "\n";
                            //$this->db->trans_rollback();
                            $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product) . " retorno=" . json_encode($updateStock), "E");
//                            $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>", "E");
                            continue;
                        }
                        if ($updateStock[0] === null) {
                            if($tipoVariacao == "V")
                                echo "Estoque da  variação={$skuVar} igual ao do banco, não será modificado\n";
                            else
                                echo "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";
                            //$this->db->trans_rollback();
                            continue;
                        }

                    } else { // produto ainda não cadastrado
                        echo "Produto não encontrado, não será possível atualizar o produto ID_TINY={$idProduct}, SKU={$skuProduct} \n";
                        //$this->db->trans_rollback();
                        //$this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o produto ID_TINY={$idProduct}, SKU={$skuProduct}", "W");
                        //$this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível localizar o produto para atualizar o estoque, em breve será cadastrado e poderá ser atualizado.</h4> <strong>SKU</strong>: {$skuProduct}<br><strong>ID Tiny</strong>: {$idProduct}", "W");
                        continue;
                    }

                } else {

//                    if ($verifyProduct['status'] != 1) {
//                        echo "Produto não está ativo\n";
//                        $this->db->trans_rollback();
//                        continue;
//                    }

                    $updateStock = $this->updateStock($tipoVariacao, $skuProduct, $skuVar, $id_produto);
                    if ($updateStock[0] === false) {
                        echo "Alerta para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product) . " retorno=" . json_encode($updateStock) . "\n";
                        //$this->db->trans_rollback();
                        $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product) . " retorno=" . json_encode($updateStock), "E");
//                        $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>" . json_encode($verifyProduct), "E");
                        continue;
                    }
                    if ($updateStock[0] === null) {
                        if($tipoVariacao == "V")
                            echo "Estoque da variação={$skuVar} igual ao do banco, não será modificado\n";
                        else
                            echo "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";
                        //$this->db->trans_rollback();
                        continue;
                    }
                }

                /*if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    echo "ocorreu um erro\n";
                    continue;
                }*/

                //$this->db->trans_commit();
                if($tipoVariacao == "V") {
                    echo "Estoque da variação={$skuVar} atualizada com sucesso\n";
                    $this->log_integration("Estoque da variação {$skuVar} atualizada", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                    $this->log_data('batch', $log_name, "Estoque da variação {$skuVar} atualizada. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                } else {
                    $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                    echo "Estoque do produto={$skuProduct} atualizado com sucesso\n";
                    $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                }
            }
        }
    }

    /**
     * Atualiza estoque de um produto ou variação, caso esteja diferente
     *
     * @param   string  $tipoVariacao   Tipo de variação, "N" ou "V"
     * @param   string  $skuProduct     SKU do produto (Normal ou Pai)
     * @param   string  $skuVariation   SKU da variação caso tenha
     * @param   int     $idProduct      ID do produto Tiny
     * @return  array                   Retorna o estado da atualização | null=Está com o mesmo estoque, true=atualizou, false=deu problema
     */
    public function updateStock($tipoVariacao, $skuProduct, $skuVariation, $idProduct)
    {
        $stock = $this->product->getStock([$idProduct]);
        if (!$stock['success']) return array(false);

        $stock = $stock['totalQty'];

        if ($tipoVariacao == "N") {
            // comparar estoque
            $stockReal = $this->product->getStockForSku($skuProduct);
            if($stockReal == $stock) return array(null);

            return array($this->product->updateStockProduct($skuProduct, $stock, $idProduct), $stockReal, $stock);
        }
        elseif ($tipoVariacao == "V") {
            // comparar estoque
            $stockReal = $this->product->getStockForSku($skuProduct, $skuVariation);
            if($stockReal == $stock) return array(null);

            return array($this->product->updateStockVariation($skuVariation, $skuProduct, $stock, $idProduct), $stockReal, $stock);
        }
        elseif ($tipoVariacao == "P") {
            // não deve ser atualizado, apenas pegar o estoque das variações
            return array(null);
        }

        return array(false);
    }
}