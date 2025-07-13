<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration/Tiny/Product/CreateProduct run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

class CreateProduct extends Main
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

        $this->setJob('CreateProduct');
    }

	// php index.php BatchC/Integration/Tiny/Product/CreateProduct run null 67
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
        $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
        $data   = $this->listPrice ? "&idListaPreco={$this->listPrice}" : '';
        $dataProducts = json_decode($this->sendREST($url, $data));

        if ($dataProducts->retorno->status != "OK") {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->retorno->codigo_erro != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                $this->log_integration("Erro para integrar produtos", "Não foi possível consultar a listagem de produtos!", "E");
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
                        $this->log_integration("Erro para integrar produto", "Não foi possível consultar a listagem de produtos!", "E");
                    }
                    continue;
                }

                $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;
            }

            foreach ($regListProducts as $registro) {
                // Inicia transação
                //$this->db->trans_begin();

                $created = true;

                $id_produto = $this->listPrice ? $registro->registro->id_produto : $registro->produto->id;
                $precoProd  = $this->listPrice ? $registro->registro->preco : $registro->produto->preco;
                $skuList    = $registro->produto->codigo ?? null;
                $this->setUniqueId($id_produto); // define novo unique_id
                echo "Pegando {$id_produto}...\n";

                if($this->product->getProductForIdErp($id_produto, $skuList)){
                    echo "ID={$id_produto} já está cadastrado\n";
                    //$this->db->trans_rollback();
                    continue;
                }

                $url    = "https://api.tiny.com.br/api2/produto.obter.php";
                $data   = "&id={$id_produto}";
                $dataProduct = json_decode($this->sendREST($url, $data));

                if ($dataProduct->retorno->status != "OK") {
                    echo "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=".json_encode($registro)." retorno=" . json_encode($dataProduct) . "\n";
                    //$this->db->trans_rollback();
                    if ($dataProduct->retorno->codigo_erro != 99) {
                        $this->log_data('batch', $log_name, "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                        $this->log_integration("Erro para integrar produto - ID Tiny {$id_produto}", "Não foi possível obter informações do produto na Tiny! <br> <strong>ID Tiny</strong>:{$id_produto} retorno da Tiny=" . json_encode($dataProduct), "E");
                    }
                    continue;
                }

                $product = $dataProduct->retorno->produto; // Dados produto/variação

                $tipoVariacao   = $product->tipoVariacao;
                $skuVar         = trim($product->codigo);
                $skuProduct     = trim($product->codigo);

                /**
                 * UM PRODUTO QUE CONTEM VARIAÇÕES, SERÃO MOSTRADOS APENAS NO PAYLOAD
                 * DA LISTA DE PREÇOS AS SUAS VARIAÇÕES, O PRODUTO PAI NÃO SERÁ MOSTRADO,
                 * DEVERÁ FAZER UM GET NA VARIAÇÃO E OBTER O PRODUTO PAI PARA CADASTRO
                 */

                // Recupera o código do produto pai
                $idProduct = $tipoVariacao == "V" ? $product->idProdutoPai : $product->id;

                if (in_array($idProduct, $arrayProductErroCheck)) {
                    echo "Já tentou integrar o ID={$idProduct} e deu erro\n";
                    //$this->db->trans_rollback();
                    continue;
                }

                $verifyProduct = $this->product->getProductForIdErp($idProduct, $tipoVariacao == "V" ? null : $skuProduct);

                // Não encontrou o produto pelo código da tiny
                if (empty($verifyProduct)) {
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
                                $this->log_integration("Erro para integrar produto - ID Tiny {$idProduct}", "Não foi possível obter informações do produto PAI de uma variação! <strong>ID Tiny</strong>: {$idProduct}<br><strong>SKU Variação</strong>: {$skuVar}<br><strong>Descrição</strong>: {$product->nome}", "E");
                            }
                            continue;
                        }
                        $skuProduct = trim($dataProduct->retorno->produto->codigo);
                    }

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da tiny
                    if (!empty($verifyProduct)) {
                        $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID Tiny
                        $created = false;
                    }
                    else { // produto ainda não cadastrado, cadastrar
                        $productCreate = $this->product->createProduct($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product, $precoProd);
                        if (!$productCreate['success']) {
                            echo "Não foi possível cadastrar o produto={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($tipoVariacao == "V" ? $dataProduct->retorno->produto : $product) . " retorno=" . json_encode($productCreate) . "\n";
                            //$this->db->trans_rollback();
                            //$this->log_data('batch', $log_name, "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                            $productIdentifier = "SKU {$skuProduct}";
                            if ($skuProduct == "") {
                                $productIdentifier = "ID Tiny {$idProduct}";
                            }
                            $this->log_integration("Erro para integrar produto - {$productIdentifier}", 'Existem algumas pendências no cadastro do produto na plataforma de integração <ul><li>' . implode('</li><li>', $productCreate['message']) . "</li></ul> <br> <strong>ID Tiny</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$product->nome}", "E");

                            // adiciono no array para não consultar mais esse produto pai
                            if (!in_array($idProduct, $arrayProductErroCheck))
                                array_push($arrayProductErroCheck, $idProduct);

                            continue;
                        }
                    }

                } else { // encontrou o produto pelo código da tiny
                    $skuProduct = trim($verifyProduct['sku']);
                    $created = false;
                }

                if($tipoVariacao == "V") {

                    // Encontrou ou cadastrou o produto, verifica se existe a variação
                    $verifyVar = $this->product->getVariationForSkuAndSkuVar($skuProduct, $skuVar);

                    //Variação não existe
                    if (!$verifyVar) {
                        echo "variação não existe, vou criar ela...\n";
                        $createVariation = $this->product->createVariation($product, $skuProduct);

                        // Ocorreu problema na criação da variação
                        if (!$createVariation['success']) {
                            echo "Ocorreu um problema na criação da variação SKU_PAI={$skuProduct}. Foi enviado=" . json_encode($product) . " retorno={".is_array($createVariation['message'])?json_encode($createVariation['message']):$createVariation['message']."}\n";
                            //$this->db->trans_rollback();
                            $this->log_data('batch', $log_name, "Ocorreu um problema na criação da variação SKU_PAI={$skuProduct}. Foi enviado=" . json_encode($product) . " retorno={".is_array($createVariation['message'])?json_encode($createVariation['message']):$createVariation['message']."}", "W");
                            $this->log_integration("Erro para integrar variação SKU {$skuProduct}", "<h4>Não foi possível criar a variação</h4> <ul><li>{".is_array($createVariation['message'])?json_encode($createVariation['message']):$createVariation['message']."}</li></ul> <strong>ID Tiny</strong>: {$product->id}<br><strong>SKU</strong>: {$skuProduct}<br><strong>SKU Variação</strong>: {$product->codigo}<br><strong>Descrição</strong>: {$product->nome}", "E");

                            // adiciono no array para não consultar mais esse produto pai
                            if (!in_array($idProduct, $arrayProductErroCheck))
                                array_push($arrayProductErroCheck, $idProduct);

                            continue;
                        }
                        $created = true;

                    } else { // Variação existe, não fazer nada

                        $verifyVariant = $this->product->getVariationForIdErp($id_produto);
                        if (!$verifyVariant) {
                            $this->product->updateVariantForSku($skuProduct, $skuVar, array('variant_id_erp' => $id_produto)); // produto atualizado com o ID Tiny
                            echo "ID Tiny atualizado na variação \n";
                        }

                        echo "Variação existe {$skuVar} do produto {$skuProduct}, não fazer nada\n";
//                        $created = false;
                    }
                }

                /*if ($this->db->trans_status() === FALSE){
                    $this->db->trans_rollback();
                    echo "ocorreu um erro\n";
                }*/

                if ($created) {

                    $messageAdd = "";
                    if (isset($productCreate)) {
                        $excessoesVarListPreco = $productCreate['variations_not_list'];
                        if (count($excessoesVarListPreco)) {
                            $messageAdd = '<br><br>Foram encontradas variações que não estão na lista, não foram cadastradas: <ul><li>' . implode('</li><li>', $excessoesVarListPreco) . '</li></ul>';
                        }
                        $this->log_integration("Produto {$skuProduct} integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi criado com sucesso</li></ul><br><strong>ID Tiny</strong>: {$product->id}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$product->nome} {$messageAdd}", "S");
                    }

                    else if ($tipoVariacao == "V")
                        $this->log_integration("Variação {$product->codigo} do produto {$skuProduct} integrada", "<h4>Nova variação integrada com sucesso</h4> <ul><li>A variação {$product->codigo} do produto {$skuProduct}, foi criada com sucesso</li></ul><br><strong>ID Tiny</strong>: {$product->id}<br><strong>SKU</strong>: {$skuProduct}<br><strong>SKU Variação</strong>: {$product->codigo}<br><strong>Descrição</strong>: {$product->nome} {$messageAdd}", "S");

                    unset($product->descricao_complementar);
                    $this->log_data('batch', $log_name, "Produto cadastrado!!! payload=" . json_encode($product), "I");

                    echo "Produto {$id_produto} cadastrado com sucesso\n";
                }
                //$this->db->trans_commit();
            }
        }
    }
}