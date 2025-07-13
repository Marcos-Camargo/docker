<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/JN2/Product/UpdateProducts run
 *
 */

require APPPATH . "controllers/BatchC/Integration/JN2/Main.php";
require APPPATH . "controllers/BatchC/Integration/JN2/Product/Product.php";

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
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->model('model_integrations');
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
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Atualizando lista de produtos \n";
        
        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);
        
        //busca última x q rodou e gravou na atualização de Estoque do JN2
        $arrDadosJobIntegration = $this->model_integrations->getJobForJobAndStore('UpdateProduct', $this->store);
        $dtUpdateJobIntegration = $arrDadosJobIntegration['date_updated'];
        $dtLastRunJobIntegration = $arrDadosJobIntegration['last_run'];

        $dtHoraUltimoJob = $this->product->ajustarDataHoraMenorMaior($dtLastRunJobIntegration, '+179 minutes');  //coloquei 2:59 horas, para buscar 1 minutos antes para não ter problema, pq estava dando diferenca de 1 minuto no meu local com o da jn2 e ainda pode estar rodando.
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
     * Recupera os produtos para atualizar
     *
     * @return bool
     */
    public function getProducts($dtHoraUltimoJob)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }
        
        $intDtHoraUltimoJob = strtotime($dtHoraUltimoJob);
        
        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $JN2_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $JN2_URL = $credentials->url_jn2;
        }
        /* faz o que o job precisa fazer */
      
        // começando a pegar os produtos para atualizar
        $registrosPorPagina = 50;
        $url = $JN2_URL .'rest/all/V1/products?searchCriteria[filterGroups][0][filters][0][field]=teste_diogo';
        $url .= '&searchCriteria[filterGroups][0][filters][0][value]=1';
        $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
        $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
        $url .= '&searchCriteria[filterGroups][2][filters][0][field]=type_id&searchCriteria[filterGroups][2][filters][0][value]=configurable,simple';
        $url .= '&searchCriteria[filterGroups][2][filters][0][conditionType]=in';
        $url .= '&searchCriteria[sortOrders][0][field]=type_id&searchCriteria[sortOrders][0][direction]=asc';
        $url .= '&searchCriteria[pageSize]='.$registrosPorPagina;
        $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';
        
        $data = '';
        $dataProducts = json_decode(json_encode($this->product->_sendREST($url, $data)));
               
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
        $iCountAlteroProduto = 0;
        $iCountDesvinculoProduto = 0;
        
        $strVariacoes = null;
        $arrVariacoes = null;
        $variacao = false;
        
        $arrayProductErroCheck = array();
        $totalProdsAtualizados = 0;
        $arrayVariacoesVerifica = array();

        while ($haveProductList) {
            if ($page != 1) {
                $url = $JN2_URL .'rest/all/V1/products?searchCriteria[filterGroups][0][filters][0][field]=teste_diogo';
                $url .= '&searchCriteria[filterGroups][0][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
                $url .= '&searchCriteria[filterGroups][2][filters][0][field]=type_id&searchCriteria[filterGroups][2][filters][0][value]=configurable,simple';
                $url .= '&searchCriteria[filterGroups][2][filters][0][conditionType]=in';
                $url .= '&searchCriteria[sortOrders][0][field]=type_id&searchCriteria[sortOrders][0][direction]=asc';               
                $url .= '&searchCriteria[pageSize]='.$registrosPorPagina.'&searchCriteria[currentPage]='.$page;
                $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';                
                
                $dataProducts = json_decode(json_encode($this->product->_sendREST($url, $data)));

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

/*            echo "\n $regProducts: ";
            print_r($regProducts); */
            $price = null;
            $quantidade = null;

            foreach ($regProducts as $registro) {
                $idProduct  = $registro->id;
                $skuProduct = $registro->sku;
                $status     = $registro->status;
                $type_id    = $registro->type_id;
                $dtAtualizacaoJn2   = $registro->updated_at;
                $intDtAtualizacaoJn2 = strtotime($dtAtualizacaoJn2);
                $arrayFilhosPai = null;
                $arrImagensVerifica = null;
                $imgFilhoProprioPai = null;
                $strCaminhoPai = null;
                $arrFilhoPai = null;
                $msgAtualiza = null;
                
                if ($status != '1') {
                    continue; //produto não está ativo já pulo e não verifico mais nada dele.
                }
                
                //echo "\nL_201_idProduct: ".$idProduct." -skuProduct: ".$skuProduct." -dtAtualizacaoJn2: ".$dtAtualizacaoJn2;
                $verifyProduct = $this->product->getProductForSku($skuProduct);
                
                if(!$verifyProduct){
                    //echo "\nProduto não encontrado, não será possível atualizar o produto ID_JN2={$idProduct}, SKU={$skuProduct}";
                    continue;
                }
                
                //Se o tipo for como simple, poderei me basear pra filtro a última alteração da data pq ao alterar altera data hora. Se for configurable, não posso me basear pq não muda ao alterar os filhos.
                if ($type_id == "simple") {
                    if ($intDtHoraUltimoJob >= $intDtAtualizacaoJn2) {
                        continue;
                    }
                }
                    
                if(($type_id == "simple" || $type_id == "virtual") && array_key_exists($idProduct, $arrayVariacoesVerifica)){
                    //Se id do produto filho, tiver aqui é porque é um filho de um produto Pai, que já passou antes no Configurável.
                    //echo "\n L_221 produtoFilho não precisa entrar pois vai passar no pai_idProduct: ".$idProduct." -sku: ".$skuProduct;
                    continue;
                }
                
                $iCountRegistrosTotais++;
                $this->setUniqueId($idProduct); // define novo unique_id
                $strVariacoes = $verifyProduct['has_variants'];
                    
                // não poderá mais ser atualizado se já foi integrado.
                if ($this->product->getPrdIntegration($verifyProduct['id'])){
                    echo "\nProduto {$skuProduct}, não pode mais receber novas variações pois já está integrado com o marketplace.";
                    continue;
                }
                
                // verifica filhos, guarda pro no simples não entrar novamente.
                if(isset($registro->extension_attributes->configurable_product_links)){
                    if (is_array($registro->extension_attributes->configurable_product_links)){
                        // id dos filhos
                        $array_filho = $registro->extension_attributes->configurable_product_links;

                        foreach($array_filho as $id_filho){
                            $arrayVariacoesVerifica[$id_filho] = array($idProduct, $skuProduct);
                            $arrayFilhosPai[$id_filho] = array($idProduct, $skuProduct);
                        }
                    }
                }
                

                $dirImage = $verifyProduct['image'];
                
                //atualiza imagens
                if ($this->countImagesDir($dirImage) == 0){
                    if(isset($registro->media_gallery_entries) && count($registro->media_gallery_entries) >0){
                        //busca endpoint de imagens
                        $urlImagens = 'https://beta1.boostcommerce.com.br/media/catalog/product';

                        foreach($registro->media_gallery_entries as $arrImagens){
                            $arrImagensVerifica[] = $urlImagens.$arrImagens->file;
                            $arrFilhoPai[] = $arrImagens->file;
                        }
                        
                        if(isset($registro->media_gallery_entries[0]->file) && $registro->type_id == "simple"){
                            $imgFilhoProprioPai = $arrFilhoPai;
                        }

                        if($arrImagensVerifica){
                            $registro->anexos = $arrImagensVerifica;

                            $arrCaminhoImagens = array('path_product' => $dirImage,
                                                        'path_complet' => 'assets/images/product_image/'.$dirImage);

                            $registro->path_images = $arrCaminhoImagens;
                            $strCaminhoPai = $dirImage;

                            if (count($arrImagensVerifica)>6) {
                                $this->log_integration("Erro para integrar produto - SKU {$skuProduct} ", 
                                    'Produto chegou com mais imagens que o permitido <br><strong>ID JN2</strong>:'. $idProduct. '<br>', "E");
                            }
                        }
                    } else {
                        if($dirImage){
                            $arrCaminhoImagens = array('path_product' => $dirImage,
                                                       'path_complet' => 'assets/images/product_image/'.$dirImage);
                            $strCaminhoPai = $dirImage;
                            $registro->path_images = $arrCaminhoImagens;
                        }
                    }
                } else {
                    if($dirImage){
                        $arrCaminhoImagens = array('path_product' => $dirImage,
                                                   'path_complet' => 'assets/images/product_image/'.$dirImage);
                        $strCaminhoPai = $dirImage;
                        $registro->path_images = $arrCaminhoImagens;
                    }
                }
                
                
                $arrAtributosCamposBuscar = null;
                $altura = null;
                $comprimento = null;
                $largura = null;
                $ean = '';
                $tamanhoCod = null;
                $fabricanteCod = null;
                $manufacturer = null;  //fabricante
                $hasVariantAtualiza = null;
                $categorias = '[]';
                $prazo_operacional = null;
                $descricao = '';
                $largura = null;
                $ncm = null;
                $garantia = 0;

                $variacao = false;
                foreach ($registro->custom_attributes as $attributes){
                    switch ($attributes->attribute_code) {
                        case "volume_height":
                            $altura = $attributes->value;
                            $registro->altura = $altura;
                            break;
                        case "volume_length":
                            $comprimento = $attributes->value;
                            $registro->comprimento = $comprimento;
                            break;
                        case "volume_width":
                            $largura = $attributes->value;
                            $registro->largura = $largura;
                            break;
                        case "description":
                            $descricao = $attributes->value;
                            $registro->descricao = $descricao;
                            break;
                        case "lead_time":
                            $prazo_operacional = $attributes->value;
                            $registro->prazo_operacional = $prazo_operacional;
                            break;
                        case "category_ids":
                            $categorias = $attributes->value;
                            $registro->category_id = $categorias;
                            break;
                        
                        case "garantia":
                            $garantia = $attributes->value;
                            $registro->garantia = $garantia;
                            break;
                        case "ean":
                            $ean = $attributes->value;
                            $registro->ean = $ean;
                            break;

                        case "manufacturer":
                            $fabricanteCod = $attributes->value;
                            $arrAtributosCamposBuscar['manufacturer'] = $fabricanteCod;
                            break;
                        case "ncm":
                            $ncm = $attributes->value;
                            $registro->ncm = $ncm;
                            break;

                        case "color":
                            $CorCod = $attributes->value;
                            $arrAtributosCamposBuscar['color'] = $CorCod;
                            $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'Cor' : $hasVariantAtualiza.';Cor';
                            $variacao = true;
                            break;
                        case "tamanho":
                            $tamanhoCod = $attributes->value;
                            $arrAtributosCamposBuscar['tamanho'] = $tamanhoCod;
                            $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'TAMANHO' : $hasVariantAtualiza.';TAMANHO';
                            $variacao = true;
                            break;
                        case "voltagem":
                            $voltagem_cod = $attributes->value;
                            $arrAtributosCamposBuscar['voltagem'] = $voltagem_cod;
                            $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'VOLTAGEM' : $hasVariantAtualiza.';VOLTAGEM';
                            $variacao = true;
                            break;
                    } 
                }

                if($arrAtributosCamposBuscar){
                    $strValoresVariacao = null;
                    
                    //Buca os valores dos atributos de campos
                    foreach($arrAtributosCamposBuscar as $chave => $valor){
                        $url = $JN2_URL .'rest/all/V1/products/attributes/'.$chave;
                        $dataAtributos = json_decode(json_encode($this->product->_sendREST($url, '')));

                        if ($dataAtributos->httpcode != 200) {
                            echo "\n Erro para buscar os atribudos da url={$url}, retorno=" . json_encode($dataAtributos) . "\n";
                            $this->log_data('batch', 'Product/updateProduct', "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataAtributos), "E");
                            continue;
                        } else {
                            //$arrayProductErroCheck = array();
                            $infAtributosProduto = json_decode($dataAtributos->content);
                            $arrObjOptions = $infAtributosProduto->options;

                            if($arrObjOptions){
                                foreach($arrObjOptions as $chaveOption => $valueOption){

                                    if($valueOption->value == $valor){
                                        $$chave = $valueOption->label;
                                        $strValor = trim($valueOption->label);
                                        
                                        if($chave == 'color' || $chave == 'tamanho' || $chave == 'voltagem'){
                                            if($chave == 'voltagem'){
                                                // Se não tiver 'V' no número da voltagem, adicionar
                                                $unityVoltage = strpos($strValor, 'V');
                                                if (!$unityVoltage) $strValor .= 'V';
                                            }
                                            
                                            $strValoresVariacao = empty($strValoresVariacao) ? $strValor : $strValoresVariacao.';'.$strValor;
                                        } else {
                                            $registro->$chave = $strValor;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
        
                
                //Vai pegar somente os produtos sem variações, que são os produtos simples sem cor, tamanho, voltagem.
                if(!empty($verifyProduct) && empty($strVariacoes) && $variacao == false)
                {
                    // Inicia transação
                    $this->db->trans_begin();
                    
                    $productUpdate = $this->product->updateProduct($registro, $skuProduct, false, false);
                    
                    if (!$productUpdate['success']) {
                        $this->db->trans_rollback();
                        echo "\nNão foi possível atualizar o produto={$idProduct}, sku={$skuProduct} encontrou um erro";
                        $this->log_data('batch', $log_name, "Não foi possível atualizar o produto={$idProduct}, sku={$skuProduct} encontrou um erro, productUpdate: ".json_encode($productUpdate), "W");
                        $this->log_integration("Erro para atualizar o produto {$skuProduct}", "<h4>Não foi possível atualizar o produto</h4> <ul>"
                            ."<li>SKU: {$skuProduct}</li><li>ID jn2: {$idProduct}</li></ul>", "E");
                        continue;
                    }

                    $this->db->trans_commit();

                    $this->log_data('batch', $log_name, "Produto atualizado! SKU={$skuProduct} ID_JN2={$idProduct}", "I");

                    echo "\nAtualizado com sucesso SKU={$skuProduct} ID_JN2={$idProduct}";
                    $this->log_integration("Produto atualizado SKU={$skuProduct}", "<h4>Produto SKU={$skuProduct} ID_JN2={$idProduct} foi atualizado com sucesso</h4>", "S");
                    $iCountAlteroProduto++;
                }
                else 
                { // Vai varrer os pais para verificar tipos de variações e capturar dados alterados ou novos
                    //Vai armazenar os dados tendo variações para depois varrer e gravar dados
                    $arrTempNaoExisteBanco = null;
                    $arrVariacaoBancoFilhos = $this->product->consultaFilhosProdutoPai($idProduct);
                                        
                    if(empty($arrVariacaoBancoFilhos) || !isset($arrVariacaoBancoFilhos)){                       
                        echo "\nProduto com problema ao consultar tendo variação no pai, possivelmente não tenha o filho cadastrado, sku: {$skuProduct}, idProduct: {$idProduct}";
                        $this->log_data('batch', $log_name, "Produto com problema ao consultar tendo variação no pai, possivelmente não tenha o filho cadastrado, sku: {$skuProduct}, idProduct: {$idProduct}", "E");
                        continue;
                    }
                    
                    $arr_variant_id_erpBanco = array_column($arrVariacaoBancoFilhos, 'variant_id_erp');
                    $arrTempNaoExisteBanco = $arr_variant_id_erpBanco;
                    
                    $prodIdConectala    = $arrVariacaoBancoFilhos[0]['prd_id'];
                    
                    //proprio Pai é seu filho, tipo simples
                    if($type_id == "simple" && $arrVariacaoBancoFilhos[0]['variant_id_erp'] == $arrVariacaoBancoFilhos[0]['product_id_erp']){
                        if ($intDtHoraUltimoJob >= $intDtAtualizacaoJn2) {
                            continue;
                        }
                    
                        $arrFilhoGeral = null;
                        $arrFilhoGeral[$idProduct]['id']    = $registro->id;
                        $arrFilhoGeral[$idProduct]['sku']   = $registro->sku.'_0';
                        $arrFilhoGeral[$idProduct]['name']  = $registro->name;
                        $arrFilhoGeral[$idProduct]['price'] = $registro->price;
                        $arrFilhoGeral[$idProduct]['status']        = $registro->status;
                        $arrFilhoGeral[$idProduct]['updated_at']    = $registro->updated_at;
                        $arrFilhoGeral[$idProduct]['strValorVariacao']  = $strValoresVariacao;
                        $arrFilhoGeral[$idProduct]['ean']               = isset($registro->ean) ? $registro->ean : '';
                        $arrFilhoGeral[$idProduct]['codigo_do_fabricante']  = '';
                        
                        //echo "\nL_484_registro->id: ".$registro->id." - sku: ".$registro->sku;
                        //echo "\nl_477_strCaminhoPai: ".$strCaminhoPai;
                        $arrFilhoGeral[$idProduct]['caminho_img_pai'] = $strCaminhoPai; //$arrVariacaoBancoFilhos[0]['image'];
                        $arrFilhoGeral[$idProduct]['img_filho'] = $imgFilhoProprioPai;
                        
                        //Se mudou tipos de variações no simples vai atualizar
                        $registro->has_variants = $hasVariantAtualiza;
                        
                        $bolPai = true;
                        $this->db->trans_begin();

                        $productUpdate = $this->atualizarProdutoPaiVariacao($arrFilhoGeral, $registro, $skuProduct, $idProduct, $prodIdConectala);
                                
                        if (!$productUpdate['success']) {
                            $this->db->trans_rollback();
                            echo "\nNão foi possível atualizar o produto={$idProduct}, sku={$skuProduct} encontrou um erro";
                            $this->log_data('batch', $log_name, "Não foi possível atualizar o produto={$idProduct}, sku={$skuProduct} encontrou um erro productUpdate=".json_encode($productUpdate), "W");
                            $this->log_integration("Erro para atualizar o produto {$skuProduct}", "<h4>Não foi possível atualizar o produto</h4> <ul>"
                                ."<li>SKU: {$skuProduct}</li><li>ID jn2: {$idProduct}</li></ul>", "E");
                            continue;
                        } else {
                            if(!empty($productUpdate['message'])){
                                $msgAtualiza = $productUpdate['message'];
                            }
                        }

                        $this->db->trans_commit();

                        $this->log_data('batch', $log_name, "Produto atualizado !!! SKU={$skuProduct} ID_JN2={$idProduct}, msgAtualiza: {$msgAtualiza}", "I");

                        echo "\nAtualizado produto com sucesso SKU={$skuProduct} ID_JN2={$idProduct}";
                        $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto {$skuProduct} ID_JN2={$idProduct} foi atualizado com sucesso.</h4>", "S");
                        $iCountAlteroProduto++;

                    } 
                    elseif($type_id == "configurable") {
                        $bolVerificaDesvinculo = false;
                        
                        if ($intDtAtualizacaoJn2 > $intDtHoraUltimoJob) {
                            $bolVerificaDesvinculo = true;
                        }
                        
                        $arrReturnFilhos_e_NaoTemBanco = $this->buscarFilhosValidarDados($skuProduct, $prodIdConectala, $JN2_URL, $arrVariacaoBancoFilhos,
                                $arr_variant_id_erpBanco, $arrTempNaoExisteBanco, $log_name, $intDtHoraUltimoJob, $intDtAtualizacaoJn2, $bolVerificaDesvinculo);
                        
                        if(isset($arrReturnFilhos_e_NaoTemBanco['success'])){
                            if($arrReturnFilhos_e_NaoTemBanco['success'] == false){
                                echo "\n Não foi possível atualizar o produto, idProduct={$idProduct}, sku={$skuProduct}: ". $arrReturnFilhos_e_NaoTemBanco['message'];
                                $this->log_data('batch', $log_name, "Não foi possível atualizar o produto, idProduct={$idProduct}, sku={$skuProduct}: ".$arrReturnFilhos_e_NaoTemBanco['message'], "E");
                                $this->log_integration("Alerta não foi possível atualizar o produto, idProduct={$idProduct}, sku={$skuProduct}", 
                                            "<h4> </h4> <ul>"
                                    ."<li>sku: {$skuProduct}</li><li>ID jn2: {$idProduct}</li>"
                                    . "<li>".$arrReturnFilhos_e_NaoTemBanco['message']."</li></ul>", "E");
                                continue;
                            }
                        }
                        
                        $arrTempNaoExisteBancoRetorno = null;
                        $arrReturnFilhos = $arrReturnFilhos_e_NaoTemBanco[0];
                        $arrTempNaoExisteBancoRetorno = $arrReturnFilhos_e_NaoTemBanco[1];
                        $strHasvarinatAtual = $arrReturnFilhos_e_NaoTemBanco[2];
                        $registro->has_variants = $strHasvarinatAtual;
                        
                        //Se existe informações dos filhos para atualizar
                        if($arrReturnFilhos){
                            if($arrTempNaoExisteBancoRetorno && $bolVerificaDesvinculo){                                
                                //Array com os produtos filhos que não tem mais vinculo com produto Pai, vai desvincular do produto Pai, retirando da prd_variants.
                                foreach($arrTempNaoExisteBancoRetorno as $key => $valor){
                                    $arrTratarDadosDesnvincula = $arrVariacaoBancoFilhos[$key];

                                    $prdId = $arrTratarDadosDesnvincula['prd_id'];
                                    $idVariacao = $arrTratarDadosDesnvincula['id'];
                                    $variantIdErp = $arrTratarDadosDesnvincula['variant_id_erp'];
                                    $skuVariacao = $arrTratarDadosDesnvincula['sku'];

                                    $this->db->trans_begin();
                                    $removeVariant = $this->product->removeVariacaoDoProdutoDesvincular($idVariacao, $prdId, $variantIdErp);

                                    if(!$removeVariant){
                                        $this->db->trans_rollback();
                                        echo "\nNão foi possível remover o vinculo da variação do produto variantIdErp={$variantIdErp}, sku={$skuVariacao} encontrou um erro";
                                        $this->log_data('batch', $log_name, "Não foi possível remover o vinculo da variação do produto variantIdErp={$variantIdErp}, sku={$skuVariacao} encontrou um erro.", "E");
                                        $this->log_integration("Erro para remover o vinculo da variação do produto variantIdErp={$variantIdErp}, sku={$skuVariacao}", 
                                                    "<h4>Não foi possível remover o vinculo da variação do produto </h4> <ul>"
                                            ."<li>skuVariacao: {$skuVariacao}</li><li>ID jn2: {$variantIdErp}</li></ul>", "E");
                                        continue;
                                    }

                                    //Quanto rodar a Criaçao do Produto, vai identificar ser novo produto dados Pai ($registro) / Filho (variação) para cadastra-lo.
                                    $this->db->trans_commit();
                                    $this->log_data('batch', $log_name, "Produto Desvinculado e vai se toru Pai/Filho !!! skuVariacao={$skuVariacao} variantIdErp={$variantIdErp}", "I");
                                    $iCountDesvinculoProduto++;
                                }
                            }

                            
                            //passou da validação dos tipos de variações de todos filhos -- Continuar.
                            $bolPai = true;
                            $this->db->trans_begin();
                            
                            $productUpdate = $this->atualizarProdutoPaiVariacao($arrReturnFilhos, $registro, $skuProduct, $idProduct, $prodIdConectala, $strCaminhoPai);

                            if (!$productUpdate['success']) {
                                $this->db->trans_rollback();
                                echo "\nNão foi possível atualizar o produto={$idProduct}, sku={$skuProduct} ID_JN2={$idProduct} encontrou um erro";
                                $this->log_data('batch', $log_name, "Não foi possível atualizar o produto={$idProduct}, sku={$skuProduct} encontrou um erro productUpdate=".json_encode($productUpdate), "E");
                                $this->log_integration("Erro para atualizar o produto {$skuProduct}", "<h4>Não foi possível atualizar o produto</h4> <ul>"
                                    ."<li>SKU: {$skuProduct}</li><li>ID jn2: {$idProduct}</li></ul>", "E");
                                    die;
                                continue;
                            } else {
                                if(!empty($productUpdate['message'])){
                                    $msgAtualiza = $productUpdate['message'];
                                }
                            }

                            $this->db->trans_commit();

                            $this->log_data('batch', $log_name, "Produto atualizado !!! SKU={$skuProduct} ID_JN2={$idProduct}, msgAtualiza: {$msgAtualiza}", "I");

                            echo "\nAtualizado produto com sucesso SKU={$skuProduct}, ID_JN2={$idProduct}";
                            $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto {$skuProduct}, ID_JN2={$idProduct} foi atualizado com sucesso.</h4>", "S");
                            $iCountAlteroProduto++;
                        } 
                        else {
                           //pode ter havido só alteração no produto Pai
                            if ($intDtHoraUltimoJob >= $intDtAtualizacaoJn2) {
                                //echo "\nProduto não precisa ser atualizado, sku: {$skuProduct}, idProduct: {$idProduct}, data atualizacao jn2 menor: {$dtAtualizacaoJn2}, conectala job: {$dtHoraUltimoJob}";
                                //$this->log_data('batch', $log_name, "Produto sku: {$skuProduct}, idProduct: {$idProduct} não precisa ser atualizado, data atualizacao jn2 menor: {$dtAtualizacaoJn2}, conectala job: {$dtHoraUltimoJob}", "I");
                                continue;
                            } else {
                                //Atualiza so o Pai.
                                $productUpdate = $this->product->updateProduct($registro, $skuProduct, null, false, null);

                                if (!$productUpdate['success']) {
                                    $this->db->trans_rollback();
                                    echo "\nNão foi possível atualizar o produto={$idProduct}, sku={$skuProduct} ID_JN2={$idProduct} encontrou um erro";
                                    $this->log_data('batch', $log_name, "Não foi possível atualizar o produto={$idProduct}, sku={$skuProduct} encontrou um erro productUpdate=".json_encode($productUpdate), "W");
                                    $this->log_integration("Erro para atualizar o produto {$skuProduct}", "<h4>Não foi possível atualizar o produto</h4> <ul>"
                                        ."<li>SKU: {$skuProduct}</li><li>ID jn2: {$idProduct}</li></ul>", "E");
                                    continue;
                                } else {
                                    if(!empty($productUpdate['message'])){
                                        $msgAtualiza = $productUpdate['message'];
                                    }
                                }

                                $this->db->trans_commit();

                                $this->log_data('batch', $log_name, "Produto atualizado !!! SKU={$skuProduct} ID_JN2={$idProduct}, msgAtualiza: {$msgAtualiza}", "I");

                                echo "\nAtualizado produto com sucesso SKU={$skuProduct}, ID_JN2={$idProduct}";
                                $this->log_integration("Produto {$skuProduct} atualizado", "<h4>Produto {$skuProduct}, ID_JN2={$idProduct} foi atualizado com sucesso.</h4>", "S");
                                $iCountAlteroProduto++;
                            }
                        }
                    }
                }
            } //fim lista foreach
            
            $page++;

            if($page > $pages){
                $haveProductList = false;
            }
        } //fim while paginação
        
        
        echo "\n Quantidade total: ".$iCountRegistrosTotais;
        echo "\n Alterou: ".$iCountAlteroProduto." produtos. ";
        echo "\n Desvinculou: ".$iCountDesvinculoProduto." produtos filhos.";

        //die ("\n verifica dados");
    }
    
     /* Consulta tipos de variações do filho
     *
     * @param   $dadosCustomAttributes  Dados das variações do endPoint JN2
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function buscaTiposVariacoesFilho($dadosCustomAttributes, $JN2_URL){
        $retornaTiposValoresVariacao = null;
        
        foreach ($dadosCustomAttributes as $attributes){
            switch ($attributes->attribute_code) {
                case "color":
                    $CorCod = $attributes->value;
                    $arrAtributosCamposBuscar['color'] = $CorCod;
                    $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'Cor' : $hasVariantAtualiza.';Cor';
                    break;
                case "tamanho":
                    $tamanhoCod = $attributes->value;
                    $arrAtributosCamposBuscar['tamanho'] = $tamanhoCod;
                    $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'TAMANHO' : $hasVariantAtualiza.';TAMANHO';
                    break;
                case "voltagem":
                    $voltagem_cod = $attributes->value;
                    $arrAtributosCamposBuscar['voltagem'] = $voltagem_cod;
                    $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'VOLTAGEM' : $hasVariantAtualiza.';VOLTAGEM';
                    break;
                case "ean":
                    $ean = $attributes->value;
                    $retornaTiposValoresVariacao['ean'] = $ean;
                    break;
                case "manufacturer":
                    $fabricanteCod = $attributes->value;
                    $arrAtributosCamposBuscar['manufacturer'] = $fabricanteCod;
                    break;
            } 
        }
        
        if($arrAtributosCamposBuscar){
            $strValoresVariacao = null;
            
            //Buca os valores dos atributos de campos
            foreach($arrAtributosCamposBuscar as $chave => $valor){
                $url = $JN2_URL .'rest/all/V1/products/attributes/'.$chave;
                $dataAtributos = json_decode(json_encode($this->product->_sendREST($url, '')));

                if ($dataAtributos->httpcode != 200) {
                    echo "\n Erro para buscar os atribudos da url={$url}, retorno=" . json_encode($dataAtributos) . "\n";
                    $this->log_data('batch', 'Product/updateProduct', "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataAtributos), "E");
                    return array('success' => false, 'message' => 'Ocorreu um problema ao buscar os atributos do produto!');
                } else {
                    $infAtributosProduto = json_decode($dataAtributos->content);
                    $arrObjOptions = $infAtributosProduto->options;

                    if($arrObjOptions){
                        foreach($arrObjOptions as $chaveOption => $valueOption){
                            if($valueOption->value == $valor){
                                $$chave = $valueOption->label;
                                $strValor = trim($valueOption->label);

                                if($chave == 'color' || $chave == 'tamanho' || $chave == 'voltagem'){
                                    if($chave == 'voltagem'){
                                        // Se não tiver 'V' no número da voltagem, adicionar
                                        $unityVoltage = strpos($strValor, 'V');
                                        if (!$unityVoltage) $strValor .= 'V';
                                    }
                                    
                                    $strValoresVariacao = empty($strValoresVariacao) ? $strValor : $strValoresVariacao.';'.$strValor;
                                } else {
                                    $retornaTiposValoresVariacao[$chave] = $strValor;
                                }
                            }
                        }
                    }
                }
            }
            
            $retornaTiposValoresVariacao[0] = $hasVariantAtualiza;
            $retornaTiposValoresVariacao[1] = $strValoresVariacao;
            return $retornaTiposValoresVariacao;
        } else {
            return null;
        }
    }
    
    public function buscarFilhosValidarDados($skuProduct, $prodIdConectala, $JN2_URL, $arrVariacaoBancoFilhos = null,
            $arr_variant_id_erpBanco = null, $arrTempNaoExisteBanco = null, $log_name, $intDtHoraUltimoJob, $intDtAtualizacaoJn2, $bolVerificaDesvinculo){
        //varre os filhos para verificar a quantidade de variações se está de acordo com a quantidade e tipo de variação
        
        $urlPaiFilhos = $JN2_URL .'rest/all/V1/configurable-products/'.$skuProduct.'/children';
        $data = '';
        $dataProductsTodosFilhos = json_decode(json_encode($this->product->_sendREST($urlPaiFilhos, $data)));

        if ($dataProductsTodosFilhos->httpcode != 200) {
            echo "Erro para buscar a lista de todos filhos url={$urlPaiFilhos}, retorno=" . json_encode($dataProductsTodosFilhos) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a lista de todos filhos url={$urlPaiFilhos}, retorno=" . json_encode($dataProductsTodosFilhos), "W");
            return array('success' => false, 'message' => "Erro para buscar a lista de todos filhos skuProduct={$skuProduct}");
        }

        $contentDadosFilhos = json_decode($dataProductsTodosFilhos->content);
        
        $icountFilho = 0;
        $arrFilhoGeral = null;
        $arrTiposVariacoesValores = null;
        $intDtUltimoAtualizacaoFilho = null;
        
        foreach($contentDadosFilhos as $arrDadoFilho){
            $skuFilho = $arrDadoFilho->sku;
            $idJN2 = $arrDadoFilho->id;
            $dtUltimoAtualizacaoFilho = $arrDadoFilho->updated_at;
            $intDtUltimoAtualizacaoFilho = strtotime($dtUltimoAtualizacaoFilho);

            if($arrDadoFilho->status != '1'){
                continue;
            }
            
            //Se já rodou e dataHora do endpoint filho e do Pai, for menor que a última dtHoradoJob não precisará atualizar. Se a dtHora do pai for Maior
            //Vai varrer tds filhos, pq pode ter desvinculado de algum filho.
            if($bolVerificaDesvinculo == false){
                if($intDtHoraUltimoJob >= $intDtUltimoAtualizacaoFilho && $intDtHoraUltimoJob >= $intDtAtualizacaoJn2){
                    //echo "\nL_748_Nao Entra _idJN2: ".$idJN2." - skuFilho: ".$skuFilho;
                    continue;
                }
            }
           
            $urlFilho = $JN2_URL .'rest/all/V1/products/'.$skuFilho;
            $data = '';
            $dataProductsFilho = json_decode(json_encode($this->product->_sendREST($urlFilho, $data)));

            if ($dataProductsFilho->httpcode != 200) {
                echo "Erro para buscar a lista de url={$urlFilho}, retorno=" . json_encode($dataProductsFilho) . "\n";
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$urlFilho}, retorno=" . json_encode($dataProductsFilho), "W");
                return array('success' => false, 'message' => "Erro para buscar a lista do filho, skuFilho={$skuFilho}");
            }
            
            $arrDadosFilhos = json_decode($dataProductsFilho->content);

            $arrTiposVariacoesValores = $this->buscaTiposVariacoesFilho($arrDadosFilhos->custom_attributes, $JN2_URL);
            $arrTiposVariacaoAtual  = explode(';', $arrTiposVariacoesValores[0]);
            
            $strValoresVariacao     = $arrTiposVariacoesValores[1];
            $eanJN2 = isset($arrTiposVariacoesValores['ean']) ?  $arrTiposVariacoesValores['ean'] : '';
            //$codFabricanteJN2 = isset($arrTiposVariacoesValores['manufacturer']) ?  $arrTiposVariacoesValores['manufacturer'] : '';
            $codFabricanteJN2 = '';

            if($icountFilho >0){
                foreach($arrTiposVariacaoAtual as $strTipoVariacaoAtual){

                    if (!in_array($strTipoVariacaoAtual, $hasVariationsAnterior)) {
                        $msg = "A variação do item =".$arrDadosFilhos->id." , sku =". $arrDadosFilhos->sku." , não é compatível com as variações do item filho $idFilhoAnterior.";
                        $this->log_data('batch', $log_name, "Não é compatível os tipos de variação com tds filhos, hasVariationsAnterior={$hasVariationsAnterior[0]} -strTipoVariacaoAtual={$strTipoVariacaoAtual}", "W");
                        return array('success' => false, 'message' => "Foram encontradas tipos de variações que não estão cadastradas no produto. ".$msg);
                    }
                }
            }
            
           $arrImagenFilho = null;
            if(isset($arrDadosFilhos->media_gallery_entries) && count($arrDadosFilhos->media_gallery_entries) >0){
                foreach($arrDadosFilhos->media_gallery_entries as $arrImagens){
                    $arrImagenFilho[] = $arrImagens->file;
                }
            }

            //depois vou ter q verificar se tem algum variant_id_erp no banco e não está mais no endpoit, tem que remover o vínculo com o Pai.
            $arrBancoFilho = $this->product->getVariationForIdErpPrdId($idJN2, $prodIdConectala);

            if($arrBancoFilho){
                $bolAlteraVariacao = false;
                //Vai verificar se não tiver imagem nas variações e tiver no endopoint vai atualizar.
                $dirImageVariacao = $arrBancoFilho['imagePai'].'/'.$arrBancoFilho['image'];

                if($arrBancoFilho['imagePai']){
                    //Se no endPoint tem alguma imagem e se na pasta conectalá não tem nenhuma imagem.
                    if ($this->countImagesDir($dirImageVariacao) == 0){
                        if(is_array($arrImagenFilho) && count($arrImagenFilho) > 0){
                            $bolAlteraVariacao = true;
                        }
                    }
                }

                //Se os valores das variações forem diferente
                if($arrBancoFilho['name'] != $strValoresVariacao ||
                    $arrBancoFilho['sku'] != $arrDadosFilhos->sku ||
                    $arrBancoFilho['status'] != $arrDadosFilhos->status ||
                    $arrBancoFilho['EAN'] != $eanJN2 ||
                    $bolAlteraVariacao){

                    $arrFilhoGeral[$idJN2]['id']    = $arrDadosFilhos->id;
                    $arrFilhoGeral[$idJN2]['sku']        = $arrDadosFilhos->sku;
                    $arrFilhoGeral[$idJN2]['name']  = $arrDadosFilhos->name;
                    //$arrFilhoGeral[$idJN2]['price']  = $arrDadosFilhos->price;
                    $arrFilhoGeral[$idJN2]['status']  = $arrDadosFilhos->status;
                    $arrFilhoGeral[$idJN2]['updated_at']  = $arrDadosFilhos->updated_at;
                    $arrFilhoGeral[$idJN2]['strValorVariacao']  = $strValoresVariacao;
                    $arrFilhoGeral[$idJN2]['ean']               = $eanJN2;
                    
                    if($arrBancoFilho['imagePai']){
                        $arrFilhoGeral[$idJN2]['caminho_img_pai']   = $arrBancoFilho['imagePai'];
                        $arrFilhoGeral[$idJN2]['img_filho']         = $arrImagenFilho ?? null;
                    }

                    $arrFilhoGeral[$idJN2]['codigo_do_fabricante'] = $codFabricanteJN2;
                }
            } 
            else {
                //Se não tiver no banco será uma nova Variação
                $arrFilhoGeral[$idJN2]['id']   = $arrDadosFilhos->id;
                $arrFilhoGeral[$idJN2]['sku']  = $arrDadosFilhos->sku;
                $arrFilhoGeral[$idJN2]['name']  = $arrDadosFilhos->name;
                $arrFilhoGeral[$idJN2]['price']  = $arrDadosFilhos->price;
                $arrFilhoGeral[$idJN2]['status']  = $arrDadosFilhos->status;
                $arrFilhoGeral[$idJN2]['updated_at']  = $arrDadosFilhos->updated_at;
                $arrFilhoGeral[$idJN2]['strValorVariacao']  = $strValoresVariacao;
                $arrFilhoGeral[$idJN2]['ean']               = $eanJN2;
                $arrFilhoGeral[$idJN2]['codigo_do_fabricante'] = '';
                $arrFilhoGeral[$idJN2]['img_filho']         = $arrImagenFilho;
            }


            if($bolVerificaDesvinculo){
                //Vai verificar se os $idJN2, não foram desvinculados e não existe no banco conectala, se existir so no banco vai deixar um Pai/filho como se comporta no JN2.
                $key = array_search($idJN2, $arr_variant_id_erpBanco);
                //Se existe no banco e no endPoint, vai retirar do arrayTemNaoExisteBanco, para deixar só os que não existe no banco conectalá, para após retirar vinculo.
                if($key>=0){
                    unset($arrTempNaoExisteBanco[$key]);
                }
            }

            $hasVariationsAnterior = explode(';', $arrTiposVariacoesValores[0]);

            $idFilhoAnterior = $arrDadosFilhos->id;
            $icountFilho++;
        }
        
        //Se for só 1 alteração vai verificar se essa alteração está diferente do banco nos tipos de variações, se tiver não poderá realizar.
        if($icountFilho ==1 && isset($arrTiposVariacoesValores[0]) && isset($arrBancoFilho['has_variants'])){
            if($arrTiposVariacoesValores[0] != $arrBancoFilho['has_variants']){            
                $msg = "A variação do item = ".$arrBancoFilho['variant_id_erp'].", sku = ".$arrBancoFilho['sku']." , não é compatível com as variações do item filho ".$arrBancoFilho['id'];
                return array('success' => false, 'message' => "Foram encontradas tipos de variações que não estão cadastradas no produto. ".$msg);
            }
        }
        
        return array(0 => $arrFilhoGeral, 1 => $arrTempNaoExisteBanco, 2=>$arrTiposVariacoesValores[0]);
    }
    
    
    public function atualizarProdutoPaiVariacao($arrReturnFilhos, $registro, $skuProduct, $idProdPai, $prodIdConectala, $strCaminhoPai=null){
        $bolPai = true;
        $msgAtualiza = null;
        
        foreach($arrReturnFilhos as $arrDadoReturnFilhos){
            if($bolPai){
                //Atualiza o Pai 1º x e seu filho, nos demais só vai atualizar os filhos conectalá.
                $productUpdate = $this->product->updateProduct($registro, $skuProduct, $arrDadoReturnFilhos, true, $prodIdConectala);
            } else {                
                $productUpdate = $this->product->atualizacaoCriacaoVariation($arrDadoReturnFilhos, $skuProduct, $prodIdConectala, $strCaminhoPai);
                //echo "\nAtualizado produto com sucesso SKU={$arrDadoReturnFilhos['sku']}, id={$arrDadoReturnFilhos['id']}, strValorVariacao={$arrDadoReturnFilhos['strValorVariacao']}";
            }

            if (!$productUpdate['success']) {
                $this->log_data('batch', 'Product/updateProduct', "Não foi possível atualizar o produto={$arrDadoReturnFilhos['id']}, sku={$skuProduct} encontrou um erro productUpdate=".json_encode($productUpdate), "E");
                return array('success'=>false, 'message'=>"Não foi possível atualizar o produto={$arrDadoReturnFilhos['id']}, sku={$arrDadoReturnFilhos['sku']} encontrou um erro");
            } else {
                if(!empty($productUpdate['message'])){
                    $msgAtualiza = empty($msgAtualiza) ? $productUpdate['message']: $msgAtualiza."\n".$productUpdate['message'];
                }
            }

            $bolPai = false;
        }
        
        return array('success' => true, 'message'=>$msgAtualiza);
    }
    
    public function countImagesDir($dirImage)
    {
        $count = 0;
        $caminhoCompleto = FCPATH . 'assets/images/product_image/' . $dirImage;
        $images = scandir(FCPATH . 'assets/images/product_image/' . $dirImage);
        foreach($images as $image) {
            if ($image != "." && $image != ".." && $image != ""){
                if(!is_dir($caminhoCompleto.'/'.$image)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
    
