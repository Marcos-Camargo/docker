<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Integration/JN2/Product/UpdateStock run
 *
 */

use phpDocumentor\Reflection\Types\Object_;

require APPPATH . "controllers/BatchC/Integration/JN2/Main.php";
require APPPATH . "controllers/BatchC/Integration/JN2/Product/Product.php";

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
        $this->load->model('model_integrations');
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
        echo "Pegando produtos para atualizar o estoque";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        //busca última x q rodou e gravaou na atualização
        $arrDadosJobIntegration = $this->model_integrations->getJobForJobAndStore('UpdateStock', $this->store);
        $dtUpdateJobIntegration = $arrDadosJobIntegration['date_updated'];
        $dtLastRunJobIntegration = $arrDadosJobIntegration['last_run'];
        /* diminuo 1 hora do último job rodado na Atualização do Estoque, para ficar na mesma faixa de horário do endpoint do JN2,
            no php local está com diferença de 3 horas maior da local do Brasil e o endPoint da JN2 está com 2 horas maior da local do Brasil. */
        $dtHoraUltimoJob = $this->product->ajustarDataHoraMenorMaior($dtLastRunJobIntegration, '+180 minutes');  //coloquei 3:00 horas, para buscar 0 minutos antes para não ter problema, pq estava dando diferenca de 1 minuto no meu local com o da jn2 e ainda pode estar rodando.
        
        // Recupera os produtos
        $this->getProducts($dtHoraUltimoJob);

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
        $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
        $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
        $url .= '&searchCriteria[pageSize]='.$registrosPorPagina;
        $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';

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
        $page = 1;
        $totalRegistros = $contentProducts->total_count;

        $pages = ceil(($totalRegistros / $registrosPorPagina));

        $prods = $contentProducts->items;
        $regProducts = $prods;

        $iCountRegistrosTotais = 0;
        $iCountAlterouQuantidade = 0;
        $arrVariacoes = array('');

        $strVariacoes = null;
        $arrVariacoes = null;

        while ($haveProductList) {
            if ($page != 1) {
                $url = $JN2_URL .'rest/all/V1/products?searchCriteria[filterGroups][0][filters][0][field]=teste_diogo';
                $url .= '&searchCriteria[filterGroups][0][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][1][filters][0][field]=status&searchCriteria[filterGroups][1][filters][0][value]=1';
                $url .= '&searchCriteria[filterGroups][1][filters][0][conditionType]=equal';
                $url .= '&searchCriteria[pageSize]='.$registrosPorPagina.'&searchCriteria[currentPage]='.$page;
                $url .= '&searchCriteria[filterGroups][0][filters][0][conditionType]=equal';

                $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

                if ($dataProducts->httpcode != 200) {
                    echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    if ($dataProducts->httpcode != 99) {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                    } else {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                    }

                    $haveProductList = false;
                    continue;
                }

                $regListProducts = json_decode($dataProducts->content);
                $prods = $regListProducts->items;
                $regProducts = $prods;
            }

            foreach ($regProducts as $registro) {
                $idProduct  = $registro->id;
                $skuProduct = $registro->sku;
                $status     = $registro->status;
                $type_id    = $registro->type_id;
                $skuPai     = null;
                $idPai      = null;
                $arrDadosVariacao = null;
                $qtFilhos = null;
                $idProdConectala = null;

                $iCountRegistrosTotais++;

                $dtAtualizacaoJn2       = $registro->updated_at;

                if ($status == '1') 
                {
                    $this->setUniqueId($idProduct); // define novo unique_id
        
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    //echo "\nL_193_dtHoraUltimoJob: ".$dtHoraUltimoJob." -dtAtualizacaoJn2: ".$dtAtualizacaoJn2;
                    if ($dtHoraUltimoJob >= $dtAtualizacaoJn2) {
                        //echo "\nProduto não precisa ser atualizado, sku: {$skuProduct}, idProduct: {$idProduct}."; //, data atualizacao jn2 menor: {$dtAtualizacaoJn2}, conectala job: {$dtHoraUltimoJob}";
                        //$this->log_data('batch', $log_name, "Produto sku: {$skuProduct}, idProduct: {$idProduct} não precisa ser atualizado, data atualizacao jn2 menor: {$dtAtualizacaoJn2}, conectala job: {$dtHoraUltimoJob}", "I");
                        continue;
                    }

                    $strVariacoes = $verifyProduct['has_variants'];
                    
                    if(!empty($verifyProduct['id'])){
                        $idProdConectala = $verifyProduct['id'];
                    }

                    //Vai pegar somente os produtos sem variações, se houver irá adicionar num array para percorrer depois.
                    if(!empty($verifyProduct) && empty($strVariacoes)){
                        // Inicia transação
                        $this->db->trans_begin();

                        $qtyProduct             = $verifyProduct['qty'];

                        //vai fazer endpoint pra verificar a quantidade, Estoque
                        $urlQtd = $JN2_URL .'rest/all/V1/stockItems/'.$skuProduct;
                        $dataProductsQtd = json_decode(json_encode($this->sendREST($urlQtd, '')));
        
                        if ($dataProductsQtd->httpcode != 200) {
                            $this->db->trans_rollback();
                            echo "Erro para buscar o estoque da lista de url={$urlQtd}, retorno=" . json_encode($dataProductsQtd) . "\n";
                            if ($dataProductsQtd->httpcode != 99) {
                                $this->log_data('batch', $log_name, "Erro para buscar a quantidade url={$urlQtd}, retorno=" . json_encode($dataProductsQtd), "W");
                            } else {
                                $this->log_data('batch', $log_name, "Erro para buscar a quantidade url={$urlQtd}, retorno=" . json_encode($dataProductsQtd), "W");
                            }

                            array_push($arrayProductErroCheck, $idProduct);
                            continue;
                        } else {
                            $dataProductsContentQtd = json_decode($dataProductsQtd->content);

                            if(isset($dataProductsContentQtd)){
                                if(isset($dataProductsContentQtd->qty)){
                                    $quantidadeJn2 = $dataProductsContentQtd->qty;
                                } else {
                                    $quantidadeJn2 = null;
                                }
                            } else {
                                $quantidadeJn2 = null;
                            }
                        }

                        if(isset($quantidadeJn2)){
                            //somar quantidade pai conectalá, e diferença da variação do sku, tem q buscar antes pra ver se está diferente **
                            if($qtyProduct != $quantidadeJn2){
                                $updateStock = $this->updateStock($idPai, $skuProduct, $quantidadeJn2, $skuPai, $dtAtualizacaoJn2, $idProdConectala);

                                //atualizou com sucesso
                                if ($updateStock[0] === false) {
                                    $this->db->trans_rollback();
                                    echo "\nErro para atualizar o estoque do produto SKU {$skuProduct}, ID={$idProduct}, estoque={$quantidadeJn2}.";
                                    $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct}, ID={$idProduct}, estoque={$quantidadeJn2}  retorno=" . json_encode($updateStock), "E");
                                    $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>", "E");
                                    continue;
                                }
                                if ($updateStock[0] === null) {         
                                    $this->db->trans_rollback();            
                                    /*echo "\nEstoque do produto igual ao do banco, não será modificado skuProduct={$skuProduct}.";                        
                                    $this->log_data('batch', $log_name, 
                                        "Estoque do produto={$skuProduct} igual ao do banco, não será modificado, quantidadeJn2 conecta = {$qtyProduct}, quantidadeJn2 jn2 = {$quantidadeJn2}", "I"); */
                                    continue;
                                }
        
                                //atualizou com sucesso
                                if ($updateStock[0] === true) {
                                    $this->db->trans_commit();
                                    $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                                    echo "\nEstoque do produto={$skuProduct} atualizado com sucesso -  estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}.";
                                    $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");                                            

                                    $iCountAlterouQuantidade++;
                                    continue;
                                }
                            } else {
                                $this->db->trans_rollback();
                                /*echo "\nProduto {$skuProduct} com o mesmo estoque, não será atualizado, quantidadeJn2 = {$qtyProduct}.";
                                $this->log_data('batch', $log_name, 
                                        "Estoque igual, não será atualizado. sku = {$skuProduct}, idProduct = {$idProduct} não precisa ser atualizado, 
                                        quantidadeJn2 conecta = {$qtyProduct}, quantidadeJn2 jn2 = {$quantidadeJn2}", "I"); */
                                continue;
                            }
                        } else {
                            $this->db->trans_rollback();
                            echo "\n Produto sku: {$skuProduct}, idProduct = {$idProduct} não precisa ser atualizado, 
                            quantidadeJn2 conecta = {$qtyProduct}, quantidadeJn2 jn2 = {$quantidadeJn2}
                            dtHoraUltimoJob: {$dtHoraUltimoJob}, dtAtualizacaoJn2: {$dtAtualizacaoJn2}";
                            $this->log_data('batch', $log_name, 
                                        "Estoque do produto não existe ou nulo sku = {$skuProduct}, idProduct = {$idProduct} não precisa ser atualizado, 
                                        quantidadeJn2 conecta = {$qtyProduct}, quantidadeJn2 jn2 = {$quantidadeJn2},
                                        dtHoraUltimoJob: {$dtHoraUltimoJob}, dtAtualizacaoJn2: {$dtAtualizacaoJn2}", "E");
                            continue;
                        }
                    }
                    else { // produto ainda não cadastrado, na prod pelo sku, vai verificar por ser variação.
                        $arrDadosVariacao = null;
                        $quantidadeJn2 = null;
                        $arrConsultaVariacao = null;
                        //Vai armazenar os dados tendo variações para depois varrer e gravar dados
                        $arrConsultaVariacao = $this->product->getVariationForIdErp($idProduct);

                        //vai fazer endpoint pra verificar a quantidade, Estoque
                        $urlQtd = $JN2_URL .'rest/all/V1/stockItems/'.$skuProduct;
                        $dataProductsQtd = json_decode(json_encode($this->sendREST($urlQtd, '')));

                        if ($dataProductsQtd->httpcode != 200) {
                            echo "Erro para buscar o estoque da lista de url={$urlQtd}, retorno=" . json_encode($dataProductsQtd) . "\n";
                            if ($dataProductsQtd->httpcode != 99) {
                                $this->log_data('batch', $log_name, "Erro para buscar a quantidade url={$urlQtd}, retorno=" . json_encode($dataProductsQtd), "W");
                            } else {
                                $this->log_data('batch', $log_name, "Erro para buscar a quantidade url={$urlQtd}, retorno=" . json_encode($dataProductsQtd), "W");
                            }
                            
                            continue;
                        } else {
                             
                            $dataProductsContentQtd = json_decode($dataProductsQtd->content);
                           
                            if(isset($dataProductsContentQtd)){
                                if(isset($dataProductsContentQtd->qty)){
                                    $quantidadeJn2 = $dataProductsContentQtd->qty;
                                } else {
                                    $quantidadeJn2 = null;
                                }
                            } else {
                                $quantidadeJn2 = null;
                            }
                        }

                        if($arrConsultaVariacao){
                            $idPai = $arrConsultaVariacao['product_id_erp'];
                            $arrDadosVariacao['qty'] = $arrConsultaVariacao['qty'];
                            $arrDadosVariacao['sku'] = $skuProduct;
                            $arrDadosVariacao['qty_jn2'] = $quantidadeJn2;
                            $arrDadosVariacao['sku_pai']    = $arrConsultaVariacao['sku_pai'];
                            $arrDadosVariacao['id_pai']      = $idPai;
                            $arrDadosVariacao['dt_update_jn2'] = $dtAtualizacaoJn2;
                            $arrDadosVariacao['prd_id'] = $arrConsultaVariacao['prd_id'];
                            $arrVariacoes[$idPai][$idProduct] = $arrDadosVariacao;
                        } else {
                            if($strVariacoes){
                                $qtyProduct             = $verifyProduct['qty'];
                                $idPai = $idProduct;
                                $arrDadosVariacao['qty'] = $qtyProduct;
                                $arrDadosVariacao['sku'] = $skuProduct;
                                $arrDadosVariacao['qty_jn2']    = $quantidadeJn2;
                                $arrDadosVariacao['sku_pai']    = $skuProduct;
                                $arrDadosVariacao['id_pai']     = $idProduct;
                                $arrDadosVariacao['dt_update_jn2'] = $dtAtualizacaoJn2;
                                $arrDadosVariacao['prd_id'] = $idProdConectala;
                                $arrVariacoes[$idPai][$idProduct] = $arrDadosVariacao;
                            }
                        }
                    }
                }
            }

            $page++;

            if($page > $pages){
                $haveProductList = false;
            }
        } // fim lista

        if($arrVariacoes){
            foreach($arrVariacoes as $keyPai => $arrDadosFilho){
                foreach($arrDadosFilho as $keyFilho => $dadosFilho){
                    $idPai  = null;
                    $skuPai = null;
                    $quantidadeJN2 = null;
                    $idProdConectala = null;

                    if($keyPai == $keyFilho) {
                        $skuProduct     = $dadosFilho['sku'].'_0';  //proprio registro pai vai ser o filho, por não ter novo registro na JN2, controlamos pelo _0 pra diferenciar o sku único.
                    } else {
                        $skuProduct     = $dadosFilho['sku'];
                    }

                    $idProduct      = $keyFilho;
                    $quantidadeJN2  = $dadosFilho['qty_jn2'];
                    $dtAtualizacaoJn2 = $dadosFilho['dt_update_jn2'];
                    $qtyProduct     = $dadosFilho['qty'];
                    $idPai          = $dadosFilho['id_pai'];
                    $skuPai         = $dadosFilho['sku_pai'];
                    $idProdConectala = $dadosFilho['prd_id'];

                    $this->setUniqueId($idProduct); // define novo unique_id, pro log_integration
                    
                    $this->db->trans_begin();
                    $updateStock = $this->updateStock($idPai, $skuProduct, $quantidadeJN2, $skuPai, $dtAtualizacaoJn2, $idProdConectala);

                    if ($updateStock[0] === false) {
                        echo "\nErro para atualizar o estoque do produto SKU {$skuProduct}, ID: {$idProduct}, estoque ERP: {$quantidadeJN2}\n";
                        $this->db->trans_rollback();
                        $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct} , estoque ERP: {$quantidadeJN2}".
                            " retorno=" . json_encode($updateStock), "E");
                        $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}, estoque ERP: {$quantidadeJN2}</h4>", "E");
                    }
            
                    if ($updateStock[0] === null) {                     
                        //echo "\nEstoque do produto igual ao do banco, não será modificado skuProduct:{$skuProduct}, ID: {$idProduct} ";
                        $this->db->trans_rollback();
                        //$this->log_data('batch', $log_name, "Produto com estoque igual ao banco não será modificado, do produto ID={$idProduct}, "." SKU={$skuProduct}, quantidadeJn2={$quantidadeJN2}, $qtyProduct={$qtyProduct}, dtAtualizacaoJn2={$dtAtualizacaoJn2}", "I");
                    }
            
                    //atualizou com sucesso
                    if ($updateStock[0] === true) {
                        $this->db->trans_commit();
                        $this->log_integration("Estoque do produto {$skuProduct}, ID: {$idProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso."
                            ." </h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                        echo "\nEstoque do produto={$skuProduct}, ID: {$idProduct} atualizado com sucesso - Estoque anterior: {$updateStock[1]}  Estoque alterado: {$updateStock[2]} ";
                        $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct}, ID: {$idProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                    
                        $iCountAlterouQuantidade++;
                    }
                }
            }
        }
        
        echo "\n Quantidade total: ".$iCountRegistrosTotais;
        echo "\n Alterou o estoque de: ".$iCountAlterouQuantidade." produtos\n";

        return true;
    }

    
    public function updateStock($idProductPai, $skuProduct, $stockNew, $skuPai = null, $dtAtualizacao = null, $idProdConectala)
    {
        //if ($stockNew <= 0) return array(false); Conforme confirmado com Pedro - 04/02/21 as 15:37 por Whatsapp vai atualizar para 0 se não tiver mais o produto.

        if(!empty($skuPai))
        {
            $stock = $stockNew;
            $stockReal = $this->product->getStockVariationForSku($skuProduct, $idProdConectala) ?? 0;
            if($stock == (int)$stockReal){
                return array(null);
            } 

            return array($this->product->updateStockVariation($skuProduct, $skuPai, $stock, $dtAtualizacao),$stockReal,$stock);
        }

        $stock = $stockNew;     
        $stockReal = $this->product->getStockForSku($skuProduct) ?? 0;
        if($stock == (int)$stockReal) return array(null);
        return array($this->product->updateStockProduct($skuProduct, $stock, $idProductPai),$stockReal,$stock);              
    }   

}