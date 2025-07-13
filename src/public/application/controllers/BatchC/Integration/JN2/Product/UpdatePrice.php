<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration/JN2/Product/UpdatePrice run
 *
 */

require APPPATH . "controllers/BatchC/Integration/JN2/Main.php";
require APPPATH . "controllers/BatchC/Integration/JN2/Product/Product.php";

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
        $this->load->model('model_integrations');
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

        //busca última x q rodou e gravou na atualização de Estoque do JN2
        $arrDadosJobIntegration = $this->model_integrations->getJobForJobAndStore('UpdatePrice', $this->store);
        $dtUpdateJobIntegration = $arrDadosJobIntegration['date_updated'];
        $dtLastRunJobIntegration = $arrDadosJobIntegration['last_run'];

        $dtHoraUltimoJob = $this->product->ajustarDataHoraMenorMaior($dtLastRunJobIntegration, '+180 minutes');  //coloquei 3:00 horas, para buscar 0 minutos antes para não ter problema, pq estava dando diferenca de 1 minuto no meu local com o da jn2 e ainda pode estar rodando.
        //echo "\n L_69_dtHoraUltimoJob: ".$dtHoraUltimoJob;
        
        // Grava a última execução
        $this->saveLastRun();
        
        // Recupera os produtos
        $this->getProducts($dtHoraUltimoJob);

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Recupera os produtos
     *
     * @return bool
     */
    public function getProducts($dtHoraUltimoJob)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "E");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }
        
        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $JN2_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $JN2_URL = $credentials->url_jn2;
        }

        // começando a pegar os produtos para atualizar
        $registrosPorPagina = 50;
        //$url = $JN2_URL .'rest/all/V1/products?searchCriteria[pageSize]='.$registrosPorPagina;
        $url = $JN2_URL .'rest/all/V1/products?searchCriteria[filterGroups][0][filters][0][field]=teste_diogo';
        $url .= '&searchCriteria[filterGroups][0][filters][0][value]=1';
        $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';
        $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
        $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
        $url .= '&searchCriteria[sortOrders][0][field]=type_id';
        $url .= '&searchCriteria[sortOrders][0][direction]=asc';
        $url .= '&searchCriteria[pageSize]='.$registrosPorPagina;
        
