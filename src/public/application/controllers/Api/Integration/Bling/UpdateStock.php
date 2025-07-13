<?php

require APPPATH . "/libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/Bling/Product/Product.php";

class UpdateStock extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $apiKey;
    public $token;
    public $store;
    public $company;
    public $product;
    public $multiStore;
    public $formatReturn = "json";
    public $countAttempt = 1;
    public $generalStock;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_products');
        $this->load->model('model_stores');

        $this->product = new Product($this);
        ini_set('display_errors', 0);
        header('Integration: v1');
    }

    /**
     * Atualização de estoque, deve ser recebido via POST
     */
    public function index_put()
    {
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook', 'WebHookUpdateProd', 'Chegou PUT, não deveria - GET='.json_encode($_GET).' - PAYLOAD='.json_encode($product), "E");

    }

    /**
     * Atualização de estoque
     */
    public function index_post()
    {
        $this->setJob('WeebHook-UpdateStock');
        //$this->log_data('WebHook', 'WebHookUpdateProd', 'Novo registro para atualização de estoque', "I");
        if (!isset($_GET['apiKey'])) {
            $this->log_data('WebHook', 'WebHookUpdateProd - Valid', 'Não foi encontrado a parâmetro apiKey, GETs='.json_encode($_GET), "E");
            $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store = $this->getStoreForApiKey($apiKey);
        if (!$store) {
            $this->log_data('WebHook', 'WebHookUpdateProd - Valid', "apiKey não localizado para nenhuma loja, apiKey={$_GET['apiKey']}", "E");
            $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        // define configuração da integração
        $this->setDataIntegration($store);

        // define multiloja de preço, caso tenha
        if ($this->multiStore === false) {
            $this->log_data('WebHook', 'WebHookUpdateProd - Valid', "Multiloja Não Encontrada - A loja está configurada para usar uma Multiloja, mas não foi encontrada da bling, apiKey={$_GET['apiKey']}", "E");
            $this->response('A loja está configurada para usar uma Multiloja, mas não foi encontrada da bling', REST_Controller::HTTP_OK);
            return false;
        }

        // Recupera dados enviado pelo body
        $product = json_decode(str_replace('data=', '', file_get_contents('php://input')));

//        $this->log_data('WebHook', 'WebHookUpdateProd - Start - 1', json_encode($product), "I");
//        $this->log_data('WebHook', 'WebHookUpdateProd - Start - 2', json_encode(file_get_contents('php://input')), "I");

        if ($product == null)
            return $this->response(null, REST_Controller::HTTP_OK);

        $products = (array)$product->retorno->estoques;

        foreach ($products as $product) {
            $sku = $product->estoque->codigo;
            
            // Inicia transação
            $this->db->trans_begin();
            $this->setUniqueId($sku); // define novo unique_id

            $update = $this->product->updateStock($product->estoque);

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->response('Ocorreu um problema para atualizar o preço do produto', REST_Controller::HTTP_OK);
                return false;
            }

            $estoque = $this->product->getGeneralStock($product->estoque->depositos, $product->estoque->estoqueAtual);
            if ($update !== false) {
                $this->db->trans_commit();
                $msg = $update === null ? "Não encontrou o produto/variação na multiloja" : "Atualizado com sucesso!";
                $status = $update === null ? "W" : "I";
                if ($update === null) {
                    $this->log_integration("Foi tentado atualizar o estoque/variação do produto {$sku}-WEBHOOK", "<h4>Não foi encontrado o produto/variação na Multiloja</h4> <strong>SKU</strong>: {$sku}", "E");
                } else {
                    if ($this->product->getProductForSku($sku))
                        $this->log_integration("Estoque do produto {$sku} atualizado-WEBHOOK", "<h4>O estoque do produto {$sku} foi atualizado com sucesso.</h4><strong>Estoque alterado:</strong> {$estoque}", "S");
                    else
                        $this->log_integration("Estoque da variação {$sku} atualizada-WEBHOOK", "<h4>O estoque da variação {$sku} foi atualizado com sucesso.</h4><strong>Estoque alterado:</strong> {$estoque}", "S");
                }
                $this->log_data('WebHook', 'WebHookUpdateProd - Finish', $msg . '. payload=' . json_encode($product), $status);
//                return $this->response(null, REST_Controller::HTTP_OK);
                continue;
            }
            // api bloqueada ou ocorreu algum erro
            echo "api bloqueada ou ocorreu algum erro\n";

            $this->db->trans_rollback();
            //$this->log_data('WebHook', 'WebHookUpdateProd - Finish', 'Foi tentado atualizar o estoque de um produto/variação que não está cadastrado. payload=' . json_encode($product), "W");
        }
        return $this->response(null, REST_Controller::HTTP_OK);
    }

    /**
     * Recupera a loja pelo apiKey
     *
     * @param string    $apiKey ApiKey de callback
     * @return int|null         Retorna o código da loja, ou nulo caso não encontre
     */
    public function getStoreForApiKey($apiKey)
    {
        $query = $this->db->get_where('stores', array('token_callback'  => $apiKey))->row_array();
        return $query ? (int)$query['id'] : null;
    }

    /**
     * Define o token de integração
     *
     * @param string $token Token de integraçõa
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Define a loja
     *
     * @param int $store Código da loja
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * Define a empresa
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Define a Multiloja, caso exista
     */
    public function setMultiStore($multiStore)
    {
        $this->multiStore = $multiStore;
    }

    /**
     * Define o job
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * Define o unique id
     */
    public function setUniqueId($uniqueId)
    {
        $this->unique_id = $uniqueId;
    }

    /**
     * Define generalStock
     */
    public function setGeneralStock($generalStock)
    {
        $this->generalStock = $generalStock;
    }

    /**
     * Cria um log da integração para ser mostrada ao usuário
     *
     * @param   string      $title          Título do log
     * @param   string      $description    Descrição do log
     * @param   string      $type           Tipo de log
     * @return  bool                        Retornar o status da criação do log
     */
    public function log_integration($title, $description, $type)
    {
        $data = array(
            'store_id'      => $this->store,
            'company_id'    => $this->company,
            'title'         => $title,
            'description'   => $description,
            'type'          => $type,
            'job'           => $this->job,
            'unique_id'     => $this->unique_id,
            'status'        => 1
        );

        // verifica se existe algum log para não duplicar
        $logExist = $this->db->get_where('log_integration',
            array(
                'store_id'      => $this->store,
                'company_id'    => $this->company,
                'description'   => $description,
                'title'         => $title
            )
        )->row_array();
        if ($logExist && ($type == 'E' || $type == 'W')) {
            $data['id'] = $logExist['id'];
            $data['type'] = $type;
        }

        return $this->db->replace('log_integration', $data);
    }

    /**
     * Define os dados para integração
     *
     * @param int $store_id Código da loja
     */
    public function setDataIntegration($store_id)
    {
        $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
        $dataStore       = $this->model_stores->getStoresData($store_id);

        $credentials = json_decode($dataIntegration['credentials']);

        $this->setStore($store_id);
        $this->setToken($credentials->apikey_bling);
        $this->setCompany($dataStore['company_id']);
        $this->setMultiStore($credentials->loja_bling);
        $this->setGeneralStock(!isset($credentials->stock_bling) || $credentials->stock_bling == '' ? null : $credentials->stock_bling);
    }
}