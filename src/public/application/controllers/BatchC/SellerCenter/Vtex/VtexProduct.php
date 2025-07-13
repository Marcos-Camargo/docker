<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class VtexProduct extends Main {
    var $int_to='';
    var $apikey='';
    var $site='';
    var $appToken='';
    var $accountName='';
    var $environment='';

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setSuffixDns('.com');

        // carrega os modulos necessários para o Job
        $this->load->model('model_company');
        $this->load->model('model_stores');
        $this->load->model('model_integrations');
    }

    function setInt_to($int_to) {
        $this->int_to = $int_to;
    }
    function getInt_to() {
        return $this->int_to;
    }
    function setApikey($apikey) {
        $this->apikey = $apikey;
    }
    function getApikey() {
        return $this->apikey;
    }
    function setAppToken($appToken) {
        $this->appToken = $appToken;
    }
    function getAppToken() {
        return $this->appToken;
    }
    function setAccoutName($accountName) {
        $this->accountName = $accountName;
    }
    function getAccoutName() {
        return $this->accountName;
    }
    function setEnvironment($environment) {
        $this->environment = $environment;
    }
    function getEnvironment() {
        return $this->environment;
    }

    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        $this->syncProductsBySeller();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function syncProductsBySeller()
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $main_integrations = $this->model_integrations->getIntegrationsbyStoreId(0);

        foreach ($main_integrations as $integrationName) {
            echo 'Sync Products: '. $integrationName['int_to']. PHP_EOL;
            $this->syncSellerIntTo($main_integrations, $integrationName['int_to']);
        }
    }

    function syncSellerIntTo($main_integrations, $int_to) 
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $endPoint = '/api/catalog_system/pvt/seller/list';
        $this->process($int_to, $endPoint);

        $sellers = json_decode($this->result, true);

        foreach($sellers as $seller) {
            $company = $this->getCompany($int_to, $seller);

            if (is_null($company)) {
                $company_id = $this->saveCompany($main_integrations, $int_to, $seller);
            }
            else {
                $company_id = $company['id'];
            }

            if (!$this->existStore($company_id, $int_to, $seller)) {
                $this->saveStore($company_id, $int_to, $seller);
            }
        }
    }

    function getCompany($int_to, $seller) 
    {
        return $this->model_company->getCompanyDataByImportSellerId($int_to, $seller['SellerId']);
    }

    function saveCompany($int_to, $seller) 
    {
        $record = array(
            'name' => $seller['Name'],
            'CNPJ' => $seller['CNPJ'],
            'address' => '',
            'addr_num' => '',
            'addr_compl' => '',
            'addr_neigh' => '',
            'addr_city' => '',
            'addr_uf' => '',
            'country' => '',
            'phone_1' => '',
            'phone_2' => '',
            'zipcode' => '',
            'message' => '',
            'currency' => '',
            'logo' => '',
            'parent_id' => '',
            'prefix' => '',
            'email' => $seller['Email'],
            'reputacao' => '',
            'active' => 0
        );

        $this->db->insert('company', $record);
        return $this->db->insert_id();
    }

    function existStore($company_id, $int_to, $seller) 
    {
        $store = $this->model_stores->getStoresDataByImportSellerId($company_id, $int_to, $seller['SellerId']);
        return !is_null($store);
    }

    function saveStore($company_id, $int_to, $seller) 
    {
        $record = array(
            'company_id' => $company_id,
            'name' => $seller['Name'],
            'CNPJ' => $seller['CNPJ'],
            'raz_social' => '',
            'address' => '',
            'addr_num' => '',
            'addr_compl' => '',
            'addr_neigh' => '',
            'addr_city' => '',
            'addr_uf' => '',
            'country' => '',
            'phone_1' => '',
            'phone_2' => '',
            'zipcode' => '',
            'prefix' => '',
            'responsible_name' => '',
            'responsible_cpf' => '',
            'responsible_email' => '',
            'bank' => '',
            'agency' => '',
            'account_type' => '',
            'account' => '',
            'token_api' => '',
            'responsible_name' => '',
            'business_street' => '',
            'business_addr_num' => '',
            'business_addr_compl' => '',
            'business_neighborhood' => '',
            'business_town' => '',
            'business_uf' => '',
            'business_nation' => '',
            'business_code' => '',
            'user_create' => '',
            'active' => 0
        );

        $this->db->insert('stores', $record);
        $store_id = $this->db->insert_id();

        $data_int = array(
            'name' => $main_integration['name'],
            'active' => $main_integration['active'],
            'store_id' => $store_id,
            'company_id' => $company_id,
            'auth_data' => json_encode(array('date_integrate'=>date("Y-m-d\TH:i:s",time()),'seller_id'=> $seller['SellerId'])),
            'int_type' => 'BLING',
            'int_from' => 'HUB',
            'int_to' => $main_integration['int_to'], 
            'auto_approve' => $main_integration['auto_approve'] 
        ); 
        $this->model_integrations->create($data_int); 

        return $store_id;
    }
}