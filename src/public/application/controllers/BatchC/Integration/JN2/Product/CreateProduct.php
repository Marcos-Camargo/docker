<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration/JN2/Product/CreateProduct run
 *
 ***************-| FINAL DO PROGRAMA |-**************
 * (X) Criar sleep de 1 minuto quando exceder limite da requisições
 * ( ) Existe variação na conecta lá, mas foi excluida no ERP, comparar para remover da conecta lá ?
 *
 */

require APPPATH . "controllers/BatchC/Integration/JN2/Main.php";
require APPPATH . "controllers/BatchC/Integration/JN2/Product/Product.php";

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
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->model('model_integrations');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('CreateProduct');
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

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Buscando produtos....... \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        //busca última x q rodou e gravou na atualização de Estoque do JN2
        $arrDadosJobIntegration = $this->model_integrations->getJobForJobAndStore('CreateProduct', $this->store);
        $dtUpdateJobIntegration = $arrDadosJobIntegration['date_updated'];
        $dtLastRunJobIntegration = $arrDadosJobIntegration['last_run'];

        $dtHoraUltimoJob = $this->product->ajustarDataHoraMenorMaior($dtLastRunJobIntegration, '+178 minutes');  //coloquei 2:58 horas, para buscar 2 minutos antes para não ter problema, pq estava dando diferenca de 1 minuto no meu local com o da jn2 e ainda pode estar rodando.
        //echo "\n L_75_dtHoraUltimoJob: ".$dtHoraUltimoJob;
        
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
        $url = $JN2_URL .'rest/all/V1/products?searchCriteria[filterGroups][0][filters][0][field]=teste_diogo';
        $url .= '&searchCriteria[filterGroups][0][filters][0][value]=1';
        $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';
        $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
        $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
        $url .= '&searchCriteria[filterGroups][2][filters][0][field]=type_id';
        $url .= '&searchCriteria[filterGroups][2][filters][0][value]=configurable,simple';
        $url .= '&searchCriteria[filterGroups][2][filters][0][conditionType]=in';        
        $url .= '&searchCriteria[sortOrders][0][field]=type_id';
        $url .= '&searchCriteria[sortOrders][1][field]=created_at';
        $url .= '&searchCriteria[sortOrders][0][direction]=asc';
        $url .= '&searchCriteria[sortOrders][1][direction]=asc';
        $url .= '&searchCriteria[pageSize]='.$registrosPorPagina;
        
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
            return false;
        }        
        
        $contentProducts = json_decode($dataProducts->content);

        $haveProductList = true;
        $page = 1;

        $totalRegistros = $contentProducts->total_count;
        $pages = ceil(($totalRegistros / $registrosPorPagina));

        $prods = $contentProducts->items;
        $regProducts = $prods;

        $iCountRegistrosTotais = 0;
        $iCountCadastro = 0;
        
        $strVariacoes = null;
        $arrVariacoes = null;
        $intDtHoraUltimoJob = strtotime($dtHoraUltimoJob);
        $prodIdConectala = null;
        
        while ($haveProductList) {
            if ($page != 1) {
                $url = $JN2_URL .'rest/all/V1/products?searchCriteria[filterGroups][0][filters][0][field]=teste_diogo';
                $url .= '&searchCriteria[filterGroups][0][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';
                $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
                $url .= '&searchCriteria[filterGroups][2][filters][0][field]=type_id';
                $url .= '&searchCriteria[filterGroups][2][filters][0][value]=configurable,simple';
                $url .= '&searchCriteria[filterGroups][2][filters][0][conditionType]=in';        
                $url .= '&searchCriteria[sortOrders][0][field]=type_id';
                $url .= '&searchCriteria[sortOrders][1][field]=created_at';
                $url .= '&searchCriteria[sortOrders][0][direction]=asc';
                $url .= '&searchCriteria[sortOrders][1][direction]=asc';
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
                // Inicia transação
                $this->db->trans_begin();
                
                $status             = $registro->status;
                $dtAtualizacaoJn2   = $registro->updated_at;
                $dtCriadoJn2        = $registro->created_at;
                $idProduct          = $registro->id;
                $skuProduct         = $registro->sku;                
                $type_id            = $registro->type_id;
                $price              = null;
                $special_price      = null;
                $arrayFilhosPai     = null;
                $strVariacoes       = null;
                $strTipoVariacoes   = null;
                $prodIdConectala    = null;
                $arrDadosVariacaoPaiFilho = null;
                $arrDadosFilho = null;
                $arrVariacoes = null;
                $strProdImagePai = null;
                $imageFilho = null;
                $strCaminhoImgPai = null;
                
                if ($status != '1') {
                    continue; //produto não está ativo já pulo e não verifico mais nada dele.
                }
                
                // Verifica se já existe product_id_erp e sku
                $verifyProduct = $this->product->getProductForIdErp($idProduct, $skuProduct);
                if (empty($verifyProduct)) {
                    echo "\n\nNovo produto identificado...idProduct: {$idProduct}, skuProduct: {$skuProduct}";               
                } else {
                    $strVariacoes = $verifyProduct['has_variants'];
                    $prodIdConectala = $verifyProduct['id'];
                    $strCaminhoImgPai = $verifyProduct['image'];
                    echo "\n\nProduto {$skuProduct}, prodIdConectala: {$prodIdConectala} já cadastrado..";
                }
                
                //Se o tipo for como simple ou virtual, poderei me basear pra filtro a última alteração da data pq ao alterar altera data hora. Se for configurable, não posso me basear pq não muda ao alterar os filhos.
/*                if ($type_id == "simple") {
                    if ($dtHoraUltimoJob >= $dtCriadoJn2) {
                        //echo "\nL_226_dtHoraUltimoJob: ".$dtHoraUltimoJob." - dtCriadoJn2: ".$dtCriadoJn2;
                        $this->db->trans_rollback();
                        continue;
                    }
                } */
                
                if(($type_id == "simple") && array_key_exists($idProduct, $arrayVariacoesVerifica)){
                    $this->db->trans_rollback();
                    continue;
                } elseif($type_id == "simple"){
                    if (!$this->product->verifySKUAvailable($skuProduct)){
                        $msgErro = "O codigo SKU já está em uso: {$skuProduct}, idProduct: {$idProduct}, não irá cadastrar!";
                        echo "\n{$msgErro}";
                        $this->db->trans_rollback();
                        
                        $this->log_data('batch', $log_name, $msgErro, "W");
                        $this->log_integration("Erro para integrar produto - skuProduct: {$skuProduct}", "Existem algumas pendências no cadastro do produto na plataforma de integração <ul><li>{$msgErro}".
                            "</li></ul> <br> <strong>ID JN2</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$registro->name}", "E");
                        continue;
                    }
                }
                
                $iCountRegistrosTotais++;
                   
                $this->setUniqueId($idProduct); // define novo unique_id
                
                if ($type_id == "configurable") {
                    // verifica filhos, guarda pro no simples não entrar novamente.
                    if(isset($registro->extension_attributes->configurable_product_links)){
                        if (is_array($registro->extension_attributes->configurable_product_links)){
                            // id dos filhos
                            $array_filho = $registro->extension_attributes->configurable_product_links;

                            foreach($array_filho as $id_filho){
                                $arrayVariacoesVerifica[$id_filho] = array($idProduct, $skuProduct);
                                $arrayFilhosPai[$idProduct] = array($id_filho, $skuProduct);
                            }
                        }
                    }
                }                
            

                //Já existe o produto Pai e não tem variações
                if(!empty($prodIdConectala) && empty($strVariacoes))
                {
                    $this->db->trans_rollback();
                    continue;
                } 
                elseif(!empty($arrayFilhosPai[$idProduct])){
                    $arrRetornoPaiFilhos = $this->buscarFilhosDados($skuProduct, $prodIdConectala, $idProduct, $JN2_URL, $log_name, $strVariacoes, true);
                    
                    $arrDadosFilho = isset($arrRetornoPaiFilhos[1]) ? $arrRetornoPaiFilhos[1] : null;
                    $strTipoVariacoes = isset($arrRetornoPaiFilhos[2]) ? $arrRetornoPaiFilhos[2] : null;
                    $msgProblema = null;
                    
                    if(isset($arrRetornoPaiFilhos['success'])){                        
                        if($arrRetornoPaiFilhos['success'] == false){
                            $msgProblema = $arrRetornoPaiFilhos['message'];
                        }
                    }
                    
                    if(isset($arrRetornoPaiFilhos[0]) && $arrRetornoPaiFilhos[0] != null) {
                        $msgProblema = $arrRetornoPaiFilhos[0];
                    }
                    
                    if($msgProblema){
                        echo "\nNão foi possível cadastrar o produto={$idProduct}, skuProduct {$skuProduct} encontrou um erro: " .$msgProblema;

                        if(empty($arrDadosFilho)){
                            $this->db->trans_rollback();  //Se for só esse produto com 1 filho, ou tds deram problema não gravará nada e vai pro próximo.
                        }

                        $this->log_data('batch', $log_name, "Produto PAI JN2 com ID={$idProduct} encontrou um erro, dados_item_lista= {$msgProblema}", "W");
                        $this->log_integration("Erro para integrar produto - skuProduct: {$skuProduct}", "Existem algumas pendências no cadastro do produto na plataforma de integração <ul><li>{$msgProblema}".
                            "</li></ul> <br> <strong>ID JN2</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$registro->name}", "E");
                        if(empty($arrDadosFilho)){
                            continue;   //Se for só esse produto com 1 filho, ou tds deram problema não gravará nada e vai pro próximo.
                        }
                    }
                    
                    if($arrDadosFilho){
                        $arrVariacoes = $arrDadosFilho;
                        
                        //Se houver já produto Pai, no configurável, é pq é so um filho q não inseriu ainda, vai inserir só a Variação (filho).
                        if($prodIdConectala){
                            $productCreateFilho = $this->product->cadastrarVariacoes($arrVariacoes, $prodIdConectala, $idProduct, $skuProduct, true, $strCaminhoImgPai);
                            
                            if (!$productCreateFilho['success']) {
                                $this->db->trans_rollback();
                                $this->log_data('batch', $log_name, "Produto filho JN2 com ID={$idProduct} encontrou um erro, retorno_productCreate=" . json_encode($productCreateFilho), "W");
                                $msgProblema = null;
                                $msgProblema = $productCreateFilho['message'];

                                echo "\nmsgProblema Filho: ".$msgProblema;
                                $this->log_integration("Erro para integrar Produto sku: {$skuProduct}", 
                                    "Existem algumas pendências no cadastro do produto na plataforma de integração <br>".
                                    "<i>{$msgProblema}</i><br>".
                                    "<strong>ID JN2</strong>: {$idProduct} <br>".
                                    "<strong>SKU</strong>: {$skuProduct}", "E");
                                continue;
                            }
                
                            
                            $this->log_data('batch', $log_name, "Variação do produto cadastrado!!! payload id = " . $prodIdConectala . " sku: ".$skuProduct, "I");
                            echo "\nVarição do produto prdIdBanco: {$prodIdConectala}  sku: {$skuProduct}, idProduct: {$idProduct} cadastrado com sucesso";
                            $this->log_integration("Produto sku: {$skuProduct}, integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto sku= {$skuProduct}, foi criado com sucesso</li></ul><br>".
                                "<strong>ID JN2</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$registro->name}", "S");
                            $iCountCadastro++;

                            $this->db->trans_commit();
                            continue;   //vai pro próximo pq já estava inserido o produto pai, inseriu produto Filho novo que faltava.
                        }
                    }

                    //echo "\n Pode cadastra as variações SKU={$skuProduct} ID={$idProduct}";
                }
                
                //Fazer verificação dos tipos de Variações se tds são iguais, tds tem Cor ou Tamanho ou VOLTAGEM ou tds eles., pega dados dos atributos do registro Pai.
                $arrTiposVariacoesValores = $this->product->buscaTiposVariacoesFilho($registro->custom_attributes, $JN2_URL, false);
                
                $dadosRegistroCustom = null;
                $strTiposVariacaoAtual  = isset($arrTiposVariacoesValores[0]) ? $arrTiposVariacoesValores[0] : null;
                $strValoresVariacao     = isset($arrTiposVariacoesValores[1]) ? $arrTiposVariacoesValores[1] : null;
                $dadosRegistroCustom    = $arrTiposVariacoesValores[2];
                
                if($dadosRegistroCustom){                    
                    foreach($dadosRegistroCustom as $keyName => $regAltCustom){
                        $registro->$keyName = $regAltCustom;
                    }
                }
                
                
                //Busca o Preço e preço promocional se existir e estiver entre as datas promocionais
                $price = $this->product->verificarPrecoEPrecoEspecial($registro->custom_attributes, $registro->price);
                $registro->price = $price;

                //busca a quantidade do produto.
                $retQuantidade = $this->product->consultarQuantidadeEstoque($skuProduct, $JN2_URL, $log_name);

                if(is_array($retQuantidade['success'] == false)){
                    $this->log_data('batch', $log_name, "Erro buscar quantidade do produto $skuProduct {$skuProduct} ".$retQuantidade['message'], "W");
                    $this->db->trans_rollback();
                    continue;
                    //return array('success' => false, 'message' => $retQuantidade['message']);
                } else {
                    $quantidade = $retQuantidade;
                    $registro->quantidade = $quantidade;
                }
                
                //Se houver variação e for um simples, será o Pai/ProprioFilho
                if($strTiposVariacaoAtual && $type_id == "simple"){
                                        
                    $arrDadosVariacaoPaiFilho['price'] = $price;
                    $arrDadosVariacaoPaiFilho['qty'] = $quantidade ?? 0;
                    $arrDadosVariacaoPaiFilho['sku'] = $skuProduct.'_0';
                    $arrDadosVariacaoPaiFilho['sku_pai']    = $skuProduct;
                    $arrDadosVariacaoPaiFilho['id_pai']     = $idProduct;
                    $arrDadosVariacaoPaiFilho['name']       = $strValoresVariacao;
                    $arrDadosVariacaoPaiFilho['created_at'] = $dtCriadoJn2;
                    $arrDadosVariacaoPaiFilho['updated_at'] = $registro->updated_at;
                    $arrDadosVariacaoPaiFilho['EAN'] = isset($registro->ean) ? $registro->ean : '';
                    $arrDadosVariacaoPaiFilho['codigo_do_fabricante']    = '';
//                    $arrDadosVariacaoPaiFilho['image_filho']      = $imageFilho;
                    $arrVariacoes[$idProduct] = $arrDadosVariacaoPaiFilho;

                    //Se mudou tipos de variações no simples vai atualizar
                    $registro->has_variants = $strTiposVariacaoAtual;
                } else {
                    if($strTipoVariacoes){
                        $registro->has_variants = $strTipoVariacoes;
                    }
                }
                               
                //Vai passar pro Produto para cadatrar produto Pai e filhos (variações)
                $productCreate = null;
                $productCreate = $this->product->createProduct($registro, $arrVariacoes);
                
                if (!$productCreate['success']) {
/*                    echo "\nL_408_productCreate[success]: ";
                    print_r($productCreate['success']); */
                    
                    $this->db->trans_rollback();
                    $this->log_data('batch', $log_name, "Produto JN2 com ID={$idProduct} encontrou um erro, retorno_productCreate=" . json_encode($productCreate), "W");
                    $msgProblema = null;
                    
                    if(is_array($productCreate['message'])){                       
                        foreach($productCreate['message'] as $msgRetorno){
                            $msgProblema = empty($msgProblema) ? $msgRetorno : $msgProblema."<br> ".$msgRetorno;
                        }
                    } else {
                        $msgProblema = $productCreate['message'];
                    }
                    
                    echo "\nmsgProblema: ".$msgProblema;
                    $this->log_integration("Erro para integrar Produto sku: {$skuProduct}", 
                        "Existem algumas pendências no cadastro do produto na plataforma de integração <br>".
                        "<i>{$msgProblema}</i><br>".
                        "<strong>ID JN2</strong>: {$idProduct} <br>".
                        "<strong>SKU</strong>: {$skuProduct}", "E");
                    continue;
                }
                
                $prdIdBanco = $productCreate['prd_id'];

                $this->log_data('batch', $log_name, "Produto cadastrado!!! payload id = " . $prdIdBanco . " sku: ".$skuProduct, "I");
                echo "\nProduto prdIdBanco: {$prdIdBanco}  sku: {$skuProduct}, idProduct: {$idProduct} cadastrado com sucesso";
                $this->log_integration("Produto sku: {$skuProduct}, integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto sku= {$skuProduct}, foi criado com sucesso</li></ul><br>".
                    "<strong>ID JN2</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$registro->name}", "S");
                $iCountCadastro++;

                $this->db->trans_commit();
            } // fim lista
            
            $page++;

            if($page > $pages){
                $haveProductList = false;
            }
        }
        
        echo "\n----------------";
        echo "\nTotal de produtos cadastrados = {$iCountCadastro}\n";

    }
    
    /* Consulta dados dos filhos (variações)
     *
     * @param   $skuProduct         Sku do produto pai
     * @param   $prodIdConectala    Id do produto no conectalá banco
     * @param   $idProduct          Id do produto pai no erp
     * @param   $JN2_URL            url da consulta endpoint
     * @param   $log_name           caminho do log_name
     * @param   $strVariacoes       tipos de variacões se existir no banco já gravado
     * @param   $bolTemFilho        se tem filho(s) para saber se tem que gravar as imagens dos filhos
     * @return  null|array          Retorna um array com dados da variação ou null caso não encontre
    */
    public function buscarFilhosDados($skuProduct, $prodIdConectala, $idProduct, $JN2_URL, $log_name, $strVariacoes, $bolTemFilho){
        $urlPaiFilhos = $JN2_URL .'rest/all/V1/configurable-products/'.$skuProduct.'/children';
        $data = '';
        $dataProductsTodosFilhos = json_decode(json_encode($this->sendREST($urlPaiFilhos, $data)));

        if ($dataProductsTodosFilhos->httpcode != 200) {
            echo "Erro para buscar a lista de todos filhos url={$urlPaiFilhos}, retorno=" . json_encode($dataProductsTodosFilhos) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a lista de todos filhos url={$urlPaiFilhos}, retorno=" . json_encode($dataProductsTodosFilhos), "W");
            return array('success' => false, 'message' => "Erro para buscar a lista de todos filhos skuProduct={$skuProduct}");
        }

        $contentDadosFilhos = json_decode($dataProductsTodosFilhos->content);
        
        $icountFilho = 0;
        $arrVarFilhos = null;
        $arrDadosVariacao = null;
        $intDtUltimoAtualizacaoFilho = null;
        $countFilhos = count($contentDadosFilhos);
        $msg = null;
        $strTipoVariacoes = null;
        $hasVariationsAnterior = null;
        
        foreach($contentDadosFilhos as $arrDadoFilho){
            $skuFilho = $arrDadoFilho->sku;
            $idJN2 = $arrDadoFilho->id;
            
            if ($arrDadoFilho->status != '1') {
                //Produto filho desativado, não irá cadastrar skuFilho: {$skuFilho}, idJN2: {$idJN2}.";
                $msgEscreveDesativo = "Variação desativada skuFilho: {$skuFilho} do produto skuProduct: {$skuProduct}, não poderá cadastar.\n";
                $msg = !empty($msg) ? $msg."\n".$msgEscreveDesativo : $msgEscreveDesativo;
                
                if($countFilhos == 1 || $icountFilho == $countFilhos){
                    return array('success' => false, 'message' => $msg); //se for único filhos desativo já irá retorar e não cadastrar nada
                } else {
                    $icountFilho++;
                    continue; //produto não está ativo já pulo e não verifico mais nada dele.
                }
            }
            
            $verifySkuFilho = $this->product->getVariationForSku($skuFilho);
            
            if($verifySkuFilho){
                echo "\nJá existe esse sku não irá cadastrar skuFilho: {$skuFilho}, idJN2: {$idJN2}.";
                $msgEscreveSkuExistente = "Variação existe skuFilho: {$skuFilho} do produto skuProduct: {$skuProduct}, não poderá cadastar.";
                $msg = !empty($msg) ? $msg."\n".$msgEscreveSkuExistente : $msgEscreveSkuExistente;
                
                if($countFilhos == 1 || $icountFilho == $countFilhos){
                    return array('success' => false, 'message' => $msg);
                } else {
                    $icountFilho++;
                    continue;
                }
            }
            
            $urlFilho = $JN2_URL .'rest/all/V1/products/'.$skuFilho;
            $data = '';
            $dataProductsFilho = json_decode(json_encode($this->product->_sendREST($urlFilho, $data)));

            if ($dataProductsFilho->httpcode != 200) {
                echo "Erro para buscar a lista de url={$urlFilho}, retorno=" . json_encode($dataProductsFilho) . "\n";
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$urlFilho}, retorno=" . json_encode($dataProductsFilho), "W");
                array('success' => false, 'message' => "Erro para buscar a lista do filho, skuFilho={$skuFilho}");
            }
            
            $arrDadosFilhos = json_decode($dataProductsFilho->content);

            //Fazer verificação dos tipos de Variações se tds são iguais, tds tem Cor ou Tamanho ou VOLTAGEM ou tds eles.
            //$arrTiposVariacoesValores = $this->buscaTiposVariacoesFilho($arrDadosFilhos->custom_attributes, $JN2_URL);
            $arrTiposVariacoesValores = $this->product->buscaTiposVariacoesFilho($arrDadosFilhos->custom_attributes, $JN2_URL, $bolTemFilho);
            
            $strTipoVariacoes = $arrTiposVariacoesValores[0];
            $arrTiposVariacaoAtual  = explode(';', $strTipoVariacoes);
            $strValoresVariacao     = $arrTiposVariacoesValores[1];
            $arrOutrosCamposFilho = isset($arrTiposVariacoesValores[2]) ? $arrTiposVariacoesValores[2] : null;
            $eanJN2 = isset($arrOutrosCamposFilho['ean']) ?  $arrOutrosCamposFilho['ean'] : '';
            $codFabricanteJN2 = '';
            
            $arrImagensFilho = null;
            if(isset($arrDadosFilhos->media_gallery_entries)){
                foreach($arrDadosFilhos->media_gallery_entries as $arrImagens){
                    $arrImagensFilho['image_filho'][] = $arrImagens->file;
                }
            }
            
            $imageFilho = isset($arrImagensFilho['image_filho']) ? $arrImagensFilho['image_filho'] : "";
                        
            if($icountFilho >0 || $strVariacoes){
                if(empty($hasVariationsAnterior) && !empty($strVariacoes)){
                    $hasVariationsAnterior[0] = $strVariacoes;
                }
                
                if($arrTiposVariacaoAtual && $hasVariationsAnterior){                    
                    foreach($arrTiposVariacaoAtual as $strTipoVariacaoAtual){                        
                        if (!in_array($strTipoVariacaoAtual, $hasVariationsAnterior)) {
                            $msg = "A variação do item =".$idJN2." , sku =". $skuFilho." , não é compatível com as variações.";
                            $this->log_data('batch', $log_name, "Não é compatível os tipos de variação com tds filhos, hasVariationsAnterior={$hasVariationsAnterior[0]} -strTipoVariacaoAtual={$strTipoVariacaoAtual}", "W");
                            return array('success' => false, 'message' => "Foram encontradas tipos de variações que não estão cadastradas no produto. ".$msg);
                        }
                    }
                }
            }
            
            $dtCriacaoFilho = $arrDadoFilho->created_at;
            $intDtCriacaoFilho = strtotime($dtCriacaoFilho);

            //Se já rodou e dataHora do endpoint filho e do Pai, for menor que a última dtHoradoJob não precisará cadastar. Se a dtHora do pai for Maior
            //Tem que ficar após a validação dos tipos de variações, senão pode pular e vai inserir o produto Pai, sendo que não é para inserir.
/*            if($intDtHoraUltimoJob >= $intDtCriacaoFilho){
                $icountFilho++;
                continue;  *** Tem q varrer tds filhos pra saber se tds estão ou não ok, se pular pela data não vai validar os tipos de variações.
            } */
            
            //Busca o Preço e preço promocional se existir e estiver entre as datas promocionais
            $price = $this->product->verificarPrecoEPrecoEspecial($arrDadoFilho->custom_attributes, $arrDadoFilho->price);

            //busca a quantidade do produto.
            $retQuantidade = $this->product->consultarQuantidadeEstoque($skuFilho, $JN2_URL, $log_name);
            
            if(is_array($retQuantidade['success'] == false)){
                $this->log_data('batch', $log_name, "Erro buscar quantidade do produto skuFilho {$skuFilho} ".$retQuantidade['message'], "W");
                return array('success' => false, 'message' => $retQuantidade['message']);
            } else {
                $quantidade = $retQuantidade;
            }
            
            if($prodIdConectala){
                $arrDadosVariacao['prd_id'] = $prodIdConectala;
            }
            
            $arrDadosVariacao['price'] = $price;
            $arrDadosVariacao['qty'] = $quantidade;
            $arrDadosVariacao['sku'] = $skuFilho;
            $arrDadosVariacao['sku_pai']    = $skuProduct;
            $arrDadosVariacao['id_pai']     = $idProduct;
            $arrDadosVariacao['name']       = $strValoresVariacao;
            $arrDadosVariacao['created_at'] = $dtCriacaoFilho;
            $arrDadosVariacao['updated_at'] = $arrDadoFilho->updated_at;
            $arrDadosVariacao['EAN'] = $eanJN2;
            $arrDadosVariacao['codigo_do_fabricante']    = $codFabricanteJN2;
            $arrDadosVariacao['image_filho']    = $imageFilho;
            $arrVarFilhos[$idJN2] = $arrDadosVariacao;
                       
            $hasVariationsAnterior = explode(';', $arrTiposVariacoesValores[0]);

            $idFilhoAnterior = $arrDadoFilho->id;            
            $icountFilho++;
        }
        
        $arrReturnFilhos = array($msg, $arrVarFilhos, $strTipoVariacoes);
        
        return $arrReturnFilhos;
    }
}    
    

