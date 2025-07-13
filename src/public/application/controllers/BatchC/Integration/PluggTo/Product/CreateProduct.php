<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration/PluggTo/Product/CreateProduct run
 *
 ***************-| FINAL DO PROGRAMA |-**************
 * (X) Criar sleep de 1 minuto quando exceder limite da requisições
 * ( ) Existe variação na conecta lá, mas foi excluida no ERP, comparar para remover da conecta lá ?
 *
 */

require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";
require APPPATH . "controllers/BatchC/Integration/PluggTo/Product/Product.php";

class CreateProduct extends Main
{
    private $product;
    private $dateNow;
    public $argParams = [];

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

    public function buildArgsData($argsData, $fields, $value, $i)
    {
        if (isset($fields[$i]) && !isset($argsData[$fields[$i]])) {
            $argsData = array_merge($argsData, [$fields[$i] => []]);
        }
        if (count($fields) > $i) {
            $child = $this->buildArgsData($argsData[$fields[$i]], $fields, $value, ($i + 1));
            if (is_array($child)) {
                $argsData[$fields[$i]] = array_merge($argsData[$fields[$i]], $child);
            } else {
                $argsData = array_merge($argsData, [$fields[$i] => $child]);
            }
        } else {
            return $value ?? '';
        }
        return $argsData;
    }

    public function parseArgs($args)
    {
        foreach ($args as $k => $arg) {
            if (!strpos($arg, ':')) {
                continue;
            }
            $valuePos = substr($arg, strpos($arg, ':') + 1);
            $keyPos = substr($arg, 0, strpos($arg, ':'));
            if (strlen($valuePos) > 1) {
                $fields = explode('.', $keyPos);
                $this->argParams = $this->buildArgsData($this->argParams, $fields, $valuePos, 0);
            }
        }
    }

    // php index.php BatchC/Integration/PluggTo/Product/CreateProduct run null 68
    public function run($id = null, $store = null)
    {
        $this->parseArgs(func_get_args());
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }
        
        $this->store =  $store;
        /* inicia o job */
        $this->setIdJob($id);

        $supplier_id = $this->getIDuserSellerByStore($store);
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

        $arrDadosJobIntegration = $this->model_integrations->getJobForJobAndStore('CreateProduct', $store);

        $dtLastRun = $arrDadosJobIntegration['last_run'] ?? null;
        if ($dtLastRun) {
            $dtLastRun = date_format(new DateTime($dtLastRun), 'Y-m-d');
        }
        // Grava a última execução
        $this->saveLastRun();
        $this->dateNow = date("Y-m-d");

