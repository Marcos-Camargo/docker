<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Integration/Bling/Product/UpdateStock run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Bling/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bling/Product/Product.php";

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

        /* faz o que o job precisa fazer */
        echo "Pegando produtos para cadastrar \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);
		
		echo "Essa rotina foi inativada\n";
		die; 
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

        // começando a pegar os produtos para criar
        $url    = 'https://bling.com.br/Api/v2/produtos/page=1';
        $data   = "&loja={$this->multiStore}&estoque=S";
        $dataProducts = $this->sendREST($url, $data);

        if ($dataProducts['httpcode'] != 200) {
            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
            //$this->log_integration("Erro para atualizar o estoque dos produtos", "Não foi possível consultar a listagem de produtos!!", "E");
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
            //$this->log_integration("Erro para atualizar o estoque dos produtos", "<h4>Não foi possível consultar a listagem de produtos!</h4><ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
            return false;
        }

        $regProducts = $contentProducts->retorno->produtos;
        $haveProductList = true;
        $page = 1;
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;
        $arrProductsStock = array();

        while ($haveProductList) {
            if ($page != 1) {
                $url    = 'https://bling.com.br/Api/v2/produtos/page='.$page;
                $data   = "&loja={$this->multiStore}&estoque=S";
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
                    //$this->log_integration("Erro para atualizar o estoque dos produtos", "Não foi possível consultar a listagem de produtos!", "E");
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
                // Não atualizar o estoque pelo produto pai
//                if (!isset($registro->codigoPai) && isset($registro->variacoes)) {
//                    echo "Produto {$registro->codigo} é um produto PAI, não atuaizar o preço, apenas com base na variação\n";
//                    continue;
//                }

                if (isset($registro->codigoPai)) {
                    echo "{$registro->codigo} é variação, não atualizar\n";
                    continue;
                }

                // Inicia transação
//                $this->db->trans_begin();

                $tipoVariacao   = isset($registro->codigoPai) ? "V" : "N";
                $skuVar         = $registro->codigo;
                $skuProduct     = $tipoVariacao == "V" ? $registro->codigoPai : $registro->codigo;
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

                        $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID Bling

//                        if ($verifyProduct['status'] != 1) {
//                            echo "Produto não está ativo\n";
////                            $this->db->trans_rollback();
//                            continue;
//                        }

                        // atualiza produto Pai
                        if (!isset($registro->codigoPai) && isset($registro->variacoes))
                            $updateStock = $this->updateStock($tipoVariacao, $skuProduct, $skuVar, $registro, true);
                        else
                            $updateStock = $this->updateStock($tipoVariacao, $skuProduct, $skuVar, $registro);

                        if (isset($updateStock['success']) && $updateStock['success'] == false) {
                            echo $updateStock['message'];
                            $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4><ul><li>{$updateStock['message'][0]}</li></ul>", "W");
                            continue;
                        }

                        $arrProductsStock[key($updateStock)] = $updateStock[key($updateStock)];

//                        if ($updateStock[0] === false) {
//                            echo "Alerta para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($updateStock) . "\n";
//                            $this->db->trans_rollback();
//                            $this->log_data('batch', $log_name, "Alerta para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($updateStock), "W");
//                            $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>", "E");
//                            continue;
//                        }
//                        if ($updateStock[0] === null) {
//                            if($tipoVariacao == "V")
//                                echo "Estoque da variação={$skuVar} igual ao do banco, não será modificado\n";
//                            else
//                                echo "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";
//                            $this->db->trans_rollback();
//                            continue;
//                        }

                    } else { // produto ainda não cadastrado
                        echo "Produto não encontrado, não será possível atualizar o produto ID_BLING={$idProduct}, SKU={$skuProduct} \n";
//                        $this->db->trans_rollback();
                        //$this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o produto ID_BLING={$idProduct}, SKU={$skuProduct}", "E");
                        //$this->log_integration("Alerta para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível localizar o produto para atualizar o estoque, em breve será cadastrado e poderá ser atualizado.</h4> <strong>SKU</strong>: {$skuProduct}<br><strong>ID Bling</strong>: {$idProduct}", "W");
                        continue;
                    }

                } else {
//                    if ($verifyProduct['status'] != 1) {
//                        echo "Produto não está ativo\n";
////                        $this->db->trans_rollback();
//                        continue;
//                    }

                    // atualiza produto Pai
                    if (!isset($registro->codigoPai) && isset($registro->variacoes))
                        $updateStock = $this->updateStock($tipoVariacao, $skuProduct, $skuVar, $registro, true);
                    else
                        $updateStock = $this->updateStock($tipoVariacao, $skuProduct, $skuVar, $registro);

                    if (isset($updateStock['success']) && $updateStock['success'] == false) {
                        echo $updateStock['message'][0];
                        $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4><ul><li>{$updateStock['message'][0]}</li></ul>", "W");
                        continue;
                    }

                    $arrProductsStock[key($updateStock)] = $updateStock[key($updateStock)];

//                    if ($updateStock[0] === false) {
//                        echo "Alerta para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($updateStock) . "\n";
//                        $this->db->trans_rollback();
//                        $this->log_data('batch', $log_name, "Alerta para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($updateStock), "W");
//                        $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>" . json_encode($verifyProduct), "E");
//                        continue;
//                    }
//                    if ($updateStock[0] === null) {
//                        if($tipoVariacao == "V")
//                            echo "Estoque da variação={$skuVar} igual ao do banco, não será modificado\n";
//                        else
//                            echo "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";
//                        $this->db->trans_rollback();
//                        continue;
//                    }
                }

