<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration/Eccosys/Product/CreateProduct run
 *
 ***************-| FINAL DO PROGRAMA |-**************
 * (X) Criar sleep de 1 minuto quando exceder limite da requisições
 * ( ) Existe variação na conecta lá, mas foi excluida no ERP, comparar para remover da conecta lá ?
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";
require APPPATH . "controllers/BatchC/Integration/Eccosys/Product/Product.php";

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
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('CreateProduct');
    }
    // php index.php BatchC/Integration/Eccosys/Product/CreateProduct run null 65
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
        
        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $ECCOSYS_URL = '';
        if ($dataIntegrationStore) {
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
        
        //$arrayProductErroCheck = array();
        $prods = json_decode($dataProducts->content);
        $totalProdsCadastrados = 0;
        // Inicia transação
        foreach ($prods as $prod) {
            if ($prod->situacao == 'A' && $prod->idProdutoMaster == '0') {                
                $id_produto = $prod->id;
                $skuProduct = $prod->codigo;

                // Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($id_produto, $skuProduct);
        		
        		

                if (empty($verifyProduct)) {
                    $urlEsp = $ECCOSYS_URL."/api/produtos/$prod->id";
                    $data = '';
                    $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $data)));
					
					$this->db->trans_begin();
                    $this->setUniqueId($id_produto); // define novo unique_id
                    echo "Novo produto identificado...\n";
                    echo "Buscado dados do produto -> {$id_produto}............\n";

                    if ($dataProduct->httpcode != 200) {
                        echo "Produto Eccosys com ID={$id_produto} encontrou um erro, retorno=" . json_encode($dataProduct) . "\n";
                        $this->db->trans_rollback();
                        if ($dataProduct->httpcode != 99) {
                            $this->log_data('batch', $log_name, "Produto Eccosys com ID={$id_produto} encontrou um erro, retorno=" . json_encode($dataProduct), "W");
                            $this->log_integration("Erro para integrar produto - ID Eccosys {$id_produto}", "Não foi possível obter informações do produto! <br> <strong>ID Eccosys</strong>:{$id_produto}", "E");
                        }
                        continue;
                    }

                    $product = json_decode($dataProduct->content);                
                    // Recupera o código do produto pai
                    $idProduct = $product->id;

                    //busca endpoint de imagens
                    $urlImg = $ECCOSYS_URL."/api/produtos/$id_produto/imagens";
                    $dataImg = '';
                    echo "Buscado imagens do produto -> {$id_produto}............\n";
                    $dataProductImgs = json_decode(json_encode($this->sendREST($urlImg, $dataImg)));
                
                    $product->anexos = '';

                    if ($dataProductImgs->httpcode == 200) {
                        $imgsResult = json_decode($dataProductImgs->content);
                        if (count($imgsResult)>0) {
                            echo "Total de imagens encontradas ".count($imgsResult)."\n";
                            $product->anexos = $imgsResult;
                        }
                    } else {
                        echo "Imagens não cadastradas ou com erro.\n";
                    }

                    if (count($imgsResult)>6) {                        
                        $productIdentifier = "SKU {$prod->codigo}";
                        $this->log_data('batch', $log_name,"Erro para integrar produto - {$productIdentifier} encontrou um erro, retorno= Produto chegou com mais imagens que o permitido <br><strong>ID Eccosys</strong>:". $idProduct, "E");
                        $this->log_integration("Erro para integrar produto - {$productIdentifier}", 'Produto chegou com mais imagens que o permitido <br><strong>ID Eccosys</strong>:'. $idProduct. '<br>', "E");
                    }
                    echo "Cadastrando produto .............\n";
                    $productCreate = $this->product->createProduct($product);
                
                    if (!$productCreate['success']) {
                        echo "Não foi possível cadastrar o produto={$idProduct} encontrou um erro.\n{$productCreate['message'][0]}\n";
                        $this->db->trans_rollback();
                        $productIdentifier = "SKU {$prod->codigo}";
                        $msg = empty($productCreate['message'][0]) ? '' : $productCreate['message'][0];
                        $this->log_data('batch', $log_name, "Produto Eccosys com ID={$productIdentifier} encontrou um erro, retorno=" . json_encode($productCreate), "W");
                        $this->log_integration("Erro para integrar produto - {​​$productIdentifier}​​", 'Existem algumas pendências no cadastro do produto na plataforma de integração retorno = ' . $msg. '<br><strong>ID Eccosys</strong>:'. $idProduct. '<br>', "E");
                        $created = false;
                        continue;
                    }
                    
                    $this->log_data('batch', $log_name, "Produto cadastrado!!! payload id = " . $prod->id, "I");
                    echo "Produto {$prod->id} cadastrado com sucesso\n";
                    $this->log_integration("Produto {$skuProduct} integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi criado com sucesso</li></ul><br><strong>ID Eccosys</strong>: {$product->id}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$product->nome}", "S");
                    $totalProdsCadastrados++;    
                    $this->db->trans_commit();
                    continue;
                } 
                if ($this->db->trans_status() === false) {
		            $this->db->trans_rollback();
		            echo "ocorreu um erro\n";
		        } 
			} 
			elseif ($prod->situacao == 'A' && $prod->idProdutoMaster != '0') {   // é uma variação; 
				echo " Verificando variação ".$prod->id." do produto ".$prod->idProdutoMaster."\n";
				$idProdutoMaster = $prod->idProdutoMaster;
				 // busca por variação
				$idProduct      = $prod->id;
           		$skuProduct     = $prod->codigo;
                $verifyProduct = $this->product->getVariationForSku($skuProduct);             
                
                if (empty($verifyProduct)) {
                	$verifyProduct = $this->product->getProductForIdErp($idProdutoMaster);
					if ($verifyProduct) { // Variação nova de um produto que já existe 
						if ($verifyProduct['status'] != 1) {
	                    	$this->db->trans_rollback();
	                        echo "Produto não está ativo\n";
	                        continue;
	                    } 
						$this->db->trans_begin();
						$createVariation = $this->product->createVariation($prod, $verifyProduct['sku']);
						if ($createVariation['success'] === false) {
							$msg = empty($createVariation['message'][0]) ? '' : $createVariation['message'][0];
	                        echo "Não foi possível criar a variação ID_eccosys={$idProduct} do produto {$idProdutoMaster} devido a um erro, retorno = " . $msg . "\n";
	                        $this->db->trans_rollback();
	                        continue;
	                    }
	                  
                        $this->db->trans_commit();
                        $this->log_data('batch', $log_name, "Variacao cadastrada para prd_id= ".$verifyProduct['id']."!!! payload=" . json_encode($prod) . 'backup_payload_prod' . json_encode($verifyProduct), "I");                        
                        echo "Variação ID_Eccosys={$idProduct}, SKU={$skuProduct} para produto ID_Eccosys={$idProdutoMaster} prd_id = {$verifyProduct['id']} criado com sucesso\n";                        
						continue; 
					}
					else { // variação de um produto q não foi cadastrado, ignoro
						echo " Produto pai ainda não cadastrado\n";
						continue;
					} 
				}
				else {
					echo " Variação já cadastrada\n";
				}

			}            
        }

                

        $this->db->trans_commit();

        echo "\n----------------";
        echo "\nTotal de produtos cadastrados = {$totalProdsCadastrados}\n";
    }
}    
    

