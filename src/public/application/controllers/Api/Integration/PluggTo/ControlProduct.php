<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";
require APPPATH . "controllers/Api/Integration/PluggTo/UpdateProduct.php";
require APPPATH . "controllers/Api/Integration/PluggTo/UpdateOrder.php";


class ControlProduct extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $token;
    public $store;
    public $company;
    public $appKey;    
    public $formatReturn = "json";
    public $product;
    public $type;       
    public $updateProduct;
    public $updateOrder;
   

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_products');
        $this->load->model('model_stores');
        $this->load->model('model_settings');

        
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens
      
        $this->updateProduct = new UpdateProduct($this);
        $this->updateOrder = new UpdateOrder($this);
        header('Integration: v1');
    }
   
    public function index_post()
    {

        // Recupera dados enviado pelo body
        $webhook = json_decode(file_get_contents('php://input'));

        $this->log_data('api', 'pluggto/validate', "chegou json=".json_encode($webhook) , "E");

        $type           = $webhook->type;
        $id             = $webhook->id;
        $user           = $webhook->user;
        $action         = $webhook->action;

        $this->setDataIntegration($user);
                
        $changes        = $webhook->changes;
        $status         = $webhook->changes->status;
        $price          = $webhook->changes->price;
        $stock          = $webhook->changes->stock;

        //$apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        //$store = $this->getStoreForApiKey($apiKey);
        // define configuração da integração
        $this->getToken();

        if (!$this->token) {
            $this->log_data('api', 'pluggto/validate', 'apiKey não localizado para nenhuma loja', "E");
            $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        // não esperado
        if ($type === null) {
            $this->log_data('webhook', 'pluggto/validate', 'Notificações não configurada. RECEBIDO='.json_encode($webhook), "E");
            $this->response('notificação não esperada', REST_Controller::HTTP_NO_CONTENT);
            return false;
        }
        
        if($type == "products"){
            if($action == "updated"){
                if($this->updateProduct->update($id, $user, $changes, $this->token)) {
                  return $this->response(null, REST_Controller::HTTP_OK);
                }
            } elseif($action == "created"){
                if($this->updateProduct->create($id, $user, $changes, $this->token)) {
                  return $this->response(null, REST_Controller::HTTP_OK);
                }
            }

            return $this->response(null, REST_Controller::HTTP_NO_CONTENT);
        }
        
        if($type == "orders"){
            if($action == "updated"){
                if($this->updateOrder->update($id, $user, $changes, $this->token)) {
                    return $this->response(null, REST_Controller::HTTP_OK);
                }
            }

            return $this->response(null, REST_Controller::HTTP_NO_CONTENT);
        }
        
        
        return $this->response(null, REST_Controller::HTTP_NO_CONTENT);
          
    }

    /**
     * Define os dados para integração
     *
     * @param int $user_id
     * @return array
     */
    public function setDataIntegration(int $user_id): array
    {
        $credentials_pluggto = $this->model_settings->getSettingDatabyName('credencial_pluggto');
        $credentials = json_decode($credentials_pluggto['value']);

        if (!isset($credentials->client_id_pluggto)) {
            return array('success' => false, 'message' => ['Pluggto ainda não está ativa nesse ambiente.']);
        }

        // recupera a loja e identifica a company_id
        $credentialsSeller = $this->db->from('api_integrations')->like('credentials', "\"user_id\":\"{$user_id}")->get()->row_array();

        if (!$credentialsSeller) {
            return array('success' => false, 'message' => ['Usuário PluggTo não encontrado.']);
        }

        $dataStore = $this->model_stores->getStoresData($credentialsSeller['store_id']);

        $this->setStore($dataStore['id'] ?? $credentialsSeller['store_id']);
        $this->setCompany($dataStore['company_id']);

        return array('success' => true);
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


    public function getToken(){
        
        $credentials_pluggto = $this->model_settings->getSettingDatabyName('credencial_pluggto');
        $credentials = json_decode($credentials_pluggto['value']);

        if(!isset($credentials->client_id_pluggto))
            return false;
        
        // Busca por token - válido por 1 hora.            
        $urlAuth = "https://api.plugg.to/oauth/token";
        $dataAuth = "grant_type=password&client_id=$credentials->client_id_pluggto&client_secret=$credentials->client_secret_pluggto&username=$credentials->username_pluggto&password=$credentials->password_pluggto";        
        

        $authResult = json_decode(json_encode($this->sendREST($urlAuth, $dataAuth, 'POST', true, 'Content-Type: application/x-www-form-urlencoded')));
        if($authResult->httpcode != 200){            
            return false;
        }

        $authResult = json_decode($authResult->content);

        $this->setToken($authResult->access_token);

    }


    public function sendREST($url, $data = '', $method = 'GET', $newRequest = true, $header_opt = array())
    {
        $curl_handle = curl_init();

        if ($method == "GET") {
            curl_setopt($curl_handle, CURLOPT_URL, $url);
        } elseif ($method == "POST" || $method == "PUT") {

            if ($method == "PUT")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            
            if ($method == "POST")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');          

            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        }

        if($header_opt == 'Content-Type: application/x-www-form-urlencoded'){
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(                
                "Content-Type: application/x-www-form-urlencoded",                    
            ));
        }else{
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(                
                "Content-Type: application/json",                                    
            ));
        }            

        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content']  = $response;         

        return $header;
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
}