//                if ($this->db->trans_status() === FALSE) {
//                    $this->db->trans_rollback();
//                    echo "ocorreu um erro\n";
//                }

//                $this->db->trans_commit();
//                if($tipoVariacao == "V") {
//                    echo "Estoque da variação={$skuVar} atualizada com sucesso\n";
//                    $this->log_integration("Estoque da variação {$skuVar} atualizada", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
//                    $this->log_data('batch', $log_name, "Estoque da variação {$skuVar} atualizada. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
//                } else {
//                    $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
//                    echo "Estoque do produto={$skuProduct} atualizado com sucesso\n";
//                    $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
//                }
            }
            $page++;
        }

        foreach ($arrProductsStock as $idProduct => $stock) {

            $tipoUpdate = is_array($stock) ? "V" : "N";

            if ($tipoUpdate == "V") {
                $qtyVars = 0;
                foreach ($stock as $idVar => $stockVar) {

                    $getStockReal = $this->product->getStockForIdErp($idProduct, $idVar);

                    if (!$getStockReal) {
                        echo "ocorreu um problema para obter os dados da variação {$idVar} do produto {$idProduct}\n";
                        continue;
                    }

                    $stockReal    = $getStockReal['qty'];
                    $skuProduct   = $getStockReal['skuProd'];
                    $skuVariation = $getStockReal['skuVar'];
                    $qtyVars += $stockVar;

                    if($stockReal == $stockVar) {
                        echo "Estoque da variação={$skuVar} igual ao do banco, não será modificado\n";
                        continue;
                    }

                    $update = $this->product->updateStockVariation($skuVariation, $skuProduct, $stockVar);

                    if (!$update) {
                        echo "ocorreu um problema para atualizar o estoque da variação {$skuVariation}\n";
                        continue;
                    }

                    echo "Estoque da variação={$skuVariation} atualizada com sucesso\n";
                    $this->log_integration("Estoque da variação {$skuVariation} atualizada", "<h4>O estoque da variação {$skuVariation} do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$stockReal}<br><strong>Estoque alterado:</strong> {$stockVar}", "S");
                    $this->log_data('batch', $log_name, "Estoque da variação {$skuVariation} atualizada. estoque_anterior={$stockReal} estoque_atualizado={$stockVar}", "I");
                }
                $stock = $qtyVars;
            }

            // comparar estoque
            $getStockReal = $this->product->getStockForIdErp($idProduct);

            if (!$getStockReal) {
                echo "ocorreu um problema para objet os dados do produto {$idProduct}\n";
                continue;
            }

            $stockReal    = $getStockReal['qty'];
            $skuProduct   = $getStockReal['sku'];

            if($stockReal == $stock) {
                echo "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";
                continue;
            }

            $update = $this->product->updateStockProduct($skuProduct, $stock);

            if (!$update) {
                echo "ocorreu um problema para atualizar o estoque do produto {$skuProduct}\n";
                continue;
            }

            $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$stockReal}<br><strong>Estoque alterado:</strong> {$stock}", "S");
            echo "Estoque do produto={$skuProduct} atualizado com sucesso\n";
            $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$stockReal} estoque_atualizado={$stock}", "I");

        }
    }

    /**
     * Atualiza estoque de um produto ou variação, caso esteja diferente
     *
     * @param   string  $tipoVariacao   Tipo de variação, "N" ou "V"
     * @param   string  $skuProduct     SKU do produto (Normal ou Pai)
     * @param   string  $skuVariation   SKU da variação caso tenha
     * @param   int     $registro       Payload
     * @param   int     $verifyVars     Verificar variações de produto PAI
     * @return  array                   Retorna o estado da atualização | null=Está com o mesmo estoque, true=atualizou, false=deu problema
     */
    public function updateStock($tipoVariacao, $skuProduct, $skuVariation, $registro, $verifyVars = false)
    {
        $stock  = $this->product->getGeneralStock($registro->depositos ?? array(), $registro->estoqueAtual ?? 0);
        $idProd = $registro->id;

        // atualiza produto PAI
        if ($verifyVars) {
            $requestStock = $this->product->getStockProductPai($registro);

            if (isset($requestStock['success']) && !$requestStock['success']) return $requestStock;

            $stock      = $requestStock['new'];
            $stockReal  = $requestStock['current'];
            $allStock   = (array)$requestStock['allStock'];

//            if($stockReal == $stock) return array(null);

            return array($idProd => $allStock);

//            return array($this->product->updateStockProduct($skuProduct, $stock), $stockReal, $stock);
        }
        if ($tipoVariacao == "N" && !$verifyVars) { // Atualiza produto simples

            // comparar estoque
//            $stockReal = $this->product->getStockForSku($skuProduct);
//            if($stockReal == $stock) return array(null);

            return array($idProd => $stock);

//            return array($this->product->updateStockProduct($skuProduct, $stock), $stockReal, $stock);
        }
        elseif ($tipoVariacao == "V" && !$verifyVars) { // atualiza variação
            // comparar estoque
//            $stockReal = $this->product->getStockForSku($skuProduct, $skuVariation);
//            if($stockReal == $stock) return array(null);

//            return array($idProd => $stock);

//            return array($this->product->updateStockVariation($skuVariation, $skuProduct, $stock), $stockReal, $stock);
        }

        return array(
            'success' => false,
            'message' => ['Não foi possível atualizar o estoque.']
        );
    }
}