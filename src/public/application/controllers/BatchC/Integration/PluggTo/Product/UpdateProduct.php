<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/PluggTo/Product/UpdateProducts run
 *
 */

require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";
require APPPATH . "controllers/BatchC/Integration/PluggTo/Product/Product.php";

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
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
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

        // Grava a última execução
        $this->saveLastRun();

        // Recupera os produtos
        $this->getProducts();

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
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $supplier_id = $this->getIDuserSellerByStore($this->store);

        // começando a pegar os produtos para criar
        $this->getListProducts($supplier_id);
    }

    /**
     * Recupera a lista para cadastro do produto
     *
     * @param   int         $supplier_id Código da loja na PluggTo
     * @return  bool
     * @throws  Exception
     */
    public function getListProducts(int $supplier_id): bool
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        if (empty($supplier_id)) {
            echo "Id conta plugg to nao encontrado. \n";
            //$this->log_data('batch', $log_name, "Id conta plugg to nao encontrado.", "W");
            return false;
        }

        $haveProductList = true;
        $access_token = $this->getToken();
        $ult_prod = null;

        while ($haveProductList) {

            $dateTimeStart      = new DateTime();
            $dateTimeEnd        = new DateTime();
            $interval           = new DateInterval("P1D");
            $interval->invert   = 1;

            $dateTimeStart->add($interval);

            $dateTimeStart = $dateTimeStart->format('Y-m-d');
            $dateTimeEnd   = $dateTimeEnd->format('Y-m-d');

            // Começando a pegar os produtos para criar
            $url = "https://api.plugg.to/products?access_token=$access_token&supplier_id=$supplier_id&modified={$dateTimeStart}to$dateTimeEnd";
            if (!empty($ult_prod)) {
                $url .= "&next=$ult_prod";
            }
            echo "URL: {$url}\n";
            $dataProducts = json_decode(json_encode($this->sendREST($url)));

            if ($dataProducts->httpcode != 200) {
                $haveProductList = false;
                echo "Erro para buscar a listagem de url=$url, retorno=" . json_encode($dataProducts) . "\n";
                //$this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
                continue;
            }

            $prods = json_decode($dataProducts->content);
            if ($prods->total <= 0) {
                $haveProductList = false;
                echo "Lista de produtos vazia\n";
                //$this->log_data('batch', $log_name, "Lista de produtos vazia, retorno = " . json_encode($dataProducts), "W");
                continue;
            }

            $arrayProductErroCheck = array();
            $id_produto = null;

            // Inicia transação
            //$this->db->trans_begin();
            foreach ($prods->result as $product) {
                $prod = $product->Product;
                echo json_encode($prod) . "\n";
                $id_produto = $prod->id;
                $skuProduct = $prod->sku;
                $this->setUniqueId($id_produto); // define novo unique_id

                if (in_array($id_produto, $arrayProductErroCheck)) {
                    echo "Já tentou atualizar o ID=$id_produto e deu erro\n";
                    //$this->db->trans_rollback();
                    continue;
                }

                $verifyProduct = $this->product->getProductForIdErp($id_produto, $skuProduct);
                // existe o sku na loja, mas não esá com o registro do id da PluggTo
                if (!empty($verifyProduct)) {
                    $dirImage = $verifyProduct['image'];
                    //atualiza imagens
                    if ($this->uploadproducts->countImagesDir($dirImage) > 0) {
                        unset($prod->photos);
                    }

                    $prod->path_images = $dirImage;
                    $productUpdate = $this->product->updateProduct($prod);

                    if ($productUpdate['success'] === false) {
                        echo "Não foi possível atualizar o produto=$id_produto encontrou um erro, retorno = " . json_encode($productUpdate) . "\n";
                        //$this->db->trans_rollback();
                        $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $id_produto)); // produto atualizado com o ID PluggTo

                        // adiciono no array para não consultar mais esse produto pai
                        if (!in_array($id_produto, $arrayProductErroCheck)) {
                            array_push($arrayProductErroCheck, $id_produto);
                        }
                        continue;
                    }

                    if ($productUpdate['success'] === true) {
                        $this->log_integration("Produto $skuProduct atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto $skuProduct, foi atualizado com sucesso</li></ul><br><strong>ID PluggTo</strong>: {$prod->id}<br><strong>SKU</strong>: $skuProduct<br><strong>Nome do produto</strong>: $prod->name", "S");
                        $this->log_data('batch', $log_name, "Produto atualizado!!! payload=" . json_encode($prod) . 'backup_payload_prod' . json_encode($verifyProduct), "I");
                        echo "Produto SKU=$skuProduct atualizado com sucesso\n";
                    }
                }
            }

            if ($id_produto) {
                $ult_prod = $id_produto;
            }
        }
        /*if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
        }*/
        //$this->db->trans_commit();
        return true;
    }
}
    
