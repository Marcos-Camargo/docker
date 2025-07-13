<?php

require APPPATH . "controllers/BatchC/SellerCenter/RD/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

// php index.php BatchC/SellerCenter/RD/Brands sync null RD
class Brands extends Main
{
    var $auth_data;
    const FILENAME = 'brandsMapping.xlsx';
    var $brands_mapping = array();

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_integrations');
        $this->load->model('model_brands');
        $this->load->model('model_brands_marketplaces');
    }

    function sync($id = null, $params=null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        if(is_null($params)){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
            echo PHP_EOL . "FIM SYNC BRAND" . PHP_EOL;
            $this->log_data('batch',$log_name,'finish',"I");
            $this->gravaFimJob();
            die;
        }
        
        $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
        if($integration){
            $credentials = json_decode($integration['auth_data']);
            $auth = $this->auth($credentials->api_url, $credentials->grant_type, $credentials->client_id, $credentials->client_secret);
            
            echo 'Sync: '. $integration['int_to']."\n";
            $this->syncIntTo($credentials, $auth, $params, 0, 1000);
        }

        echo PHP_EOL . "FIM SYNC MARCAS" . PHP_EOL;
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();

    }

    function syncIntTo($credential, $auth, $int_to, $page, $size)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $endPoint   = '/marketplace/catalogs/brands?page='.$page.'&size='.$size;
        $this->auth_data = $credential;

        $this->process($credential, $auth, $endPoint);

        if ($this->responseCode != 200) {
            $erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar brands";
            echo $erro . "\n";
            $this->log_data('batch', $log_name, $erro, "E");
            die;
        }

        $brands = json_decode($this->result);
        foreach($brands as $brand) {
            $brandName = mb_strtolower($brand->name);
            $localbrand['id'] = $this->model_brands->getBrandbyName($brandName);

            
            if (!$localbrand['id']) { // ainda não exite, crio

                $localbrand = array(
                    'name' => $brandName,
                    'active' => 1,
                );

                echo "Criando " . $brandName . "\n";
                $brand_id = $this->model_brands->create($localbrand);
                $localbrand['id'] = $brand_id;

            }

            $data = array(
                'int_to' => $int_to,
                'brand_id' => $localbrand['id'],
                'id_marketplace' => $brand->id,
                'name' => $brand->name,
                'isActive' => 1,
                'title' => $brand->name,
            );

            echo "Criando " . $brandName . "\n";
            $brand_id = $this->model_brands_marketplaces->createOrUpdateIfChanged($data);
        }

        if (count($brands) > 0) {
            $this->syncIntTo($credential, $auth, $int_to, $page + 1, $size);
        }
    }
}
