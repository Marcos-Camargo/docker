<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

class UpdatePrice extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $apiKey;
    public $token;
    public $store;
    public $company;
    public $product;
    public $listPrice;
    public $formatReturn = "json";

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_products');
        $this->load->model('model_stores');

        $this->product = new Product($this);
        header('Integration: v1');
    }

    /**
     * Atualização de preço, deve ser recebido via POST
     */
    public function index_put()
    {
        $productPrice = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook', 'WebHookUpdatePrice', 'Chegou PUT, não deveria - GET='.json_encode($_GET).' - PAYLOAD='.json_encode($productPrice), "E");

    }

    /**
     * Atualização de preço
     */
    public function index_post()
    {
        ob_start();
        $this->setJob('WeebHook-UpdatePrice');
        //$this->log_data('WebHook', 'WebHookUpdateProd[NEW]', 'Novo registro', "I");
        if (!isset($_GET['apiKey'])) {
            $this->log_data('WebHook', 'WebHookUpdateProd - Valid', 'Não foi encontrado a parâmetro apiKey, GETs='.json_encode($_GET), "E");
            ob_clean();
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store = $this->getStoreForApiKey($apiKey);
        if (!$store) {
            $this->log_data('WebHook', 'WebHookUpdateProd - Valid', "apiKey não localizado para nenhuma loja, apiKey={$_GET['apiKey']}", "E");
            ob_clean();
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
        }

        // define configuração da integração
        $this->setDataIntegration($store);
        // define lista de preço, caso tenha
        if ($this->listPrice === false) {
            $this->log_data('WebHook', 'WebHookUpdateProd - Valid', "Lista de Preço Não Encontrada - A loja está configurada para usar uma lista de preço, mas não foi encontrada da tiny, apiKey={$_GET['apiKey']}", "E");
            ob_clean();
            return $this->response('A loja está configurada para usar uma lista de preço, mas não foi encontrada da tiny', REST_Controller::HTTP_OK);
        }

        $product = json_decode(file_get_contents('php://input'));

        if ($product->tipo != "precos") {
            $error = "Preço não atualizado";
            //$this->log_data('WebHook', 'UpdateStock/index_post', "Chegou um tipo de atualização diferente de preço. Chegou={$product->tipo} - payload=" . json_encode($product) , "W");
            $this->log_integration("Recebimento Indevido de Atualização de Preço", "Chegou um tipo de atualização diferente de preço. Chegou={$product->tipo}", "E");
            ob_clean();
            return $this->response($error, REST_Controller::HTTP_OK);
        }

        // Inicia transação
        $this->db->trans_begin();

        $this->setUniqueId($product->dados->skuMapeamento); // define novo unique_id
        $precoPromocional = $product->dados->precoPromocional  ?? $product->dados->preco_promocional;
        $price     = empty($precoPromocional ?? null) ? ($product->preco ?? null) : $precoPromocional;
        $update = $this->product->updatePrice($product->dados->skuMapeamento, $price );

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            ob_clean();
            return $this->response('Ocorreu um problema para atualizar o preço do produto', REST_Controller::HTTP_OK);
        }

        if ($update !== false) {
            $this->db->trans_commit();
            $msg = $update === null ? "Não encontrou o produto/variação na lista" : "Atualizado com sucesso!";
            $status = $update === null ? "W" : "I";
            if ($update === null) {
                $this->log_integration("Foi tentado atualizar o preço do produto {$product->dados->skuMapeamento}", "<h4>Não foi encontrado o produto/variação na lista de preço</h4> <strong>SKU</strong>: {$product->dados->skuMapeamento}", "E");
            } else {
                $tipoVariacao = $product->dados->skuMapeamentoPai == "" ? "N" : "V";

                if($tipoVariacao == "N")
                    $this->log_integration("Preço do produto {$product->dados->skuMapeamento} atualizado", "<h4>O preço do produto {$product->dados->skuMapeamento} foi atualizado com sucesso.</h4><strong>Preço alterado:</strong> {$product->dados->saldo}", "S");
                if($tipoVariacao == "V")
                    $this->log_integration("Preço da variação {$product->dados->skuMapeamento} atualizada", "<h4>O preço do produto {$product->dados->skuMapeamento} foi atualizado com sucesso.</h4><strong>Preço alterado:</strong> {$product->dados->saldo}", "S");
            }
            $this->log_data('WebHook', 'WebHookUpdateProd - Finish', $msg.'. payload='.json_encode($product), $status);
            ob_clean();
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        $this->db->trans_rollback();
        //$this->log_data('WebHook', 'WebHookUpdateProd - Finish', 'Foi tentado atualizar o preço de um produto/variação que não está cadastrado. payload='.json_encode($product), "W");
        ob_clean();
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
     * Define a lista de preço, caso exista
     */
    public function setListPrice($list)
    {
        if ($list == "") $list = null;

        $this->listPrice = $list;
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
        $this->setToken($credentials->token_tiny);
        $this->setCompany($dataStore['company_id']);
        $this->setListPrice($credentials->id_lista_tiny);
    }
}