/*        echo "\nL_117_url: ";
        print_r($url);
        die; */
        
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        $arrayProductErroCheck  = array();

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
            return false;
        }

        $contentProducts = json_decode($dataProducts->content);
       
        $haveProductList = true;
        $arrPriceUpdate  = array();
        $page = 1;

        $totalRegistros = $contentProducts->total_count;
        $pages = ceil(($totalRegistros / $registrosPorPagina));
        //echo '<br>L_118_totalRegistros: '.$totalRegistros.' -registrosPorPagina: '.$registrosPorPagina." -pages: ".$pages."\n";

        $prods = $contentProducts->items;
        $regProducts = $prods;

        $iCountRegistrosTotais = 0;
        $iCountAlterouPreco = 0;
        
        $strVariacoes = null;
        $arrVariacoes = null;
        $intDtHoraUltimoJob = strtotime($dtHoraUltimoJob);

        while ($haveProductList) {
            if ($page != 1) {
                //$url = $JN2_URL .'rest/all/V1/products?'.'searchCriteria[pageSize]='.$registrosPorPagina.'&searchCriteria[currentPage]='.$page;
                $url = $JN2_URL .'rest/all/V1/products?searchCriteria[filterGroups][0][filters][0][field]=teste_diogo';
                $url .= '&searchCriteria[filterGroups][0][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';
                $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
                $url .= '&searchCriteria[sortOrders][0][field]=type_id';
                $url .= '&searchCriteria[sortOrders][0][direction]=asc';
                $url .= '&searchCriteria[pageSize]='.$registrosPorPagina.'&searchCriteria[currentPage]='.$page;
                $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

                if ($dataProducts->httpcode != 200) {
                    echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    if ($dataProducts->httpcode != 99) {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                    } else {
                        $this->log_data('batch', $log_name, "L_133_Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                    }

                    $haveProductList = false;
                    continue;
                }

                $regListProducts = json_decode($dataProducts->content);
                $prods = $regListProducts->items;
                $regProducts = $prods;
            }
            
            foreach ($regProducts as $registro) {
                $status             = $registro->status;
                $dtAtualizacaoJn2   = $registro->updated_at;
                $idProduct          = $registro->id;
                $skuProduct         = $registro->sku;                
                $type_id            = $registro->type_id;
                $price              = null;
                $special_price      = null;
                
                if ($status != '1') {
                    continue; //produto não está ativo já pulo e não verifico mais nada dele.
                }
                
                //echo "\n\nL_195_idProduct: ".$idProduct." -skuProduct: ".$skuProduct." -dtAtualizacaoJn2: ".$dtAtualizacaoJn2;
                $verifyProduct = $this->product->getProductForSku($skuProduct);
                
                if(!$verifyProduct){
                    //echo "\nProduto não encontrado, não será possível atualizar o produto ID_JN2={$idProduct}, SKU={$skuProduct}";
                    continue;
                }
                
                //Se o tipo for como simple ou virtual, poderei me basear pra filtro a última alteração da data pq ao alterar altera data hora. Se for configurable, não posso me basear pq não muda ao alterar os filhos.
                if ($type_id == "simple") {
                    //echo "\nL_210_dtHoraUltimoJob: ".$dtHoraUltimoJob." - dtAtualizacaoJn2: ".$dtAtualizacaoJn2 ." -idProduct: ".$idProduct;
                    if ($dtHoraUltimoJob >= $dtAtualizacaoJn2) {
                        //echo "\nProduto não precisa ser atualizado, sku: {$skuProduct}, idProduct: {$idProduct}, data atualizacao jn2 menor: {$dtAtualizacaoJn2}, conectala job: {$dtHoraUltimoJob}";
                        //$this->log_data('batch', $log_name, "Produto sku: {$skuProduct}, idProduct: {$idProduct} não precisa ser atualizado, data atualizacao jn2 menor: {$dtAtualizacaoJn2}, conectala job: {$dtHoraUltimoJob}", "I");
                        continue;
                    }
                }
                
                if(($type_id == "simple") && array_key_exists($idProduct, $arrayVariacoesVerifica)){
                    //Se id do produto filho, tiver aqui é porque é um filho de um produto Pai, que já passou antes no Configurável.
                    //echo "\n L_221 produtoFilho não precisa entrar pois vai passar no pai_idProduct: ".$idProduct." -sku: ".$skuProduct;
                    continue;
                }
                
                $iCountRegistrosTotais++;
                
                   
                $this->setUniqueId($idProduct); // define novo unique_id
                $strVariacoes = $verifyProduct['has_variants'];
                $prodIdConectala = $verifyProduct['id'];

                //Vai pegar somente os produtos sem variações, se houver irá adicionar num array para percorrer depois.
                if(!empty($verifyProduct) && empty($strVariacoes))
                {
                    $price = $this->product->verificarPrecoEPrecoEspecial($registro->custom_attributes, $registro->price);

                    // Inicia transação
                    $this->db->trans_begin();

                    if ($price == 0) {
                        $this->db->trans_rollback();
                        echo "produto {$skuProduct} com preço de venda zerado, preço={$price}\n";
                        $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", 
                            "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>Valor de venda igual a R$0,00 é preciso informar um valor maior que zero.</li></ul>"
                             ."<strong>SKU</strong>: {$skuProduct}<br><strong>ID jn2</strong>: {$idProduct}", "W");
                        continue;
                    }

                    $verifyProduct['price'] = number_format($verifyProduct['price'], 2, ".", "");
                    
                    // produto com o mesmo preço, não será atualizado
                    if ($verifyProduct['price'] == $price) {
                        $this->db->trans_rollback();
                        //echo "\n produto {$skuProduct} com o mesmo preço, não será atualizado, preço={$price}\n";
                        //$this->log_data('batch', $log_name, "produto {$skuProduct} com o mesmo preço, não será atualizado, preço={$price}", "I");
                        continue;
                    }

                    $productUpdate = $this->product->updatePricePai($skuProduct, $price, $dtAtualizacaoJn2);
                    
                    if (!$productUpdate) {
                        $this->db->trans_rollback();
                        echo "\nNão foi possível atualizar o preço do produto={$idProduct}, sku={$skuProduct} encontrou um erro";
                        $this->log_data('batch', $log_name, "Não foi possível atualizar o preço do produto={$idProduct}, sku={$skuProduct} encontrou um erro, productUpdate: {$productUpdate}.", "W");
                        $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul>"
                            ."<li>SKU: {$skuProduct}</li><li>ID jn2: {$idProduct}</li></ul>", "E");
                        continue;
                    }

                    $this->db->trans_commit();

                    $this->log_data('batch', $log_name, "Produto atualizado preço!!! SKU={$skuProduct} preco_anterior={$verifyProduct['price']} novo_preco={$price} ID={$idProduct}", "I");

                    echo "\nAtualizado preço com sucesso SKU={$skuProduct}, preco_anterior={$verifyProduct['price']} novo_preco={$price} ID={$idProduct}";
                    $this->log_integration("Preço do produto {$skuProduct} atualizado", "<h4>O preço do produto {$skuProduct} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$price}", "S");
                    $iCountAlterouPreco++;
                }
                else 
                { // produto ainda não cadastrado, na prod pelo sku, vai verificar por ser variação.
                    $arrDadosVariacao = null;
                    
                    if($type_id == "configurable"){
                        // verifica filhos, guarda pro no simples ou virtual poder achar o IdProdutPai
                        if(isset($registro->extension_attributes->configurable_product_links)){
                            if (is_array($registro->extension_attributes->configurable_product_links)){
                                // id dos filhos
                                $array_filho = $registro->extension_attributes->configurable_product_links;

/*                                echo "\nL_282_arrVariacoes_antes: ";
                                print_r($arrVariacoes); */
                                
                                foreach($array_filho as $id_filho){
                                    $arrayVariacoesVerifica[$id_filho] = array($prodIdConectala, $idProduct, $skuProduct);
                                }
                                
                                //public function buscarFilhosDados($skuProduct, $prodIdConectala, $idProduct, $JN2_URL, $log_name, $intDtHoraUltimoJob){
                                $arrRetornoPaiFilhos = $this->buscarFilhosDados($skuProduct, $prodIdConectala, $idProduct, $JN2_URL, $log_name, $intDtHoraUltimoJob);

                                if($arrRetornoPaiFilhos){
                                    $arrVariacoes[$idProduct] = $arrRetornoPaiFilhos;
                                }

/*                                echo "\nL_294_arrVariacoes_apos: ";
                                print_r($arrVariacoes); */
                                //die;
                            }
                        }
                    } else {
                        //Vai armazenar os dados tendo variações para depois varrer e gravar dados
                        $arrConsultaVariacao = $this->product->getVariationForIdErp($idProduct);
                        
                        $price = $this->product->verificarPrecoEPrecoEspecial($registro->custom_attributes, $registro->price);
                        $price = number_format($price, 2, ".", "");
                        
                        if($arrConsultaVariacao){
                            $priceBanco = $arrConsultaVariacao['price'];
                        } else {
                            if($strVariacoes){
                                $priceBanco   = $verifyProduct['price'];
                            } else {
                                continue;  //não existe no banco irá pular por ser um produto novo ou não importado.
                            }
                        }
                        
                        $priceBanco = number_format($priceBanco, 2, ".", "");

                        //echo "\nL_315_priceBanco: ".$priceBanco." -price: ".$price." -idProduct: ".$idProduct;
                        // produto com o mesmo preço, não será atualizado
                        if ($priceBanco == $price) {
                            //echo "\n Produto {$skuProduct} com o mesmo preço, não será atualizado, preço={$price} , priceBanco={$priceBanco}\n";
                            //$this->log_data('batch', $log_name, "produto {$skuProduct} com o mesmo preço, não será atualizado, preço={$price}, priceBanco={$priceBanco}", "I");
                            continue;
                        }

                        if($arrConsultaVariacao){
                            $idPai = $arrConsultaVariacao['product_id_erp'];
                            //echo "\nL_315_idPai: ".$idPai." -idProduct: ".$idProduct." -skuProduct: ".$skuProduct;
                            $arrDadosVariacao['price'] = $priceBanco;
                            $arrDadosVariacao['sku'] = $skuProduct;
                            $arrDadosVariacao['preco_jn2'] = $price;    //preço Endponint
                            $arrDadosVariacao['sku_pai']    = $arrConsultaVariacao['sku_pai'];
                            $arrDadosVariacao['id_pai']      = $idPai;
                            $arrDadosVariacao['dt_update_jn2'] = $dtAtualizacaoJn2;
                            $arrVariacoes[$idPai][$idProduct] = $arrDadosVariacao;
                        }  
                    }
                }

            } // fim lista
            
            $page++;

            if($page > $pages){
                $haveProductList = false;
            }
        }
        
