<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration/Bling/Product/UpdateProduct run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Bling/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bling/Product/Product.php";

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
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('UpdateProduct');
    }

    // php index.php BatchC/Integration/Bling/Product/UpdateProduct run null 65 
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
        echo "Pegando produtos para atualizar \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        $this->setDateStartJob();
        $this->setLastRun();
        $this->formatDateFilterBling();

        // Recupera os produtos
        if ($status = $this->getProducts());
            $status = $this->getProducts(true);

        // Grava a última execução
        if ($status) $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Recupera os produtos
     *
     * @param   bool $filterMultiloja O filtro será pela data de alteração da multi loja
     * @return  bool                    Retorna se o get de produtos ocorreu tudo certo e poderá atualizar a data de última buscar
     */
    public function getProducts($filterMultiloja = false)
    {
        $filterDate = $filterMultiloja ? 'dataAlteracaoLoja' : 'dataAlteracao';

        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $space = '%20';

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "E");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $url    = 'https://bling.com.br/Api/v2/produtos/page=1';
        $data   = "&loja={$this->multiStore}&estoque=S&imagem=S&filters=situacao[A];tipo[P]";
        $data   .= $this->dateLastJob ? ";{$filterDate}[{$this->dateLastJob}{$space}TO{$space}{$this->dateStartJob}]" : '';
        $dataProducts = $this->sendREST($url, $data);

        if ($dataProducts['httpcode'] != 200) {
            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
            return false;
        }

        $contentProducts = json_decode($dataProducts['content']);

        if (isset($contentProducts->retorno->erros)) {
            // formatar mensagens de erro para log integration
            $arrErrors = array();
            $errors = $contentProducts->retorno->erros;

            if (isset($errors[0]->erro->cod) && $errors[0]->erro->cod == 14) {
                echo "não encontrou resultados para os parametros informados ({$url}{$data})\n";
                return true;
            }

            if (!is_array($errors)) $errors = (array)$errors;
            foreach ($errors as $error) {
                $msgErrorIntegration = $error->erro->msg ?? "Erro desconhecido";
                array_push($arrErrors, $msgErrorIntegration);
            }
            return false;
        }

        $regProducts = $contentProducts->retorno->produtos;
        $haveProductList = true;
        $page = 1;
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;
        $productsUpdated = array();

        while ($haveProductList) {
            if ($page != 1) {
                $url    = 'https://bling.com.br/Api/v2/produtos/page='.$page;
                $data   = "&loja={$this->multiStore}&estoque=S&imagem=S&filters=situacao[A];tipo[P]";
                $data   .= $this->dateLastJob ? ";{$filterDate}[{$this->dateLastJob}{$space}TO{$space}{$this->dateStartJob}]" : '';
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

                $this->setUniqueId($registro->id); // define novo unique_id
                $id_produto     = $registro->id;
                $tipoVariacao   = isset($registro->codigoPai) ? "V" : "N"; // N = P
                $skuVar         = $registro->codigo;
                $skuProduct     = $tipoVariacao == "V" ? $registro->codigoPai : $registro->codigo;
                $nameProduct    = $registro->descricao;

                if ($tipoVariacao == "V") {
                    echo "{$skuVar} é uma variação, só vou pegar os produtos pai e simples\n";
                    continue;
                }

                // Recupera o código do produto pai
                $idProduct = $registro->id;
                $verifyProduct = $this->product->getProductForIdErp($id_produto, $tipoVariacao == "V" ? null : $skuProduct);

                // Não encontrou o produto pelo código da bling
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da bling
                    if (!empty($verifyProduct)) {
                        $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $id_produto)); // produto atualizado com o ID Bling
                        continue;
                    } else { // produto ainda não cadastrado, cadastrar
                        echo "Produto não encontrado, não será possível atualizar o produto ID_BLING={$id_produto}, SKU={$skuProduct} \n";
                        continue;
                    }
                }

                $productUpdate = $this->product->updateProduct($registro);
                unset($registro->descricaoCurta);
                unset($registro->descricaoComplementar);
                if ($productUpdate['success'] === false) {
                    echo "Não foi possível cadastrar o produto={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($productUpdate) . "\n";
                    $this->log_data('batch', $log_name, "Erro para atualizar o produto ID={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($registro), "E");
                    continue;
                }
                if (count($productUpdate['errorsVar'])) {
                    foreach ($productUpdate['errorsVar'] as $errorVar) {
                        echo "{$errorVar['title']}. Foi enviado=" . json_encode($registro) . " retorno=" . json_encode($productUpdate) . "\n";
                        $this->log_data('batch', $log_name, "{$errorVar['title']}. Foi enviado=" . json_encode($registro) . " retorno=" . json_encode($productUpdate) . "\n", "W");
                        $this->log_integration($errorVar['title'], $errorVar['description'], "W");
                    }
                }
                if (count($productUpdate['successVar'])) {
                    foreach ($productUpdate['successVar'] as $successVar) {
                        echo "{$successVar['title']}.\n";
                        $this->log_data('batch', $log_name, "{$successVar['title']}. Foi enviado=" . json_encode($registro) . " retorno=" . json_encode($productUpdate) . "\n", "I");
                        $this->log_integration($successVar['title'], $successVar['description'], "S");
                    }
                }
                if ($productUpdate['success'] === null) {
                    echo "Product {$skuProduct} está igual ao do cadastro, não será atualizado\n";
                }

                // mostra log se atualizou o produto pai
                if ($productUpdate['success'] === true)
                    $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi atualizado com sucesso</li></ul><br><strong>ID Bling</strong>: {$id_produto}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$nameProduct}", "S");

                if ($productUpdate['success'] === true || (isset($productUpdate['successVar']) && count($productUpdate['successVar']))) {
                    echo "Produto {$skuProduct} atualizado\n";
                    $this->log_data('batch', $log_name, "Produto atualizado!!! payload=" . json_encode($registro) . 'backup_payload_prod' . json_encode($verifyProduct), "I");
                }
            }
            $page++;
        }

        return true;
    }
}