<?php

class CreateAllTokens extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_stores');
        $this->load->library('JWT');
    }
    // php index.php BatchC/Automation/CreateAllTokens run null null
    function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            get_instance()->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        get_instance()->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
        $this->db->trans_begin();
        $stores = $this->model_stores->getWithoutTokens();
        foreach($stores as $keu=>$store){
            $company_id=$store["company_id"];
            $store_id=$store["id"];
            $token = $this->createTokenAPI($store_id, $company_id);
            $this->model_stores->update(array("token_api" => $token), $store_id);
        }
        $stores2 = $this->model_stores->getWithoutTokens();
        // dd(count($stores),count($stores2));
        // sleep(120);
        $this->db->trans_commit();
        get_instance()->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
    public function createTokenAPI($cod_store, $cod_company)
    {
        $key = get_instance()->config->config['encryption_key'];
        $payload = array(
            "cod_store" => $cod_store,
            "cod_company" => $cod_company
        );
        return $this->jwt->encode($payload, $key);
    }
    
}