/*        echo "\n L_367_arrVariacoes: ";
        print_r($arrVariacoes); */
        //die;
        
        
        if($arrVariacoes){
            $maiorPreco = null;
            
            foreach($arrVariacoes as $keyPai => $arrDadosFilho){   
                $iFilho = 1;
                $countFilhos = count($arrDadosFilho);
                                    
                $this->db->trans_begin();
                
                foreach($arrDadosFilho as $keyFilho => $dadosFilho){
                    $idPai  = null;
                    $skuPai = null;

                    if($keyPai == $keyFilho && $countFilhos == 1) {
                        $skuProduct     = $dadosFilho['sku'].'_0';  //proprio registro pai vai ser o filho, por não ter novo registro na JN2, controlamos pelo _0 pra diferenciar o sku único.
                    } else {
                        $skuProduct     = $dadosFilho['sku'];
                    }

                    $idProduct          = $keyFilho;
                    $precoJN2           = $dadosFilho['preco_jn2'];
                    $dtAtualizacaoJn2   = $dadosFilho['dt_update_jn2'];
                    $priceProduct       = $dadosFilho['price'];
                    $idPai              = $dadosFilho['id_pai'];
                    $skuPai             = $dadosFilho['sku_pai'];
                    
                    $this->setUniqueId($idProduct); // define novo unique_id, pro log_integration
                    
                    if($countFilhos == 1){
                        $productUpdate = $this->product->updatePrice($skuProduct, $precoJN2, $dtAtualizacaoJn2, $skuPai);
                    } else {
                        if($keyPai == $keyFilho){
                            if ($iFilho != $countFilhos){
                                $iFilho++; //pula se for o próprio pai, pq após irá varrer os filhos para buscar o maiorPreço e atualiza-lo;
                                continue;
                            }
                        } else {
                            $productUpdate = $this->product->updatePrice($skuProduct, $precoJN2, $dtAtualizacaoJn2, null);
                        }
                    }
                    
                    if ($iFilho == $countFilhos){
                        //vai verificar tds registro pelo produto pai, seus filos para ver o maior no banco.
                        $maiorPreco = $this->product->maiorPrecoVariacaoProdutoPai($this->store, $idPai);
                        
                        $this->product->updatePricePai($skuPai, $maiorPreco, $dtAtualizacaoJn2);
                    }
                                       
                    if (!$productUpdate) {
                        $this->db->trans_rollback();
                        echo "\nNão foi possível atualizar o preço do produto={$idProduct}, sku={$skuProduct}, skuPai={$skuPai} encontrou um erro";
                        $this->log_data('batch', $log_name, "Não foi possível atualizar o preço do produto={$idProduct}, sku={$skuProduct}, skuPai={$skuPai} encontrou um erro,", "W");
                        $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul>"
                            ."<li>SKU: {$skuProduct}</li><li>ID jn2: {$idProduct}</li> <li>skuPai={$skuPai}</li></ul>", "E");
                        continue;
                    }

                    if($countFilhos == 1 || $iFilho == $countFilhos){
                        $this->db->trans_commit();
                    }
                    
                    $this->log_data('batch', $log_name, "Produto atualizado preço!!! SKU={$skuProduct} skuPai={$skuPai} preco_anterior={$priceProduct} novo_preco={$precoJN2} ID={$idProduct}", "I");

                    echo "\nAtualizado preço com sucesso SKU={$skuProduct},  skuPai={$skuPai}, preco_anterior={$priceProduct} novo_preco={$precoJN2} ID={$idProduct}";
                    $this->log_integration("Preço do produto {$skuProduct} atualizado", "<h4>O preço do produto {$skuProduct}, skuPai={$skuPai} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$priceProduct} <br> <strong>Novo preço</strong>:{$precoJN2}", "S");
                    $iCountAlterouPreco++;
                    $iFilho++;
                }
            }
        }
        
        echo "\n Quantidade total: ".$iCountRegistrosTotais;
        echo "\n Alterou o preço de: ".$iCountAlterouPreco." produtos. \n";
    }

    public function buscarFilhosDados($skuProduct, $prodIdConectala, $idProduct, $JN2_URL, $log_name, $intDtHoraUltimoJob){
        //busca o pai e vai varrer os filhos para ver os dados e alteração do preço
        $urlPaiFilhos = $JN2_URL .'rest/all/V1/configurable-products/'.$skuProduct.'/children';
        //echo "\nL_439_buscaPaiFilhos: ".$urlPaiFilhos;
        $data = '';
        $dataProductsTodosFilhos = json_decode(json_encode($this->product->_sendREST($urlPaiFilhos, $data)));

        if ($dataProductsTodosFilhos->httpcode != 200) {
            echo "Erro para buscar a lista de todos filhos url={$urlPaiFilhos}, retorno=" . json_encode($dataProductsTodosFilhos) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a lista de todos filhos url={$urlPaiFilhos}, retorno=" . json_encode($dataProductsTodosFilhos), "W");
            array('success' => false, 'message' => "Erro para buscar a lista de todos filhos skuProduct={$skuProduct}");
        }

        $contentDadosFilhos = json_decode($dataProductsTodosFilhos->content);
        
        $icountFilho = 0;
        $arrConfFilhos = null;
        $intDtUltimoAtualizacaoFilho = null;
        
        foreach($contentDadosFilhos as $arrDadoFilho){
            if ($arrDadoFilho->status != '1') {
                //echo "\nL_468_id: ".$arrDadoFilho->id." -arrDadoFilho->sku: ".$arrDadoFilho->sku." - skuProduct: ".$skuProduct;
                continue; //produto não está ativo já pulo e não verifico mais nada dele.
            }
                
            $skuFilho = $arrDadoFilho->sku;
            $idJN2 = $arrDadoFilho->id;
            $dtUltimoAtualizacaoFilho = $arrDadoFilho->updated_at;
            $intDtUltimoAtualizacaoFilho = strtotime($dtUltimoAtualizacaoFilho);

            //Se já rodou e dataHora do endpoint filho e do Pai, for menor que a última dtHoradoJob não precisará atualizar. Se a dtHora do pai for Maior
            if($intDtHoraUltimoJob >= $intDtUltimoAtualizacaoFilho){
                continue;
            }
            
            $price = $this->product->verificarPrecoEPrecoEspecial($arrDadoFilho->custom_attributes, $arrDadoFilho->price);
            
            //busca pelo varinta_id_erp e prd_id, porque pode ter produto filho para outros pais.
            $arrBancoFilho = $this->product->getVariationForIdErpPrdId($idJN2, $prodIdConectala);

            if($arrBancoFilho){
                $priceBanco   = number_format($arrBancoFilho['price'], 2, ".", "");
                //number_format($priceEndPoint, 2, ".", "");
                
                //echo "\nL_475_priceBanco: ".$priceBanco." -price: ".$price;
                // produto com o mesmo preço, não será atualizado
                if ($priceBanco == $price) {
                    //echo "\n Produto {$skuProduct}, idJN2={$idJN2}, prodIdConectala={$prodIdConectala} com o mesmo preço, não será atualizado, preço={$price} , priceBanco={$priceBanco}\n";
                    //$this->log_data('batch', $log_name, "produto {$skuProduct} com o mesmo preço, não será atualizado, preço={$price}, priceBanco={$priceBanco}", "I");
                    continue;
                } else {
                    $arrDadosVariacao['price'] = $priceBanco;
                    $arrDadosVariacao['sku'] = $skuFilho;
                    $arrDadosVariacao['preco_jn2']    = $price; //preço Endponint
                    $arrDadosVariacao['sku_pai']    = $skuProduct;
                    $arrDadosVariacao['id_pai']     = $idProduct;
                    $arrDadosVariacao['dt_update_jn2'] = $dtUltimoAtualizacaoFilho;
                    $arrConfFilhos[$idJN2] = $arrDadosVariacao;
                }
            } else {
                continue;  //não existe no banco irá pular por ser um produto novo ou não importado.
            }
                
            $icountFilho++;
        }
                
        return $arrConfFilhos;
    }
    
    
    
}