<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/Vtex/Product/Product.php";
require APPPATH . "controllers/Api/Integration/Vtex/UpdateStock.php";
require APPPATH . "controllers/Api/Integration/Vtex/UpdatePrice.php";
require APPPATH . "controllers/Api/Integration/Vtex/UpdateProduct.php";
require APPPATH . "controllers/Api/Integration/Vtex/UpdateStatus.php";

class ControlProduct extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $token;
    public $store;
    public $company;
    public $appKey;
    public $accountName;
    public $environment;
    public $salesChannel;
    public $affiliateId;
    public $formatReturn = "json";
    public $product;
    public $typeAction;
    public $updateStock;
    public $updatePrice;
    public $updateProduct;
    public $updateStatus;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_products');
        $this->load->model('model_stores');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product          = new Product($this);
        $this->updateStock      = new UpdateStock($this);
        $this->updatePrice      = new UpdatePrice($this);
        $this->updateProduct    = new UpdateProduct($this);
        $this->updateStatus     = new UpdateStatus($this);
        header('Integration: v1');
    }

    /**
     * Atualização de estoque, receber via POST
     */
    public function index_post()
    {
        if(!isset($_GET['apiKey'])) {
            $this->log_data('api', 'vtex/validate', 'Não foi encontrado a parâmetro apiKey', "E");
            $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }
        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store = $this->getStoreForApiKey($apiKey);
        if (!$store) {
            $this->log_data('api', 'vtex/validate', "apiKey não localizado para nenhuma loja. apiKey={$apiKey}", "E");
            $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        // define configuração da integração
        $this->setDataIntegration($store);

        // Recupera dados enviado pelo body
        $product = json_decode(file_get_contents('php://input'));


        $this->log_data('api', 'vtex', json_encode($product), "E");
        return $this->response(false, REST_Controller::HTTP_OK);



        $idProduct      = $product->IdSku;
        $skuProduct     = $product->IdSku;
        $account_name   = $product->An;
        $affiliate_id   = $product->IdAffiliate;

        // accountName ou idAffiliate não correspondem, ignorar
        if ($account_name != $this->accountName || $affiliate_id != $this->affiliateId) {
            return $this->response(false, REST_Controller::HTTP_OK);
        }

        $verifyProduct = $this->product->getProductForIdErp($idProduct);
        $this->setUniqueId($idProduct); // define novo unique_id
        $typeAction = null;

        if (empty($verifyProduct)) { // não encontrou o produto pelo id_erp
            $verifyProduct = $this->product->getProductForSku($skuProduct);
            if (empty($verifyProduct)) // não encontro também pelo sku, provavelmente não existe
                return $this->response(null, REST_Controller::HTTP_OK); // produto não existe ainda
            else // encontrou pelo sku, mas não tem o id_erp, será atualizado
                $this->product->updateProductForSku($skuProduct, array('variant_id_erp' => $idProduct)); // produto atualizado com o ID Vtex
        }

        // produto estava inativo na conecta lá, mas ativo na vtex, ativar na conecta lá
        if ($verifyProduct['status'] != 1 && $product->IsActive)
            $this->updateStatus->updateStatus($idProduct, 1);

        // tipos de notificação
        if ($product->StockModified) $typeAction = 'S'; // atualização de estoque
        elseif ($product->PriceModified) $typeAction = 'P'; // atualização de preço
        elseif ($product->HasStockKeepingUnitModified) $typeAction = 'U'; // alterar o produto
        elseif ($product->HasStockKeepingUnitRemovedFromAffiliate) $typeAction = 'X'; // produto removido do afiliado, inativar

        // não esperado
        if ($typeAction === null) {
            $this->log_data('webhook', 'vtex/validate', 'Notificações não configurada. RECEBIDO='.json_encode($product), "E");
            $this->response('notificação não esperada', REST_Controller::HTTP_OK);
            return false;
        }

        switch ($typeAction) {
            case 'S': // atualizar estoque
                $update = $this->updateStock->updateStock($idProduct);
                break;
            case 'U': // atualizar produto
                $update = $this->updateProduct->updateProduct($idProduct);
                break;
            case 'P': // atualizar preço
                $update = $this->updatePrice->updatePrice($idProduct);
                break;
            case 'X': // removido da afiliação
                $update = $this->updateStatus->updateStatus($idProduct, 2); // ver como irá funcionar
                break;
            default:
                return $this->response(null, REST_Controller::HTTP_OK);
        }

        return $this->response(null, REST_Controller::HTTP_NO_CONTENT);
    }

    /**
     * Recupera a loja pelo apiKey
     *
     * @param   string  $apiKey ApiKey de callback
     * @return  int|null        Retorna o código da loja, ou nulo caso não encontre
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
        $this->company = (int)$company;
    }

    /**
     * Define AppKey
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;
    }

    /**
     * Define AccountName
     */
    public function setAccountName($accountName)
    {
        $this->accountName = $accountName;
    }

    /**
     * Define environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Define salesChannel
     */
    public function setSalesChannel($salesChannel)
    {
        $this->salesChannel = $salesChannel;
    }

    /**
     * Define salesChannel
     */
    public function setAffiliateId($affiliateId)
    {
        $this->affiliateId = $affiliateId;
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

        if (empty($dataIntegration)) {
            return false;
        }
        $credentials = json_decode($dataIntegration['credentials']);
        if (!is_object($credentials) || !$credentials) {
            return false;
        }

        $this->setStore($store_id);
        $this->setCompany($dataStore['company_id']);
        $this->setToken($credentials->token_vtex);
        $this->setAppKey($credentials->appkey_vtex);
        $this->setAccountName($credentials->account_name_vtex);
        $this->setEnvironment($credentials->environment_vtex);
        $this->setSalesChannel($credentials->sales_channel_vtex);
        $this->setAffiliateId($credentials->affiliate_id_vtex);
        return true;
    }
}