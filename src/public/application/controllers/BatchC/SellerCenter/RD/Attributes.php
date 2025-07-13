<?php

require APPPATH . "controllers/BatchC/SellerCenter/RD/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

class Attributes extends Main
{
    var $auth_data;
    const FILENAME = 'attributesMapping.xlsx';
    var $attibutes_mapping = array();

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
        $this->load->model('model_attributes');
        $this->load->model('model_atributos_categorias_marketplaces');
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
            echo PHP_EOL . "FIM SYNC ATTRIBUTES" . PHP_EOL;
            $this->log_data('batch',$log_name,'finish',"I");
            $this->gravaFimJob();
            die;
        }

        $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
        if($integration){
            echo 'Sync: '. $integration['int_to']."\n";
            $auth_data = json_decode($integration['auth_data']);
            $auth_data_token = $this->auth($auth_data->api_url, $auth_data->grant_type, $auth_data->client_id, $auth_data->client_secret, $auth_data->scope );
            $auth_data_token = [
                'auth_data' => $auth_data,
                'auth_data_token' => $auth_data_token
            ];
            $this->syncIntTo($auth_data_token);
        
        }

        echo PHP_EOL . "FIM SYNC ATTRIBUTES" . PHP_EOL;
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();

    }

    function syncIntTo($integrationData)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $endPoint = '/marketplace/catalogs/attributes';
        $result = $this->process($integrationData['auth_data'], $integrationData['auth_data_token'], $endPoint);
        if ($this->responseCode != 200) {
            $erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar os atributos";
            echo $erro . "\n";
            $this->log_data('batch', $log_name, $erro, "E");
            die;
        }

        $attributes = json_decode($this->result);
        foreach($attributes as $attribute) {
            $attributeName = mb_strtolower($attributes->name);
            $localbrand['id'] = $this->model_brands->getAttributebyName($attributeName);

            if (!$localbrand) { // ainda não exite, crio

                $localbrand = array(
                    'name' => $attributeName,
                    'active' => 1,
                );

                echo "Criando " . $attributeName . "\n";
                $brand_id = $this->model_brands->create($localbrand);
                $localbrand['id'] = $brand_id;

            }

            $data = array(
                'int_to' => $integrationData['int_to'],
                'attribute_id' => $localbrand['id'],
                'id_marketplace' => $attribute->id,
                'name' => $attribute->name,
                'isActive' => 1,
                'title' => $attribute->name,
            );

            echo "Criando " . $attributeName . "\n";
            $brand_id = $this->model_brands_marketplaces->createOrUpdateIfChanged($data);
        }

    }

}
