<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration/Eccosys/Product/UpdatePrice run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";
require APPPATH . "controllers/BatchC/Integration/Eccosys/Product/Product.php";

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
            'userstore' => 0,
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

		echo "Rotina desativada \n";
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
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        // começando a pegar os produtos para criar
        $this->getListProducts();
    }

    /**
     * Recupera a lista para atualização do produto/variação
     *
     * @return bool
     */
    public function getListProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }
        
        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $ECCOSYS_URL = '';
        if($dataIntegrationStore){
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $ECCOSYS_URL = $credentials->url_eccosys;
        }
        /* faz o que o job precisa fazer */        

        // começando a pegar os produtos para criar
        $url = $ECCOSYS_URL.'/api/produtos?$filter=opcEcommerce+eq+S';
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->httpcode != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");                
            }
            return false;
        }

        $regProducts = json_decode($dataProducts->content);      
        
        $haveProductList = true;
        $arrPriceUpdate  = array();
        $page = 1;
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;

    
        if ($dataProducts->httpcode != 200) {            
            if ($dataProducts->httpcode == 999 && $countlimiteRequestBlock <= $limiteRequestBlock) {
                echo "aguardo 1 minuto para testar novamente. (Tentativas: {$countlimiteRequestBlock}/{$limiteRequestBlock})\n";
                sleep(60);
                $countlimiteRequestBlock++;                
            }

            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");                
            $haveProductList = false;            
        }       

        foreach ($regProducts as $registro) 
        {           
            if($registro->situacao == 'A'){                
                $skuProduct     = $registro->codigo;
                $precoProd      = $registro->preco;
                $idProduct      = $registro->id;

                $this->setUniqueId($idProduct); // define novo unique_id

                // Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($idProduct);

                // Não encontrou o produto pelo código da Eccosys
                if (empty($verifyProduct)) {

                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    // existe o sku na loja, mas não esá com o registro do id da Eccosys
                    if (!empty($verifyProduct)) {                        
                        // produto com o mesmo preço, não será atualizado
                        echo "atualizou código Eccosys para o produto sku={$skuProduct}, código Eccosys={$idProduct}...\n";

                        if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                            if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                                $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                        } else {
                            $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                        }
                    }                    
                    else {
                        //verifica se o sku é uma variação
                        $verifyProductVar = $this->product->getVariationForSku($skuProduct);
                        if (!empty($verifyProductVar)) 
                        {
                            // Adiciona preço para atualizar
                            if (array_key_exists($skuProduct, $arrPriceUpdate)) {
                                if ($precoProd > $arrPriceUpdate[$skuProduct]['price'])
                                    $arrPriceUpdate[$skuProduct]['price'] = $precoProd;
                                    continue;
                            } else {
                                $arrPriceUpdate[$skuProduct] = array('price' => $precoProd, 'id' => $idProduct);
                                continue;
                            }

                        } 
                        else{                        
                            // produto ainda não cadastrado                            
                            continue;
                        }
                    }

                } else {  // encontrou o produto pelo código da Eccosys, atualizar

                    if ($verifyProduct['status'] != 1) {
                        echo "Produto não está ativo\n";
                        continue;
                    }

                    if ($skuProduct != $verifyProduct['sku']) {
                        echo "Produto encontrado pelo código Eccosys, mas com o sku diferente do sku cadastrado ID_Eccosys={$idProduct}, SKU={$skuProduct} \n";
                        $this->log_data('batch', $log_name, "Produto encontrado pelo código Eccosys, mas com o sku diferente do sku cadastrado ID_Eccosys={$idProduct}, SKU={$skuProduct}", "W");
                        $this->log_integration("Erro para atualizar o preço do produto {$skuProduct}", "<h4>Produto recebido e encontrado pelo código Eccosys, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$skuProduct}<br><strong>SKU Na Conecta Lá</strong>: {$verifyProduct['sku']}<br><strong>ID Eccosys<strong>: {$idProduct}", "E");
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
        }
        

         // fim lista

        // Atualiza produtos
        // É feita por fora, pois precisa comparar com o valores
        // das variações e recuperar o maior valor. Não temos
        // preços diferente para variações
        foreach ($arrPriceUpdate as $sku => $product) 
        {
            $price      = number_format($product['price'],2,".","");            
            $idProduct  = $product['id'];

            $verifyProduct = $this->product->getProductForSku($sku);
            if(!$verifyProduct){
                //se não for produto pai, procura por variações
                $verifyProduct = $this->product->getVariationForSku($sku);
            }

            // produto com o mesmo preço, não será atualizado
            if ($price == 0) {
                echo "produto {$sku} com preço de venda zerado, preço={$price}\n";
                $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>Valor de venda igual a R$0,00 é preciso informar um valor maior que zero.</li></ul> <strong>SKU</strong>: {$sku}<br><strong>ID Eccosys</strong>: {$idProduct}", "W");
                continue;
            }

            // produto com o mesmo preço, não será atualizado
            if ($verifyProduct['price'] == $price) {                
                continue;
            }

            $productUpdate = $this->product->updatePrice($sku, $price);

            if (!$productUpdate) {
                echo "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro\n";
                $this->log_data('batch', $log_name, "Não foi possível atualizar o preço do produto={$idProduct}, sku={$sku} encontrou um erro, dados_item_lista=" . json_encode($product), "W");
                $this->log_integration("Erro para atualizar o preço do produto {$sku}", "<h4>Não foi possível atualizar o preço do produto</h4> <ul><li>SKU: {$sku}</li><li>ID Eccosys: {$idProduct}</li></ul>", "E");
                continue;
            }

            $this->log_data('batch', $log_name, "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price} ID={$idProduct}" . json_encode($product), "I");           
            echo "Produto atualizado preço!!! SKU={$sku} preco_anterior={$verifyProduct['price']} novo_preco={$price}\n";
            $this->log_integration("Preço do produto {$sku} atualizado", "<h4>O preço do produto {$sku} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$price}", "S");

        }
    }
}