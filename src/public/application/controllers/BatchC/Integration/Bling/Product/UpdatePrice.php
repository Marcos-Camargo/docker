<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration/Bling/Product/UpdatePrice run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Bling/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bling/Product/Product.php";

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

		echo "Essa rotina foi inativada\n";
		die; 
		
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
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "E");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $url    = 'https://bling.com.br/Api/v2/produtos/page=1';
        $data   = "&loja={$this->multiStore}&estoque=S&imagem=S";
        $dataProducts = $this->sendREST($url, $data);

        if ($dataProducts['httpcode'] != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
            //$this->log_integration("Erro para atualizar o preço dos produtos", "<h4>Não foi possível consultar a listagem de produtos!!</h4>", "E");
            return false;
        }

        $contentProducts = json_decode($dataProducts['content']);

        if (isset($contentProducts->retorno->erros)) {
            // formatar mensagens de erro para log integration
            $arrErrors = array();
            $errors = $contentProducts->retorno->erros;
            if (!is_array($errors)) $errors = (array)$errors;
            foreach ($errors as $error) {
                $msgErrorIntegration = $error->erro->msg ?? "Erro desconhecido";
                array_push($arrErrors, $msgErrorIntegration);
            }
            //$this->log_integration("Erro para atualizar o preço dos produtos", "<h4>Não foi possível consultar a listagem de produtos!</h4><ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
            return false;
        }

        $regProducts     = $contentProducts->retorno->produtos;
        $haveProductList = true;
        $arrPriceUpdate  = array();
        $page = 1;
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;

        while ($haveProductList) {
            if ($page != 1) {
                $url    = 'https://bling.com.br/Api/v2/produtos/page='.$page;
                $data   = "&loja={$this->multiStore}&estoque=S&imagem=S";
                $dataProducts = $this->sendREST($url, $data);

                $contentProducts = json_decode($dataProducts['content']);

                if ($dataProducts['httpcode'] != 200) {
                    if ($dataProducts['httpcode'] == 504 || $dataProducts['httpcode'] == 401) continue;
                    if ($dataProducts['httpcode'] == 999 && $countlimiteRequestBlock <= $limiteRequestBlock) {
                        echo "aguardo 1 minuto para testar novamente. (Tentativas: {$countlimiteRequestBlock}/{$limiteRequestBlock})\n";
                        sleep(60);
                        $countlimiteRequestBlock++;
                        continue;
                    }

                    echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
                    //$this->log_integration("Erro para atualizar o preço dos produtos", "<h4>Não foi possível consultar a listagem de produtos!</h4>", "E");
                    $haveProductList = false;
                    continue;
                }
                if (isset($contentProducts->retorno->erros[0]->erro->cod) && $contentProducts->retorno->erros[0]->erro->cod == 14) {
                    $haveProductList = false;
                    continue;
                }

                $regProducts = $contentProducts->retorno->produtos;
            }

            foreach ($regProducts as $registro) {

                $registro = $registro->produto;

                // Produto não está na multiloja
                if (!isset($registro->produtoLoja)) {
                    echo "Produto {$registro->codigo} não está na multiloja\n";
                    continue;
                }
                // Não atualizar o preço pelo produto pai
                if (!isset($registro->codigoPai) && isset($registro->variacoes)) {
                    echo "Produto {$registro->codigo} é um produto PAI, não atuaizar o preço, apenas com base na variação\n";
                    continue;
                }

                $tipoVariacao   = isset($registro->codigoPai) ? "V" : "N";
                $skuProduct     = $tipoVariacao == 'V' ? $registro->codigoPai : $registro->codigo;
                $precoProd      = $registro->produtoLoja->preco->precoPromocional == 0 ? $registro->produtoLoja->preco->preco : $registro->produtoLoja->preco->precoPromocional;
                $idProduct      = $registro->id;

                $this->setUniqueId($idProduct); // define novo unique_id

                // Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($idProduct);

                // Não encontrou o produto pelo código da bling
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da bling
                    if (!empty($verifyProduct)) {

//                        if ($verifyProduct['status'] != 1) {
//                            echo "Produto não está ativo\n";
//                            continue;
//                        }

                        // produto com o mesmo preço, não será atualizado
//                        if ($verifyProduct['price'] == $precoProd) {
//                            echo "produto com o mesmo preço, não será atualizado, sku={$skuProduct}, recebi={$precoProd}\n";
//                            echo "atualizou código bling para o produto sku={$skuProduct}, código bling={$idProduct}\n";
//                            continue;
//                        }
                        echo "atualizou código bling para o produto sku={$skuProduct}, código bling={$idProduct}...\n";

                        if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                            if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                                $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                        } else {
                            $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                        }
                    }
                    else { // produto ainda não cadastrado, mostrar erro
                        echo "Produto não encontrado, não será possível atualizar o preço do produto ID_BLING={$idProduct}, SKU={$skuProduct} \n";
                        //$this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o preço do produto ID_BLING={$idProduct}, SKU={$skuProduct}", "E");
                        //$this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Não foi possível localizar o produto para atualizar seu preço, em breve será cadastrado e poderá ser atualizado.</h4> <strong>SKU</strong>: {$skuProduct}<br><strong>ID_BLING</strong>: {$idProduct}", "E");
                        continue;
                    }

                } else {  // encontrou o produto pelo código da bling, atualizar

//                    if ($verifyProduct['status'] != 1) {
//                        echo "Produto não está ativo\n";
//                        continue;
//                    }

                    if ($skuProduct != $verifyProduct['sku']) {
                        echo "Produto encontrado pelo código bling, mas com o sku diferente do sku cadastrado ID_BLING={$idProduct}, SKU={$skuProduct} \n";
                        $this->log_data('batch', $log_name, "Produto encontrado pelo código bling, mas com o sku diferente do sku cadastrado ID_BLING={$idProduct}, SKU={$skuProduct}", "W");
                        $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Produto recebido e encontrado pelo código Bling, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$skuProduct}<br><strong>SKU Na Conecta Lá</strong>: {$verifyProduct['sku']}<br><strong>ID Bling<strong>: {$idProduct}", "E");
                        continue;
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
            $page++;
        } // fim lista

        // Atualiza produtos
        // É feita por fora, pois precisa comparar com o valores
        // das variações e recuperar o maior valor. Não temos
        // preços diferente para variações
        foreach ($arrPriceUpdate as $sku => $product) {

            $price = $product['price'];
            if (!is_float($price) && !is_double($price)) {
                $price = ($price === null ? 0 : (float)str_replace(['.', ','], ['', '.'], $price));
            }

            $idProduct  = $product['id'];

            $verifyProduct = $this->product->getProductForSku($sku);

            // produto com o mesmo preço, não será atualizado
            if (((float)$price) <= 0) {
                echo "produto {$sku} com preço de venda zerado, preço={$price}\n";
                $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>Valor de venda igual a R$0,00 é preciso informar um valor maior que zero.</li></ul> <strong>SKU</strong>: {$sku}<br><strong>ID Bling</strong>: {$idProduct}", "W");
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
                $this->log_data('batch', $log_name, "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro, dados_item_lista=" . json_encode($product), "W");
                $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>SKU: {$sku}</li><li>ID Bling: {$idProduct}</li></ul>", "E");
                continue;
            }

            $this->log_data('batch', $log_name, "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price} ID={$idProduct}" . json_encode($product), "I");

            echo "atualizado com sucesso SKU={$sku}\n";
            $this->log_integration("Preço do produto {$sku} atualizado", "<h4>O preço do produto {$sku} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$price}", "S");

        }
    }
}