        // Recupera os produtos
        $this->getProducts($dtLastRun, $supplier_id);


        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }


    /**
     * Recupera os produtos
     *
     * @return bool
     */
    public function getProducts(?string $dtLastRun, int $supplier_id): bool
    {
        $ult_prod = "";

        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        if ($supplier_id == null || $supplier_id == "") {
            echo "Id conta plugg to nao encontrado. \n";
            //$this->log_data('batch', $log_name, "Id conta plugg to nao encontrado.", "W");
            return false;
        }

        $haveProductList = true;
        $totalProdsCadastrados = 0;
        $access_token = $this->getToken();

        while ($haveProductList) {

            if (isset($this->argParams['sku'])) {
                $haveProductList = false;
                // Começando a pegar os produtos para criar
                $url = "https://api.plugg.to/skus/{$this->argParams['sku']}?access_token=$access_token&limit=100&supplier_id=$supplier_id";
                $dataProducts = json_decode(json_encode($this->sendREST($url)));
                if (isset($dataProducts->content)) {
                    $dataProducts->content = json_encode([
                        'total' => 1,
                        'result' => [json_decode($dataProducts->content)]
                    ]);
                } else {
                    continue;
                }
            } else {
                // Começando a pegar os produtos para criar
                $url = "https://api.plugg.to/products?access_token=$access_token&limit=100&supplier_id=$supplier_id";
                if ($dtLastRun) {
                    /**
                     * @todo rever se quando cria um produto a data de modificação vem preenchida com a mesma data que de criação.
                     */
                    $url .= "&modified={$dtLastRun}to" . $this->dateNow;
                }
                if (!empty($ult_prod)) {
                    $url = "&next=$ult_prod";
                }
                $dataProducts = json_decode(json_encode($this->sendREST($url)));
            }

            if ($dataProducts->httpcode != 200) {
                $haveProductList = false;
                echo "Erro para buscar a lista de produto\n$url\n";
                if ($dataProducts->httpcode != 99) {
                    $this->log_data('batch', $log_name, "Erro para buscar a lista de url=$url, retorno=" . json_encode($dataProducts), "W");
                }
                continue;
            }

            $prods = json_decode($dataProducts->content);

            if ($prods->total <= 0) {
                $haveProductList = false;
                echo "Lista de produtos vazia.\n$url\n".json_encode($dataProducts)."\n";
                //$this->log_data('batch', $log_name, "Lista de produtos vazia, retorno = " . json_encode($dataProducts), "W");
                continue;
            }
            $id_produto = null;

            foreach ($prods->result as $index => $product) {

                /*
                 * PEDRO
                if (ENVIRONMENT === 'development') {
                    echo "=============[DEBUG=ON]\n";
                    if ($index !== 0) {
                        dd('==================== EXIT');
                    }

                    $urlDebug   = "https://api.plugg.to/skus/3157-TAPETEESTAMPA?access_token={$access_token}";
                    $prdDebug   = json_decode(json_encode($this->sendREST($urlDebug)));
                    $product    = json_decode($prdDebug->content);
                }*/

                echo  "------------------------------------------------------------\n";
                $prod = $product->Product;
                $id_produto = $prod->id;
                $skuProduct = $prod->sku;
                $nomeProd = $prod->name;

                // Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($id_produto, $skuProduct);
                if (empty($verifyProduct)) {
                    // Inicia transação
                    // $this->db->trans_begin();

                    $this->setUniqueId($id_produto); // define novo unique_id
                    echo "Novo produto identificado...\n";
                    echo "Cadastrando produto .............\n";
                    $productCreate = $this->product->createProduct($prod);
                    if (!$productCreate['success']) {
                        //$this->db->trans_rollback();
                        //echo "Não foi possível cadastrar o product_id={$id_produto},name={$prod->name},sku={$skuProduct} encontrou um erro\nretorno=" . json_encode($productCreate) . "\n";
                        echo "Produto product_id=$id_produto, nome= $prod->name, SKU = $skuProduct não cadastrado:" . json_encode($productCreate['message']) . "\n";
                        $this->log_integration("Erro para integrar produto - $prod->sku", 'Existem algumas pendências no cadastro do produto na plataforma de integração <ul><li>' . implode('</li><li>', $productCreate['message']) . "</li></ul> <br> <strong>ID PluggTo</strong>: $id_produto<br><strong>SKU</strong>: $prod->sku<br><strong>Nome do Produto</strong>: $nomeProd", "E");
                        continue;
                    }

                    $this->log_data('batch', $log_name, "Produto PluggTo cadastrado\n".json_encode($prod));
                    echo "Produto {$prod->name}, SKU = $skuProduct cadastrado com sucesso\n";
                    $this->log_integration("Produto $skuProduct integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto $skuProduct, foi criado com sucesso</li></ul><br><strong>ID PluggTo</strong>: $id_produto<br><strong>SKU</strong>: $skuProduct<br><strong>Nome do Produto</strong>: $nomeProd", "S");
                    $totalProdsCadastrados++;

                    /*if ($this->db->trans_status() === false) {
                        $this->db->trans_rollback();
                        echo "ocorreu um erro com a transação\n";
                        $this->log_integration("Erro para integrar produto - $prod->sku", 'Ocorreu uma instabilidade para cadastro o produto. Em instantes será feito uma nova tentativa!', "E");
                    }*/

                    //$this->db->trans_commit();

                } else {
                    echo "Produto $skuProduct já cadastrado..\n";
                }

            }

            if ($id_produto) {
                $ult_prod = $id_produto;
            }
        }

        //$this->getProducts($dtLastRun, $supplier_id, $ult_prod);

        echo "\nTotal de produtos cadastrados = $totalProdsCadastrados\n";
        return true;
    }
}    
    

