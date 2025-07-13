<?php

/**
 * Class GetVariationsProducts
 *
 * php index.php BatchC/Integration/Eccosys/Product/GetVariationsProducts run null 20
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";
require APPPATH . "controllers/BatchC/Integration/Eccosys/Product/Product.php";

class GetVariationsProducts extends Main
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

        $this->setJob('GetVariationsProducts');
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            echo "Parametros informados incorretamente. ID={$id} - STORE={$store}\n";
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando produtos... \n";

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
            return false;
        }

        //$url    = 'https://bling.com.br/Api/v2/produtos/page=1';
        //$data   = "&loja={$this->multiStore}&estoque=S&imagem=S";
        $data = "";
        $url = ECCOSYS_URL.'/produtos/';
        $dataProducts = $this->sendREST($url, $data);

        if ($dataProducts['httpcode'] != 200) {
            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "E");
            $this->log_integration("Erro para integrar produtos", "Não foi possível consultar a listagem de produtos!!", "E");
            return false;
        }

        $contentProducts = json_decode($dataProducts['content']);
        $regProducts = $contentProducts->retorno->produtos;
        $haveProductList = true;
        $page = 1;

        while ($haveProductList) {
            if ($page != 1) {
                //$url    = 'https://Eccosys.com.br/Api/v2/produtos/page='.$page;
                //$data   = "&loja={$this->multiStore}&estoque=S&imagem=S";
                $data = "";
                $url = ECCOSYS_URL.'/produtos/';
                $dataProducts = $this->sendREST($url, $data);

                $contentProducts = json_decode($dataProducts['content']);

                if ($dataProducts['httpcode'] != 200) {
                    echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "E");
                    $this->log_integration("Erro para integrar produtos", "Não foi possível consultar a listagem de produtos!", "E");
                    if ($dataProducts['httpcode'] != 504 && $dataProducts['httpcode'] != 401)
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

                // Produto não é um pai
                if (!isset($registro->variacoes)) {
                    echo "Produto {$registro->codigo} não é um produto PAI\n";
                    continue;
                }

                $arrProducts[$registro->codigo] = array();

                foreach ($registro->variacoes as $variacao) {
                    array_push($arrProducts[$registro->codigo], array(
                        'sku' => $variacao->variacao->codigo,
                        'nome' => $variacao->variacao->nome,
                    ));
                }
            }
            $page++;
        }
        echo "\n\n\n";

        // SQL PARA PEGAR OS ID DE CADA SKU
        $arrSku = [];
        foreach($arrProducts as $key => $var) {
            $query = $this->db->get_where('products', array('sku' => $key, 'store_id' => $this->store));
            if($query->num_rows() == 0) continue;
            $result = $query->row_array();
            $arrSku[$result['sku']] = $result['id'];
        }

        // FAÇO OS UPDATE, AJUSTA O CAMPO name
        foreach($arrProducts as $skuPai => $vars) {

            if (!array_key_exists($skuPai, $arrSku)) continue;
            foreach((array)$vars as $var) {
                echo "UPDATE prd_variants SET sku = '{$var['sku']}' WHERE prd_id = {$arrSku[$skuPai]} AND name = '{$var['nome']}';\n";
            }
        }
//        echo json_encode($arrSku);
    }
}