<?php

use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Null_;

require APPPATH . "controllers/BatchC/Marketplace/Vtex/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

class Brand extends Main
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
		$this->load->model('model_brands_vtex');
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
            echo 'Sync: '. $integration['int_to']."\n";
            $this->syncIntTo($integration);
        }

	}

	function syncIntTo($integrationData)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$endPoint   = 'api/catalog_system/pvt/brand/list/';
		$this->auth_data = json_decode($integrationData['auth_data']);
		$skuExist = $this->processNew($this->auth_data, $endPoint);

		if ($this->responseCode != 200) {
			$erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar brands";
			echo $erro . "\n";
			$this->log_data('batch', $log_name, $erro, "E");
			die;
		}

       	$brands = json_decode($this->result);
		foreach($brands as $brand) {
			$brandName = mb_strtolower($brand->name);
			$active = $brand->isActive;
			$localbrand = $this->model_brands_vtex->getBrandMktplaceByName($integrationData['int_to'], $brandName);
			if (!$localbrand) { // ainda não exite, crio 
				if($active){
					$localbrand = array(
						'name' => $brandName,
						'int_to' => $integrationData['int_to'],
						'external_id' => $brand->id
					);
					echo "Criando " . $brandName . "\n";
					$brand_id = $this->model_brands_vtex->create($localbrand);
				}
			}
		}
		
	}

}
