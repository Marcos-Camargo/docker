<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration/Bling/Product/CreateProduct run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Bling/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bling/Product/Product.php";

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
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('CreateProduct');
    }
    // php index.php BatchC/Integration/Bling/Product/CreateProduct run null 65
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

        $this->setDateStartJob();
        $this->setLastRun();
        $this->formatDateFilterBling();

        // Recupera os produtos
        if ($status = $this->getProducts());
            $status = $this->getProducts(true);

        // Grava a execução
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
            //$this->log_integration("Erro para integrar produtos", "Não foi possível consultar a listagem de produtos!!", "E");
            return false;
        }

        $contentProducts = json_decode($dataProducts['content']);

        if (isset($contentProducts->retorno->erros)) {
            // formatar mensagens de erro para log integration
            $arrErrors = array();
            $errors = $contentProducts->retorno->erros;

            if (isset($errors[0]) && $errors[0]->erro->cod == 14) {
                echo "não encontrou resultados para os parametros informados ({$url}{$data})\n";
                return true;
            }

            if (!is_array($errors)) $errors = (array)$errors;
            foreach ($errors as $error) {
                $msgErrorIntegration = $error->erro->msg ?? "Erro desconhecido";
                array_push($arrErrors, $msgErrorIntegration);
            }
            //$this->log_integration("Erro para integrar produtos", "<h4>Não foi possível consultar a listagem de produtos!</h4><ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
            return false;
        }

        $regProducts = $contentProducts->retorno->produtos;
        $haveProductList = true;
        $page = 1;
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;
        $arrayProductErroCheck = array();

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
                    $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "E");
                    //$this->log_integration("Erro para integrar produtos", "Não foi possível consultar a listagem de produtos!", "E");
                    $haveProductList = false;
                    continue;
                }
                if (isset($contentProducts->retorno->erros[0]->erro->cod) && $contentProducts->retorno->erros[0]->erro->cod == 14) {
                    $haveProductList = false;
                    continue;
                }

                $regProducts = $contentProducts->retorno->produtos;
            }
            ECHO "\n##### INÍCIO PÁGINA: ({$page})\n\n";

            foreach ($regProducts as $registro) {

                $registro = $registro->produto;

                // Produto não está na multiloja
                if (!isset($registro->produtoLoja)) {
                    echo "Produto {$registro->codigo} não está na multiloja\n";
                    continue;
                }

                $this->setUniqueId($registro->id); // define novo unique_id

                // Inicia transação
                $this->db->trans_begin();

                $created = true;

                $id_produto     = $registro->id;
                $precoProd      = $registro->produtoLoja->preco->precoPromocional == 0 ? $registro->produtoLoja->preco->preco : $registro->produtoLoja->preco->precoPromocional;
                $tipoVariacao   = isset($registro->codigoPai) ? "V" : "N"; // N = P
                $skuVar         = trim($registro->codigo);
                $skuProduct     = trim($registro->codigo);
                $nameProduct    = $registro->descricao;

                echo "Pegando {$skuProduct}...\n";

                // consulta produto pai da variação
                if($tipoVariacao == "V") {

                    if (in_array($registro->codigoPai, $arrayProductErroCheck)) {
                        echo "Variação {$skuVar} já tentou integrar e deu erro\n";
                        $this->db->trans_rollback();
                        continue;
                    }

                    // verifica se a variação já existe
                    $verifyVariationID = $this->product->getVariationForIdErp($id_produto);
                    if ($verifyVariationID) {
                        echo "Variação {$skuVar} já existe\n";
                        $this->db->trans_rollback();
                        continue;
                    } else { // caso exista o sku, mas não tenha código bling, vou atualizar
                        $skuProduct      = trim($registro->codigoPai);
                        $verifyVariationSku = $this->product->getVariationForSkuAndSkuVar($skuProduct, $skuVar);
                        if ($verifyVariationSku) {
                            $this->db->trans_rollback();
                            $this->product->updateVariantForSku($skuProduct, $skuVar, array('variant_id_erp' => $id_produto)); // produto atualizado com o ID Bling
                            echo "ID Bling atualizado na variação \n";
                            continue;
                        }
                    }

                    // Consulta o produto pai
                    $url = "https://bling.com.br/Api/v2/produto/{$registro->codigoPai}";
                    $data   = "&loja={$this->multiStore}&estoque=S&imagem=S&filters=situacao[A];tipo[P]";
                    $data   .= $this->dateLastJob ? ";{$filterDate}[{$this->dateLastJob}{$space}TO{$space}{$this->dateStartJob}]" : '';
                    $dataProduct = $this->sendREST($url, $data);

                    if ($dataProduct['httpcode'] != 200) {
                        echo "Produto PAI bling com SKU={$registro->codigoPai} encontrou um erro, dados=" . json_encode($registro) . " retorno=" . json_encode($dataProduct) . "\n";
                        $this->db->trans_rollback();
                        // $this->log_data('batch', $log_name, "Produto PAI bling com SKU={$registro->codigoPai} encontrou um erro, dados=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "E");
                        //$this->log_integration("Erro para integrar produto {$registro->codigoPai}", "Não foi possível obter informações do produto PAI de uma variação! <strong>SKU Pai</strong>: {$registro->codigoPai}<br><strong>SKU Variação</strong>: {$skuVar}<br><strong>Descrição</strong>: {$registro->descricao}", "E");

                        // adiciono no array para não consultar mais esse produto pai
                        if (!in_array($registro->codigoPai, $arrayProductErroCheck))
                            array_push($arrayProductErroCheck, $registro->codigoPai);

                        continue;
                    }

                    $contentProduct = json_decode($dataProduct['content']);
                    if (!isset($contentProduct->retorno->produtos[0]->produto)) {
                        $this->db->trans_rollback();
                        continue;
                    }
                    $contentProduct = $contentProduct->retorno->produtos[0]->produto;
                    $skuProduct     = trim($contentProduct->codigo);
                    $nameProduct    = $contentProduct->descricao;
                } else {
                    if (in_array($skuProduct, $arrayProductErroCheck)) {
                        echo "Produto {$skuProduct} já tentou integrar e deu erro\n";
                        $this->db->trans_rollback();
                        continue;
                    }
                }

                // Recupera o código do produto pai
                $idProduct = $tipoVariacao == "V" ? $contentProduct->id : $registro->id;
                $verifyProduct = $this->product->getProductForIdErp($idProduct, $tipoVariacao == "V" ? null : $skuProduct);

                // Não encontrou o produto pelo código da bling
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da bling
                    if (!empty($verifyProduct)) {
                        $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID Bling
                        $created = false;
                    }
                    else { // produto ainda não cadastrado, cadastrar
                        try {
                            $productCreate = $this->product->createProduct($tipoVariacao == "V" ? $contentProduct : $registro, $precoProd);
                        } catch (Throwable $e) {
                            echo "Não foi possível cadastrar o produto={$idProduct} encontrou um erro, dados=" . json_encode($tipoVariacao == "V" ? $contentProduct : $registro) . " retorno={$e->getMessage()}\n";
                            $this->db->trans_rollback();
                            continue;
                        }
                        if (!$productCreate['success']) {
                            echo "Não foi possível cadastrar o produto={$idProduct} encontrou um erro, dados=" . json_encode($tipoVariacao == "V" ? $contentProduct : $registro) . " retorno=" . json_encode($productCreate) . "\n";
                            $this->db->trans_rollback();
                            //$this->log_data('batch', $log_name, "Produto PAI bling com ID={$idProduct} encontrou um erro, dados=" . json_encode($registro) . " retorno=" . json_encode($tipoVariacao == "V" ? $contentProduct : $registro), "W");
                            $productIdentifier = "SKU {$skuProduct}";
                            if ($skuProduct == "") {
                                $productIdentifier = "ID Bling {$idProduct}";
                            }
                            $this->log_integration("Erro para integrar produto - {$productIdentifier}", 'Existem algumas pendências no cadastro do produto na plataforma de integração <ul><li>' . implode('</li><li>', $productCreate['message']) . "</li></ul> <br> <strong>ID Bling</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$registro->descricao}", "E");
                            // adiciono no array para não consultar mais esse produto pai
                            if (!in_array($skuProduct, $arrayProductErroCheck))
                                array_push($arrayProductErroCheck, $skuProduct);

                            continue;
                        }
                    }

                } else { // encontrou o produto pelo código da bling
                    $skuProduct = trim($verifyProduct['sku']);
                    $created = false;
                }

                if($tipoVariacao == "V") {

                    // Encontrou ou cadastrou o produto, verifica se existe a variação
                    $verifyVar = $this->product->getVariationForSkuAndSkuVar($skuProduct, $skuVar);

                    //Variação não existe
                    if (!$verifyVar) {
                        echo "variação não existe, vou criar ela...\n";
                        $createVariation = $this->product->createVariation($registro, $skuProduct);

                        // Ocorreu problema na criação da variação
                        if (!$createVariation['success']) {
                            echo "Ocorreu um problema na criação da variação SKU_PAI={$skuProduct}. Foi enviado=" . json_encode($registro) . " retorno={$createVariation['message']}\n";
                            // rick não faço mais rollback pois quero alterar o produto pai e colocar ele como incompleto
                            // $this->db->trans_rollback();
							$this->db->trans_commit();
                            //$this->log_data('batch', $log_name, "Ocorreu um problema na criação da variação SKU_PAI={$skuProduct}. Foi enviado=" . json_encode($registro) . " retorno={$createVariation['message']}", "E");
                            $this->log_integration("Erro para integrar variação SKU {$skuProduct}", "<h4>Não foi possível criar a variação</h4> <ul><li>{$createVariation['message']}</li></ul> <strong>ID Bling</strong>: {$registro->id}<br><strong>SKU</strong>: {$skuProduct}<br><strong>SKU Variação</strong>: {$registro->codigo}<br><strong>Descrição</strong>: {$nameProduct}", "E");
                            continue;
                        }
                        $created = true;

                    } else { // Variação existe, não fazer nada (atualizar código bling da variação, caso não tenha)

                        $verifyVariant = $this->product->getVariationForIdErp($registro->id);
                        if (!$verifyVariant) {
                            $this->product->updateVariantForSku($skuProduct, $skuVar, array('variant_id_erp' => $registro->id)); // produto atualizado com o ID Bling
                            echo "ID Bling atualizado na variação \n";
                        }

                        echo "Variação {$skuVar} do produto {$skuProduct} existe, não fazer nada\n";
//                        $created = false;
                    }
                }

                if ($this->db->trans_status() === FALSE){
                    $this->db->trans_rollback();
                    echo "ocorreu um erro\n";
                    continue;
                }

                if ($created) {

                    $messageAdd = "";
                    if (isset($productCreate) && $tipoVariacao != "V") {
                        $excessoesVarMultiLoja = $productCreate['variations_not_multiloja'] ?? array();
                        if (count($excessoesVarMultiLoja)) {
                            $messageAdd = '<br><br>Foram encontradas variações que não estão na multi loja, não foram cadastradas: <ul><li>' . implode('</li><li>', $excessoesVarMultiLoja) . '</li></ul>';
                        }
                        $this->log_integration("Produto {$skuProduct} integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi criado com sucesso</li></ul><br><strong>ID Bling</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$nameProduct} {$messageAdd}", "S");
                    }

                    else if ($tipoVariacao == "V")
                        $this->log_integration("Variação {$registro->codigo} do produto {$skuProduct} integrada", "<h4>Nova variação integrada com sucesso</h4> <ul><li>A variação {$registro->codigo} do produto {$skuProduct}, foi criada com sucesso</li></ul><br><strong>ID Bling</strong>: {$registro->id}<br><strong>SKU</strong>: {$skuProduct}<br><strong>SKU Variação</strong>: {$registro->codigo}<br><strong>Descrição</strong>: {$nameProduct} {$messageAdd}", "S");


                    unset($registro->descricaoCurta);
                    unset($registro->descricaoComplementar);
                    $this->log_data('batch', $log_name, "Produto cadastrado - payload=" . json_encode($registro), "I");

                    echo "Produto {$skuProduct} cadastrado com sucesso\n";
                }
                $this->db->trans_commit();
            }
            ECHO "\n##### FIM PÁGINA: ({$page})\n";
            $page++;
        }

        return true;
    }
}