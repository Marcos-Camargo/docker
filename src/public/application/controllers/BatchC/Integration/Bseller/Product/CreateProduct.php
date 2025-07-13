<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration/Bseller/Product/CreateProduct run null 51
 *
 * **************-| FINAL DO PROGRAMA |-**************
 * (X) Criar sleep de 1 minuto quando exceder limite da requisições
 * ( ) Existe variação na conecta lá, mas foi excluida no ERP, comparar para remover da conecta lá ?
 *
 */
require APPPATH . "controllers/BatchC/Integration/Bseller/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bseller/Product/Product.php";
require APPPATH . "libraries/Traits/VerifyFieldsProduct.trait.php";

class CreateProduct extends Main
{
    use VerifyFieldsProduct;
    private $product;

    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->model('model_settings');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens
        $this->product = new Product($this);

        $this->setJob('CreateProduct');
        $this->BSELLER_URL = $this->product->getUrlBseller($this->store);
    }

    //php index.php BatchC/Integration/Bseller/Product/CreateProduct run null 51
    public function run($id = null, $store = null)
    {
        $this->log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $this->log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $this->log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            echo('Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store);
            return;
        }

        $this->log_data('batch', $this->log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Buscando produtos....... \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);
        $this->log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $this->BSELLER_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $this->BSELLER_URL = $credentials->url_bseller;
        }
        try {
            $this->checkAllItensInSistem();
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
        // Recupera os produtos
        try {
            $this->getProducts();
        } catch (Exception $e) {
            print_r($e->getMessage());
        }


        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $this->log_name, 'finish', "I");
        $this->gravaFimJob();
    }
    public function checkAllItensInSistem()
    {
        echo "Iniciando a atualização dos produtos em nossa base da dados.\n";
        $more = [
            "store_id" => $this->store,
        ];
        $products = $this->model_products->getAllProducts($more);
        foreach ($products as $prod) {
            echo "iniciando atualização do produto {$prod['id']} - {$prod['name']}\n";
            $urlEsp = $this->BSELLER_URL . "api/itens/{$prod["product_id_erp"]}?tipoInterface=" . $this->interface;
            $dataEsp = '';
            $dataProduct = $this->sendREST($urlEsp, $dataEsp);
            $dataProduct = json_decode(json_encode($dataProduct));
            if ($dataProduct->httpcode != 200) {
                echo "Erro para buscar a lista de url={$urlEsp}, retorno=" . json_encode($dataProduct) . "\n";
                if ($dataProduct->httpcode != 99) {
                    $this->log_data('batch', $this->log_name, "Erro para buscar a lista de url={$urlEsp}, retorno=" . json_encode($dataProduct), "W");
                }
                throw new Exception("Erro para buscar a lista de url={$urlEsp}, retorno=" . json_encode($dataProduct));
            }
            $dataProduct = json_decode($dataProduct->content);
            if ($prod['name'] != $dataProduct->nome)
                $prod['name'] = $dataProduct->nome;
            if ($prod['description'] != $dataProduct->descricao)
                $prod['description'] = $dataProduct->descricao;
            $this->model_products->update($prod, $prod['id']);
        }
        $total = count($products);
        echo "Finalizando atualização com verificação para o(s) {$total} objeto(s) no banco persistidos.\n";
    }
    private function getMassiveItens()
    {
        $url = $this->BSELLER_URL . 'api/itens/massivo?tipoInterface=' . $this->interface . '&maxRegistros=100';
        $dataProducts = json_decode(json_encode($this->sendREST($url, '')));
        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->httpcode != 99) {
                $this->log_data('batch', $this->log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
            }
            throw new Exception("Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts));
        }
        $prods = json_decode($dataProducts->content);
        return $prods;
    }
    /**
     * Recupera os produtos
     *
     * @return bool
     */
    public function getProducts()
    {

        /* faz o que o job precisa fazer */

        // começando a pegar os produtos para criar

        $prods = $this->getMassiveItens($this->BSELLER_URL);

        $batchNumber = (int) $prods->batchNumber;

        $this->totalProdsCadastrados = 0;
        $this->db->trans_begin();
        foreach ($prods->content as $prod) {
            $this->getProductData($prod);
            $this->db->trans_commit();
        } //foreach
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
        } else {
            $this->db->trans_commit();
        }

        echo "\n----------------";
        echo "\nTotal de produtos cadastrados = {$this->totalProdsCadastrados}\n";

        //limpa a lista
        if ($batchNumber > 0) {
            $url = $this->BSELLER_URL . 'api/itens/massivo/' . $batchNumber;
            $dataProducts = json_decode(json_encode($this->sendREST($url, '', 'PUT')));
        }
    }
    public function createProductPai($prod)
    {
        $codigoItemBseller = $prod->codigoItem;
        $id_produto = $prod->codigoTerceiro;
        $urlEsp = $this->BSELLER_URL . "api/itens/$id_produto?tipoInterface=" . $this->interface;
        $dataEsp = '';
        $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

        if ($dataProduct->httpcode != 200) {
            echo "Produto Bseller com ID={$id_produto} encontrou um erro, retorno=" . json_encode($dataProduct) . "\n";
            $this->db->trans_rollback();
            if ($dataProduct->httpcode != 99) {
                $this->log_data('batch', $this->log_name, "Produto Bseller com ID={$id_produto} encontrou um erro, retorno=" . json_encode($dataProduct) . "url: $urlEsp", "W");
                $this->log_integration("Erro para integrar produto - ID Bseller {$id_produto}", "Não foi possível obter informações do produto! <br> <strong>ID Bseller</strong>:{$id_produto}", "E");
            }
            return;
        }
        $codigoItemBseller = $prod->codigoItem;
        $produtoCompleto = json_decode($dataProduct->content);

        // Recupera o código do produto pai
        $skuProduct = $produtoCompleto->codigoFornecedor;
        $verifyProduct = $this->product->getProductForIdErp($codigoItemBseller, $skuProduct);

        $product = json_decode($dataProduct->content);

        // Recupera o código do produto pai
        $idProduct = $product->codigoTerceiro;


        if (empty($verifyProduct)) {
            $this->setUniqueId($id_produto); // define novo unique_id
            echo "Novo produto identificado...\n";
            echo "Buscado dados do produto -> {$id_produto}\n";

            if (isset($imgArray)) {
                $product->anexos = $imgArray;
            }
            $productCreate = $this->product->createProduct($product);
            if (!$productCreate['success']) {
                $this->db->trans_rollback();
                $productIdentifier = "SKU {$product->codigoTerceiro}";
                if (isset($productCreate['message']) && $productCreate['message'] != '') {
                    if (is_array($productCreate['message'])) {
                        $msg = '';
                        foreach ($productCreate['message'] as $key => $value) {
                            $msg .= $value . "\n";
                        }
                        $this->log_data('batch', $this->log_name, $msg, "I");
                        echo $msg . "\n";
                        $this->log_integration("Erro para integrar produto - {$productIdentifier}", $msg . '<br>', "E");
                    } else {
                        $this->log_data('batch', $this->log_name, $productCreate['message'], "I");
                        echo $productCreate['message'] . "\n";
                        $this->log_integration("Erro para integrar produto - {$productIdentifier}", $productCreate['message'] . '<br>', "E");
                    }
                } else {
                    $this->log_data('batch', $this->log_name, 'Existem algumas pendências no cadastro do produto na plataforma de integração <br><strong>ID Bseller</strong>:' . $idProduct, "I");
                    echo 'Existem algumas pendências no cadastro do produto na plataforma de integração <br><strong>ID Bseller</strong>:' . $idProduct . "\n\n";
                    $this->log_integration("Erro para integrar produto - {$productIdentifier}", 'Existem algumas pendências no cadastro do produto na plataforma de integração <br><strong>ID Bseller</strong>:' . $idProduct . '<br>', "E");
                }
                $created = false;
                return;
            }

            $this->log_data('batch', $this->log_name, "Produto cadastrado!!! payload id = " . $idProduct, "I");
            echo "Produto {$idProduct} cadastrado com sucesso\n\n";
            $this->log_integration("Produto {$skuProduct} integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi criado com sucesso</li></ul><br><strong>ID Tiny</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$product->nome}", "S");
            $this->totalProdsCadastrados++;
            $this->db->trans_commit();
            return;
        } else {
            if (!$this->model_products->productHasIntegration($verifyProduct['id'])) {
                $this->updateProduct($verifyProduct, $product);
            } else {
                echo ("Produto já integrado: " . $verifyProduct['id']);
            }
            return;
        }
    }
    public function updateProduct($verifyProduct, $product)
    {
        echo ("Iniciando atualização para o produto " . $verifyProduct['id'] . ". Na BSELLER como $product->codigoItem\n");
        $imgArray = [];
        $dataUpdate = [];
        if ($product->codigoItemPai == null) {
            echo ("Produto pai\n");
            foreach ($product->imagens as $img) {
                $linkImg = $img->link;
                $imgArray[] = $linkImg;
            }
            if (count($imgArray) > 0) {
                $serverpath = $_SERVER['SCRIPT_FILENAME'];
                $pos = strpos($serverpath, 'assets');
                $serverpath = substr($serverpath, 0, $pos);
                $targetDir = $serverpath . 'assets/images/product_image/' . $verifyProduct["image"] . '/';

                $dir_verify = trim($targetDir); 
                $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13); 						  
                if ($last_path_delete =='product_image') {
                    log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
                    die; 
                }

                $files = glob($targetDir . "/*");
                foreach ($files as $file) {
                    if (!is_dir($file)) {
                        log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' unlink '.$file);
                        unlink($file);
                    }
                }
                
                $product->anexos = $imgArray;
                $images = $this->product->getImages($product->anexos, $targetDir);
                if (!$images['success']) {
                    // encontrou erro no upload de imagem
                    return array('success' => false, 'message' => '1 ou mais imagem do produto inválida');
                }
            }

            $dataUpdate["name"] = $product->nome;
            $dataUpdate["description"] = $product->descricao;
            $dataUpdate["price"] = $this->getPrecoOnArray($product->preco);
            $dataUpdate["list_price"] = $this->getPrecoDeOnArray($product->preco);
            $dataUpdate["status"] = $product->ativo ? 1 : 2;
            if (!empty($product->variacoes)) {
                $this->createVariationForItem($product, $verifyProduct, $product);
            }
            $this->cadastraVariacaoFilhos($product, $verifyProduct);
            $this->model_products->update($dataUpdate, $verifyProduct['id']);
            $this->model_products->disableVariationForIdErpOutProduct($product->codigoItem, $verifyProduct['id']);
            // $this->model_products->VariationForIdErpOutProduct($product->codigoItem, $verifyProduct['id']);
            $this->db->trans_commit();
        } else {
            echo ("Produto filho\n");
            $id_produto = $product->codigoItemPai;
            $urlEsp = $this->BSELLER_URL . "api/itens/$id_produto?tipoInterface=" . $this->interface;
            $dataEsp = '';
            $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

            if ($dataProduct->httpcode != 200) {
                echo "Produto Bseller com ID={$id_produto} encontrou um erro, retorno=" . json_encode($dataProduct) . "\n";
                $this->db->trans_rollback();
                if ($dataProduct->httpcode != 99) {
                    $this->log_data('batch', $this->log_name, "Produto Bseller com ID={$id_produto} encontrou um erro, retorno=" . json_encode($dataProduct) . "url: $urlEsp", "W");
                    $this->log_integration("Erro para integrar produto - ID Bseller {$id_produto}", "Não foi possível obter informações do produto! <br> <strong>ID Bseller</strong>:{$id_produto}", "E");
                }
                return;
            }
            $codigoItemBseller = $product->codigoItemPai;
            $produtoCompleto = json_decode($dataProduct->content);

            // Recupera o código do produto pai
            $skuProduct = $produtoCompleto->codigoFornecedor;
            $verifyProduct = $this->product->getProductForIdErp($codigoItemBseller, $skuProduct);

            $product = json_decode($dataProduct->content);
            $verifyProduct = $this->product->getProductForIdErp($codigoItemBseller, $skuProduct);
            if ($verifyProduct) {
                $this->updateProduct($verifyProduct, $product);
            }
        }
    }
    public function cadastraVariacaoFilhos($product, $verifyProduct)
    {
        // $verifyProduct

        $urlEsp = $this->BSELLER_URL . "api/itens/" . $product->codigoItem . "/filhos?tipoInterface=" . $this->interface;
        $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, '')));
        $ids_erp = [];
        $ids_erp[] = $product->codigoItem;
        if ($dataProduct->httpcode == 200) {
            $filhos = json_decode($dataProduct->content);
            //-----------------------------------------
            //PERCORRE TODAS AS VARIAÇOES DE CADA FILHO
            //-----------------------------------------
            $hasVariations = array();
            foreach ($filhos as $filho) {
                echo ("Lidando com filho:" . $filho->codigoItem . "\n");
                $this->createVariationForItem($product, $verifyProduct, $filho);
                $ids_erp[] = $filho->codigoItem;
            }
            $variants = $this->model_products->getVariantsByProd_id($verifyProduct['id']);
            foreach ($variants as $variant) {
                if (!in_array($variant['variant_id_erp'], $ids_erp)) {
                    $data = array('status' => 2);
                    $this->model_products->updateVar($data, $variant['prd_id'], $variant['variant']);
                }
            }
            if (!empty($hasVariations)) {
                $this->product->updateHasVariations($verifyProduct['id'], $hasVariations);
            }
        }
    }
    public function createVariationForItem($product, $verifyProduct, $filho)
    {
        $verifyVariation = $this->product->getVariationForPrdIdAndIdErp($verifyProduct['id'], $filho->codigoTerceiro);
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}
        if (!$verifyVariation) {
            foreach ($filho->variacoes as $variacao) {
                if (!array_key_exists($variacao->tipoVariacao, $this->product->tipoVariacoes)) {
                    echo ("O produto sku = " . $verifyProduct['sku'] . ", tem uma variação $variacao->tipoVariacao, não compatível com o sistema...\n");
                    return array('success' => false);
                } else {
                    $variationType = $this->product->tipoVariacoes[$variacao->tipoVariacao];
                }
            }
            //tudo certo, inserir a variacao do filho
            if (!empty($this->getVarName($filho))) {
                $urlEsp = $this->BSELLER_URL . "api/itens/$filho->codigoTerceiro?tipoInterface=" . $this->interface;
                $dataEsp = '';
                $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));
                if ($dataProduct->httpcode == 200) {
                    $produtoFilho = json_decode($dataProduct->content);
                    $eanUnicoFilho = '';
                    if (isset($produtoFilho->ean)) {
                        foreach ($produtoFilho->ean as $ean) {
                            if ($ean->preferencial == true || $ean->preferencial == 1) {
                                $eanUnicoFilho = $ean->codEan;
                            }
                        }
                    }
                    $estoqueFilho = $this->product->getEstoqueProduto($filho->codigoTerceiro);
                    if ($estoqueFilho == 0) {
                        // return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variação está na lista!');
                    }
                    $preco = 0;
                    $list_price = 0;
                    // Preço de é obrigatório no BSeller
                    if (!isset($produtoFilho->preco[0]->precoDe)) {
                        echo "Não tem um preço cadastrado. \n";
                        return array('success' => false, 'message' => array('Não tem um preço cadastrado.'));
                    } else {
                        if (isset($produtoFilho->preco[0]->precoDe) && $produtoFilho->preco[0]->precoDe > 0) {
                            $preco = $produtoFilho->preco[0]->precoDe;
                            $list_price = $produtoFilho->preco[0]->precoDe;
                        }
                        if (isset($produtoFilho->preco[0]->precoPor) && $produtoFilho->preco[0]->precoPor > 0) {
                            $preco = $produtoFilho->preco[0]->precoPor;
                        }
                    }

                    //--------------------------------------------------
                    // upload de imagens, so é permitido 6 imagens;
                    $imgArray = array();
                    $qtdImage = 0;
                    foreach ($produtoFilho->imagens as $img) {
                        if ($qtdImage >= $limite_imagens_aceitas_api) {
                            continue;
                        }
                        $linkImg = $img->link;
                        $imgArray[] = $linkImg;
                        $qtdImage++;
                    }


                    $pathFilho = $this->product->getPathNewImage($verifyProduct['image']); // folder que receberá as imagens

                    if (count($imgArray) > 0) {
                        $produtoFilho->anexos = $imgArray;

                        // Faz upload e recupera os códigos
                        $images = $this->product->getImages($produtoFilho->anexos, $pathFilho['path_complet']);
                        if (!$images['success']) {
                            // encontrou erro no upload de imagem
                            return array('success' => false, 'message' => '1 ou mais imagem do produto inválida');
                        }
                    }
                    $variants = $this->model_products->getVariantsByProd_id($verifyProduct['id']);
                    $estoqueFilho = $this->product->getEstoqueProduto($product->codigoTerceiro);
                    $field_format = $this->verifyFieldsProduct('sku', 'VAR' . $produtoFilho->codigoFornecedor, true, 'S');
                    if(!$field_format[0]){
                        return array('success' => $field_format[0], 'message' => $field_format[1]);
                    }
                    $newVariation = array(
                        'prd_id' => $verifyProduct['id'] ?? 0,
                        'variant' => count($variants),
                        'name' => $this->getVarName($filho),
                        'sku' => 'VAR' . $produtoFilho->codigoFornecedor ?? '',
                        'variant_id_erp' => $produtoFilho->codigoTerceiro ?? 0,
                        'image' => (isset($pathFilho['path_product'])) ? $pathFilho['path_product'] : '',
                        'status' => $produtoFilho->vendavel ? 1 : 2,
                        'EAN' => $eanUnicoFilho,
                        'codigo_do_fabricante' => $produtoFilho->codigoFornecedor,
                        'qty' => $estoqueFilho,
                        'price' => $preco,
                        'list_price' => $list_price
                    );
                    // print_r($newVariation);
                    $this->model_products->createvar($newVariation);
                    $this->model_products->disableProductByIdErp($newVariation['variant_id_erp']);
                    //atualiza o estoque o produto pai
                    $this->model_products->updateEstoqueProdutoPai($verifyProduct['id']);
                    $verifyProduct = $this->product->getProductForIdErp($filho->codigoItem);
                    if ($verifyProduct) {
                        if ($verifyProduct['product_id_erp'] != $product->codigoItem) {
                            $this->model_products->update(array('status' => 2), $verifyProduct['id']);
                        }
                    }
                }
                $this->model_products->disableVariationForIdErpOutProduct($filho->codigoItem, $verifyProduct['id']);
                $verifyProduct = $this->product->getProductForIdErp($filho->codigoItem);
                if ($verifyProduct) {
                    if ($verifyProduct['product_id_erp'] != $product->codigoItem) {
                        $this->model_products->update(array('status' => 2), $verifyProduct['id']);
                    }
                }
            }
        } else {
            $variants = $this->model_products->getVariantsByProd_id($verifyProduct['id']);
            $estoqueFilho = $this->product->getEstoqueProduto($product->codigoTerceiro);
            if ($estoqueFilho == 0) {
                // throw new Exception('Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variação está na lista!');
            }
            $newVariation = array(
                'sku' => 'VAR' . $product->codigoFornecedor ?? '',
                'status' => $product->vendavel ? 1 : 2,
                'EAN' => empty($product->ean) ? '' : $this->getEan($product->ean),
                'qty' => $estoqueFilho,
                'price' => $this->getPrecoOnArray($product->preco),
                'list_price' => $this->getPrecoDeOnArray($product->preco)
            );
            echo ("Variacao: " . $verifyVariation['id'] . "\n");
            $this->model_products->updateVar($newVariation, $verifyVariation['prd_id'], $verifyVariation['variant']);
            // $this->model_products->disableProductByIdErp($produtoFilho->codigoTerceiro);
        }
    }
    public function getVarName($filho)
    {
        $variation_name = '';
        foreach ($filho->variacoes as $variacao) {

            $variationType = $this->product->tipoVariacoes[$variacao->tipoVariacao];
            if ($variationType == 'VOLTAGEM') {
                if ($this->product->likeText("%220%", strtolower($variacao->variacao))) {
                    if ($variation_name == '') {
                        $variation_name .= '220V';
                    } else {
                        $variation_name .= ';220V';
                    }
                } else {
                    if ($variation_name == '') {
                        $variation_name .= '110V';
                    } else {
                        $variation_name .= ';110V';
                    }
                }
            } else {
                if ($variation_name == '') {
                    $variation_name .= $variacao->variacao;
                } else {
                    $variation_name .= ';' . $variacao->variacao;
                }
            }
            // if (!in_array($variationType, $hasVariations)) {
            //     $hasVariations[] = $variationType;
            // }
        }
        return $variation_name;
    }
    public function getEan($eans)
    {
        foreach ($eans as $ean) {
            if ($ean->preferencial) {
                return $ean->codEan;
            }
        }
        return $eans[0]->codEan;
    }
    public function getPrecoOnArray($arrayPreco)
    {
        $maior = 0;
        foreach ($arrayPreco as $preco) {
            if ($preco->precoPor > $maior) {
                $maior = $preco->precoPor;
            }
        }
        return $maior;
    }
    public function getPrecoDeOnArray($arrayPreco)
    {
        $maior = 0;
        foreach ($arrayPreco as $preco) {
            if ($preco->precoDe > $maior) {
                $maior = $preco->precoDe;
            }
        }
        return $maior;
    }
    private function getProductData($prod)
    {
        $arrayProductErroCheck = array();
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}
        if ($prod->ativo === true && $prod->codigoItemPai === null) {
            $product = $this->createProductPai($prod);
        } else if ($prod->ativo === true && $prod->codigoItemPai != null) {
            //api/itens/1798199?tipoInterface=CONECTALA
            $urlEsp = $this->BSELLER_URL . "api/itens/$prod->codigoItemPai?tipoInterface=" . $this->interface;
            $dataEsp = '';
            $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));
            if ($dataProduct->httpcode != 200) {
                echo "Produto filho encontrado verifique se o produto $prod->codigoItemPai está vinculado a interface conectala";
                return;
            }

            $codigoItemBseller = $prod->codigoItem;
            $produtoCompleto = json_decode($dataProduct->content);
            // $product = $this->createProductPai($produtoCompleto);
            $this->getProductData($produtoCompleto);
            return;
            // $skuProduct = $produtoCompleto->codigoFornecedor;
            // $verifyProduct = $this->product->getProductForIdErp($codigoItemBseller, $skuProduct);
        }
        if (!$product) {
            return;
        }

        //-----------------------------------------------------------------
        //update
        //-----------------------------------------------------------------
        $hasVariation = false;
        $id_produto = isset($product->codigoTerceiro) ? $product->codigoTerceiro : null;
        $this->setUniqueId($id_produto); // define novo unique_id

        $idProduct = isset($product->codigoTerceiro) ? $product->codigoTerceiro : 0;
        $skuProduct = isset($product->codigoFornecedor) ? $product->codigoFornecedor : 0;
        if (in_array($idProduct, $arrayProductErroCheck)) {
            echo "Já tentou atualizar o ID={$idProduct} e deu erro\n";
            $this->db->trans_rollback();
            return;
        }

        $verifyProduct = $this->product->getProductForSku($skuProduct);

        // existe o sku na loja, mas não esá com o registro do id da bseller
        if (!empty($verifyProduct)) {
            if ($verifyProduct['status'] != 1) {
                echo "Produto não está ativo\n";
                $this->db->trans_rollback();
                return;
            }


            $dirImage = $verifyProduct['image'];

            //atualiza imagens
            if ($this->uploadproducts->countImagesDir($dirImage) == 0) {
                // upload de imagens, so é permitido 6 imagens;
                $imgArray = array();
                $qtdImage = 0;
                foreach ($product->imagens as $img) {
                    if ($qtdImage >= $limite_imagens_aceitas_api) {
                        return;
                    }
                    $linkImg = $img->link;
                    $imgArray[] = $linkImg;
                    $qtdImage++;
                }
                if (count($imgArray) > 0) {
                    $product->anexos = $imgArray;
                }
            }

            if (isset($product->variacoes) && (count($product->variacoes) > 0)) {

                $hasVariation = true;

                $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
                $this->BSELLER_URL = '';
                if ($dataIntegrationStore) {
                    $credentials = json_decode($dataIntegrationStore['credentials']);
                    $this->BSELLER_URL = $credentials->url_bseller;
                }

                $urlEsp = $this->BSELLER_URL . 'api/itens/' . $idProduct . '/filhos?tipoInterface=' . $this->interface;
                $dataEsp = '';
                $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

                if ($dataProduct->httpcode == 200) {
                    $filhos = json_decode($dataProduct->content);

                    foreach ($filhos as $filho) {
                        //pegar os dados completo do produto
                        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
                        $this->BSELLER_URL = '';
                        if ($dataIntegrationStore) {
                            $credentials = json_decode($dataIntegrationStore['credentials']);
                            $this->BSELLER_URL = $credentials->url_bseller;
                        }

                        $codigoItemFilho = $filho->codigoTerceiro;

                        // pegar o estoque de cada filho
                        $urlEsp = $this->BSELLER_URL . 'api/itens/' . $codigoItemFilho . '/estoque?tipoInterface=' . $this->interface;
                        $data = '';
                        $dataProdEstoqueFilho = json_decode(json_encode($this->sendREST($urlEsp, $data)));

                        if ($dataProdEstoqueFilho->httpcode == 200) {

                            $estoqueFilho = json_decode($dataProdEstoqueFilho->content);
                            $stock = $estoqueFilho->estoqueEstabelecimento[0]->quantidade;

                            //Pegar o preço de cada produto filho
                            $url = $this->BSELLER_URL . 'api/itens/' . $codigoItemFilho . 'tipoInterface=' . $this->interface;
                            $data = '';
                            $dataProd = json_decode(json_encode($this->sendREST($url, $data)));

                            $regProd = json_decode($dataProd->content);

                            if ($dataProd->httpcode == 200) {

                                if (empty($regProd->preco[0]->precoPor)) {
                                    $precoProd = $regProd->preco[0]->precoDe;
                                } else {
                                    $precoProd = $regProd->preco[0]->precoPor;
                                }
                                $list_price = $regProd->preco[0]->precoDe;

                                $productUpdate = $this->product->updateVariation($filho, $id_produto, $precoProd, $stock, $list_price);


                                if ($productUpdate['success'] === false) {
                                    echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($productUpdate) . "\n";
                                    $this->db->trans_rollback();
                                    $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID bseller
                                    // adiciono no array para não consultar mais esse produto pai
                                    if (!in_array($idProduct, $arrayProductErroCheck)) {
                                        array_push($arrayProductErroCheck, $idProduct);
                                    }

                                    return;
                                }

                                if ($productUpdate['success'] === null) {
                                    return;
                                }


                                if ($productUpdate['success'] === true) {
                                    $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi atualizado com sucesso</li></ul><br><strong>ID Bseller</strong>: {$product->codigoItem}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Nome do produto</strong>: {$product->nome}", "S");
                                    $this->log_data('batch', $this->log_name, "Produto atualizado!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), "I");
                                    echo "Produto SKU={$skuProduct} atualizado com sucesso\n";
                                }
                            }
                        }
                    }
                }
            }


            $productUpdate = $this->product->updateProduct($product);
            if ($productUpdate['success'] === false) {
                echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($productUpdate) . "\n";
                $this->db->trans_rollback();
                $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID bseller
                // adiciono no array para não consultar mais esse produto pai
                if (!in_array($idProduct, $arrayProductErroCheck)) {
                    array_push($arrayProductErroCheck, $idProduct);
                }

                return;
            }
            if ($productUpdate['success'] === null) {
                return;
            }

            if ($productUpdate['success'] === true) {
                $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi atualizado com sucesso</li></ul><br><strong>ID Bseller</strong>: {$product->codigoItem}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Nome do produto</strong>: {$product->nome}", "S");
                $this->log_data('batch', $this->log_name, "Produto atualizado!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), "I");
                echo "Produto SKU={$skuProduct} atualizado com sucesso\n";
                return;
            }
        } else {
            if ($prod->codigoItemPai > 0) {
                $skuProduct = $prod->codigoItem;
                //verificar se existe uma variação
                $verifyProduct = $this->product->getVariationForIdErp($skuProduct);
                if (!empty($verifyProduct)) {
                    //update variaçao
                    //------------------------------------------------------
                    $item = $this->product->updateVariacao($prod, $verifyProduct);
                    if ($item) {
                        $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$prod->codigoFornecedor}, foi atualizado com sucesso</li></ul><br><strong>ID Bseller</strong>: {$prod->codigoItem}<br><strong>SKU</strong>: {$skuProduct}<br>", "S");
                        $this->log_data('batch', $this->log_name, "Produto atualizado!!! payload=" . json_encode($prod), "I");
                        echo "Produto SKU={$skuProduct} atualizado com sucesso\n";
                        return;
                    }
                    //------------------------------------------------------
                }
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
        }
    }
}
