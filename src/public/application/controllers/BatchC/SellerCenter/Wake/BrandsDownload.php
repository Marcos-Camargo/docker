<?php

use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Null_;

require APPPATH . "controllers/BatchC/SellerCenter/Wake/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') || exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

class BrandsDownload extends Main
{

  var $auth_data;
  const FILENAME = 'brandsMapping.xlsx';
  var $brands_mapping = array();

  public function __construct()
	{
		parent::__construct();

		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);

		// carrega os modulos necessários para o Job
		$this->load->model('model_integrations');
		$this->load->model('model_queue_products_marketplace'); 
		$this->load->model('model_brands');
		$this->load->model('model_brands_marketplaces');
		$this->load->model('model_settings');
        $this->load->library('excel');
	}

	// php index.php BatchC/SellerCenter/Wake/BrandsDownload run null Wake
	function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return;
		}
		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");	

		 /* faz o que o job precisa fazer */
		 if(is_null($params)  || ($params == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
			if($integration){
				$this->int_to=$integration['int_to'];
				echo 'Sync: '. $integration['int_to']."\n";
				$this->syncIntTo($integration);
				// coloco o brand como ativo se tiver pelo menos 1 ativo ou inativo se não tiver nenhum ativo
				$this->model_brands_marketplaces->setBrandsActiveOrInactive();
            }
			else {
				echo PHP_EOL .$params." não tem integração definida". PHP_EOL;
			}
		}

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

	function syncIntTo($integrationData)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;


			$endPoint  = 'fabricantes';
			$this->auth_data = json_decode($integrationData['auth_data']);
			$this->processNew($this->auth_data, $endPoint);

			if ($this->responseCode != 200) {
				$erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar brands";
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, "E");
				die;
			}

			// seta todos os brands para não integrado 
			// $this->model_brands_marketplaces->setAllNotIntegrated($integrationData['int_to']);

			$brands = json_decode($this->result,true);
			foreach ($brands as $key => $brand) {

				$brand_save = $brand['nome'];
				//Comentado por causa de marcas como L'oreal
			//	$brand['nome'] = str_replace("'", "&#039", $brand['nome']); 
				
				if ($brand_save != $brand['nome']) {  // estava com ' então vou acertar 
					$localbrand = $this->model_brands->getBrandDatabyName($brand_save);
					if (!is_null($localbrand)) {
						echo "Acertando ".$localbrand['id']." de ".$brand_save." para ".$brand['nome']."\n";  
						$this->model_brands->update(array('nome' => $brand['nome']), $localbrand['id']);
						$this->model_brands_marketplaces->update(array('nome' => $brand['nome']), $integrationData['int_to'], $localbrand['id']);
					}						
				}
				
				$localbrand = $this->model_brands->getBrandDatabyName($brand['nome']);
				$this->addMapping(array(
					'mapping'=> '['. $brand['fabricanteId'] .'] '. $brand['nome'],
					'breadcrumb'=>$brand['nome']
				));
				if (!$localbrand) { // ainda não exite, crio 
					$brandmarketplace = $this->model_brands_marketplaces->getDataByBrandIdMarketplace($brand['fabricanteId'], $integrationData['int_to']);
					if($brandmarketplace){
						echo "Acertando ".$brandmarketplace[0]['name']." de ".$brand_save." para ".$brand['nome']."\n";  
						$this->model_brands->update(array('name' => $brand['nome']), $brandmarketplace[0]['brand_id']);
						$this->model_brands_marketplaces->update(array('name' => $brand['nome']), $integrationData['int_to'], $brandmarketplace[0]['brand_id']);
						$localbrand['id'] = $brandmarketplace[0]['brand_id'];
					}else{
						$localbrand = array(
							'name' => $brand['nome'],
							'active' => 1
						);
						echo "Criando " . $brand['nome'] . "\n";
						$brand_id = $this->model_brands->create($localbrand);
						$localbrand['id'] = $brand_id;
					}
				}

				// vejo se alterou
				$data = array(
					'int_to' 				=> $integrationData['int_to'],
					'brand_id' 				=> $localbrand['id'],
					'id_marketplace' 		=> $brand['fabricanteId'],
					'name' 					=> $brand['nome'],
					'imageUrl' 				=> $brand['urlLink'],
					'isActive' 				=> $brand['ativo'],
					'title' 				=> $brand['nome'],
					'metaTagDescription' 	=> $brand['nome'],
				);
				echo "Verificando brands_marketplaces " . $localbrand['id'] . " - " . $brand['nome'] . "\n";
				$this->model_brands_marketplaces->createOrUpdateIfChanged($data);
			}
		
	}
	
	private function addMapping($mapping) {
        $canAdd = true;
        foreach($this->brands_mapping as $item) {
            if ($mapping['breadcrumb'] == $item['breadcrumb']) {
                $canAdd = false;
            }
        }
        if ($canAdd === true) {
            array_push($this->brands_mapping, $mapping);
        }
    }

}
