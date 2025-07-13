<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Tiny/Product/UpdateProducts run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

class UpdateProduct extends Main
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

        $this->setJob('UpdateProduct');
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
        echo "Pegando produtos para atuaizar \n";

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
                $this->log_integration("Erro para atualizar o produto", "Não foi possível consultar a listagem de produtos!", "E");
            }
            return false;
        }

        $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;

        $pages = $dataProducts->retorno->numero_paginas;
        $arrayProductErroCheck = array();

        for ($page = 1; $page <= $pages; $page++) {
            if ($page != 1) {
                $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
                $data = $this->listPrice ? "&idListaPreco={$this->listPrice}&pagina={$page}" : "&pagina={$page}";
                $dataProducts = json_decode($this->sendREST($url, $data));

                if ($dataProducts->retorno->status != "OK") {
                    echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    if ($dataProducts->retorno->codigo_erro != 99) {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                        $this->log_integration("Erro para atualizar o produto", "Não foi possível consultar a listagem de produtos!", "E");
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
                        $this->log_integration("Erro para atualizar o produto ID Tiny {$id_produto}", "Não foi possível obter informações do produto! ID={$id_produto}", "E");
                    }
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

                if (in_array($idProduct, $arrayProductErroCheck)) {
                    echo "Já tentou atualizar o ID={$idProduct} e deu erro\n";
                    //$this->db->trans_rollback();
                    continue;
                }

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
                            $this->log_integration("Erro para atualizar o produto ID Tiny {$id_produto}", "Não foi possível obter informações do produto PAI de uma variação! ID={$idProduct}", "E");
                        }
                        continue;
                    }
                }
                $skuProduct = $tipoVariacao == "V" ? $dataProduct->retorno->produto->codigo : $product->codigo;

                // Não encontrou o produto pelo código da tiny
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da tiny
                    if (!empty($verifyProduct)) {

//                        if ($verifyProduct['status'] != 1 && $verifyProduct['status'] != 4) {
//                            echo "Produto não está ativo\n";
//                            $this->db->trans_rollback();
//                            continue;
//                        }

                        $productUpdate = $this->product->updateProduct($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product);
                        if ($productUpdate['success'] === false) {
                            echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product) . " retorno=" . json_encode($productUpdate) . "\n";
                            //$this->db->trans_rollback();
                            $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID Tiny
                            //$this->log_data('batch', $log_name, "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                            //$this->log_integration("Erro para atualizar o produto SKU {$skuProduct}", 'Existem algumas pendências no cadastro do produto na plataforma de integração: <ul><li>' . implode('</li><li>', $productUpdate['message']) . '</li></ul>', "E");
                            // adiciono no array para não consultar mais esse produto pai
                            if (!in_array($idProduct, $arrayProductErroCheck))
                                array_push($arrayProductErroCheck, $idProduct);

                            continue;
                        }
                        if ($productUpdate['success'] === null) {
                            echo "Product está igual ao do cadastro, não será atualizado\n";
                            continue;
                        }

                    } else { // produto ainda não cadastrado, atualizar
                        echo "Produto não encontrado, não será possível atualizar o produto ID_TINY={$idProduct}, SKU={$skuProduct} \n";
                        //$this->db->trans_rollback();
                        //$this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o produto ID_TINY={$idProduct}, SKU={$skuProduct}", "E");
                        //$this->log_integration("Erro para atualizar o produto SKU {$skuProduct}", "Não foi possível localizar o produto para atualizar: <ul><li>SKU: {$skuProduct}</li><li>ID Tiny: {$idProduct}</li></ul>", "E");
                        continue;
                    }

                } else {

//                    if ($verifyProduct['status'] != 1 && $verifyProduct['status'] != 4) {
//                        echo "Produto não está ativo\n";
//                        $this->db->trans_rollback();
//                        continue;
//                    }

                    $productUpdate = $this->product->updateProduct($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product);
                    if ($productUpdate['success'] === false) {
                        echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product) . " retorno=" . json_encode($productUpdate) . "\n";
                        //$this->db->trans_rollback();
                        //$this->log_data('batch', $log_name, "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                        //$this->log_integration("Erro para atualizar o produto SKU {$skuProduct}", 'Existem algumas pendências no cadastro do produto na plataforma de integração: <ul><li>' . implode('</li><li>', $productUpdate['message']) . '</li></ul>', "E");
                        // adiciono no array para não consultar mais esse produto pai
                        if (!in_array($idProduct, $arrayProductErroCheck))
                            array_push($arrayProductErroCheck, $idProduct);

                        continue;
                    }
                    if ($productUpdate['success'] === null) {
                        echo "Product {$idProduct} está igual ao do cadastro, não será atualizado\n";
                        continue;
                    }
                }

                if($tipoVariacao == "V") {

                    // Encontrou e/ou alterou o produto, verifica se existe a variação
                    $verifyVar = $this->product->getVariationForSkuAndSkuVar($skuProduct, $skuVar);

                    // Variação existe, vou alterar
                    if ($verifyVar) {
                        echo "variação existe, vou atualizar ela...\n";
                        $updateVariation = $this->product->updateVariation($product, $skuProduct);

                        // Ocorreu problema na criação da variação
                        if (!$updateVariation['success']) {
                            echo "Ocorreu um problema na atualização da variação SKU_PAI={$skuProduct}. Foi enviado=" . json_encode($product) . " retorno={$updateVariation['message']}\n";
                            //$this->db->trans_rollback();
                            $this->log_data('batch', $log_name, "Ocorreu um problema na atualização da variação SKU_PAI={$skuProduct}. Foi enviado=" . json_encode($product) . " retorno={$updateVariation['message']}", "E");
                            $this->log_integration("Erro para atualizar o produto SKU {$skuProduct}", "Não foi possível atualizar a variação! <ul><li>{$updateVariation['message']}</li></ul> <br> <strong>ID Tiny</strong>:{$product->id}<br><strong>SKU</strong>:{$skuProduct}<br><strong>SKU Variação</strong>:{$product->codigo}", "E");
                            continue;
                        }

                    } else { // Variação não existe, mostrar erro pois precisa ser criada
                        echo "Ocorreu um problema na atualização da variação SKU_PAI={$skuProduct}. Variação não encontrada VAR={$product->codigo}\n";
                        //$this->db->trans_rollback();
                        //$this->log_data('batch', $log_name, "Ocorreu um problema na atualização da variação SKU_PAI={$skuProduct}. Variação não encontrada VAR={$product->codigo}", "E");
                        //$this->log_integration("Erro para atualizar o produto SKU {$skuProduct}", "Não foi possível localizar a variação para atualizar! <ul><li>Variação: {$product->codigo}, não foi localizada para atualizar</li></ul> <br> <strong>ID Tiny</strong>:{$product->id}<br><strong>SKU</strong>:{$skuProduct}<br><strong>SKU Variação</strong>:{$product->codigo}", "E");
                        continue;
                    }
                }

                /*if ($this->db->trans_status() === FALSE){
                    $this->db->trans_rollback();
                    echo "ocorreu um erro\n";
                    continue;
                }*/

                if($tipoVariacao == "V")
                    $this->log_data('batch', $log_name, "Variação atualizada!!! payload=" . json_encode($product) . 'backup_payload_var' . json_encode($verifyVar), "I");
                else
                    $this->log_data('batch', $log_name, "Produto atualizado!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), "I");

                //$this->db->trans_commit();
                echo "atualizada com sucesso\n";
            }
        }
    }
}