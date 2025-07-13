<?php

use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Null_;

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

abstract class Brands extends Main
{

	var $auth_data;
  const FILENAME = 'brandsMapping.xlsx';
  var $brands_mapping = array();

  abstract function run($id = null, $params = null);

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
		$this->load->model('model_queue_products_marketplace'); 
		$this->load->model('model_brands');
		$this->load->model('model_brands_marketplaces');
		$this->load->model('model_settings');
        $this->load->library('excel');
	}

	function upload()
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        // Cria a associação da marca com o marketplace, antes de realizar o envio da marca com o marketplace.
        $this->createBrandInBrandMkt();

		$main_integrations = $this->model_integrations->getIntegrationsbyStoreId(0);
		foreach ($main_integrations as $key => $integration) {
			//if($integration['int_to']=='Farm'){
				echo "int_to = ".$integration['int_to']. "\n";
				$brand_marketplaces = $this->model_brands_marketplaces->getDontSend($integration['int_to']);
				foreach ($brand_marketplaces as $key2 => $brand) {
					if($brand['id_marketplace']){
						$result=$this->updateIntTo($integration, $brand);
						$result=json_decode($result);
						$brand['integrated']=true;
						$brand['date_update']='';
						$this->model_brands_marketplaces->update($brand,$brand['int_to'],$brand['brand_id']);
					}else{
						$result=$this->uploadIntTo($integration, $brand);
						$result=json_decode($result);
						$brand['id_marketplace']=$result->Id;
						$brand['integrated']=true;
						$brand['date_update']='';
						$this->model_brands_marketplaces->update($brand,$brand['int_to'],$brand['brand_id']);
					}
					//CONSULTA OS PRODUTOS DESTE FABRICANTE E REENVIA PARA A FILA
					$data='';
					$caracterOne = '["';
					$caracterTwo = '"]';
					$sql = "SELECT P.id as prd_id 
							FROM products P 
							JOIN prd_to_integration PI ON P.id = PI.prd_id
							WHERE REPLACE(REPLACE(P.brand_id, '$caracterOne',''),'$caracterTwo','') = ? AND PI.int_to=?";
					$query = $this->db->query($sql, array($brand['brand_id'],$brand['int_to']));
					$products_send = $query->result_array();
					foreach ($products_send as $products_send) {
						$data = array(
							'status' => 0,
							'prd_id' => $products_send['prd_id'],
							'int_to' => $brand['int_to']
						);
						$saved = $this->model_queue_products_marketplace->create($data);
					}
				}
		//}
		}
	}

	function updateIntTo($integration, $brand)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		
		$data = array(
			'Name' => $brand['name'],
			'Keywords' => $brand['metaTagDescription'],
			'Text' => $brand['name'],
			'SiteTitle' => $brand['title'],
			'Active' => boolval($brand['isActive']),
			'MenuHome' => boolval($brand['MenuHome']),
			'AdWordsRemarketingCode' => $brand['AdWordsRemarketingCode'],
			'LomadeeCampaignCode' => $brand['LomadeeCampaignCode'],
			'Score' => $brand['Score']
		);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		
		$endPoint   = 'api/catalog/pvt/brand/'.$brand['id_marketplace'];
		$data = json_encode($data);
		$this->auth_data = json_decode($integration['auth_data']);
		// $this->auth_data = json_encode($this->auth_data);
		$skuExist = $this->processNew($this->auth_data, $endPoint, 'PUT', $data);
		if ($this->responseCode != 200) {
			$erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar brands";
			echo $erro . "\n";
			$this->log_data('batch', $log_name, $erro, "E");
			$this->gravaFimJob();
			die;
		}
		return $this->result;
	}
	function uploadIntTo($integration, $brand)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		
		$data = array(
			'Name' => $brand['name'],
			'Keywords' => $brand['metaTagDescription'],
			'Text' => $brand['name'],
			'SiteTitle' => $brand['title'],
			'Active' => boolval($brand['isActive']),
			'MenuHome' => boolval($brand['MenuHome']),
			'AdWordsRemarketingCode' => $brand['AdWordsRemarketingCode'],
			'LomadeeCampaignCode' => $brand['LomadeeCampaignCode'],
			'Score' => $brand['Score']
		);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		
		$endPoint   = 'api/catalog/pvt/brand';
		$data = json_encode($data);
		$this->auth_data = json_decode($integration['auth_data']);
		// $this->auth_data = json_encode($this->auth_data);
		$skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $data);
		if ($this->responseCode != 200) {
			$erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar brands";
			echo $erro . "\n";
			$this->log_data('batch', $log_name, $erro, "E");
			$this->gravaFimJob();
			die;
		}
		return $this->result;
	}

	function sync()
	{
		$main_integrations = $this->model_integrations->getIntegrationsbyStoreId(0);

		foreach ($main_integrations as $integrationData) {
			echo 'Sync: ' . $integrationData['int_to'] . "\n";

			$this->syncIntTo($integrationData);
		}
		// coloco o brand como ativo se tiver pelo menos 1 ativo ou inativo se não tiver nenhum ativo
		$this->model_brands_marketplaces->setBrandsActiveOrInactive();
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
			$this->gravaFimJob();
			die;
		}

		// seta todos os brands para não integrado
		$this->model_brands_marketplaces->setAllNotIntegrated($integrationData['int_to']);

       	$brands = json_decode($this->result);
		foreach($brands as $brand) {
			$this->addMapping(array(
				'mapping'=> '['. $brand->id .'] '. $brand->name,
				'breadcrumb'=>$brand->name
			));

			$localbrand = $this->model_brands->getBrandDatabyName($brand->name);
			if (!$localbrand) { // ainda não exite, crio 
				$localbrand = array(
					'name' => $brand->name,
					'active' => 1
				);
				echo "Criando " . $brand->name . "\n";
				$brand_id = $this->model_brands->create($localbrand);
				$localbrand['id'] = $brand_id;
			}

			// vejo se alterou
			$data = array(
				'int_to' => $integrationData['int_to'],
				'brand_id' => $localbrand['id'],
				'id_marketplace' => $brand->id,
				'name' => $brand->name,
				'imageUrl' => $brand->imageUrl,
				'isActive' => $brand->isActive,
				'title' => $brand->title,
				'metaTagDescription' => $brand->metaTagDescription,
			);
			echo "Verificando brands_marketplaces " . $brand->id . " - " . $brand->name . "\n";
			$this->model_brands_marketplaces->createOrUpdateIfChanged($data);
		}
		
		//Removo os que sumiram na Vtex
		$this->model_brands_marketplaces->removeAllNotIntegrated($integrationData['int_to']);	

		$this->createXls($this->brands_mapping, $integrationData['int_to']);
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

	private function createXls($mapping, $int_to) {
        $objPHPExcel = new Excel();
        $objPHPExcel->setActiveSheetIndex(0);

        $line = 1;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, 'MarketPlace brands');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $line, 'Brands submitted by seller');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $line, 'Unmapped brands submitted by seller');
        
        foreach($mapping as $item) {
            $line++;

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, $item['mapping']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $line, $item['breadcrumb']);
        }
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save(FCPATH . 'assets/images/'.SELF::FILENAME);
    }

    private function createBrandInBrandMkt(): void
    {
        if (!$this->model_settings->getValueIfAtiveByName('integrate_MarketplaceBrand_incomplete')) {
            return;
        }

        // Integrações do seller center.
        $integrations = $this->model_integrations->getIntegrationsbyStoreId(0);
        foreach ($integrations as $integration) {
            // Marcas que ainda não foram enviadas para o marketplace.
            $brandDontMarketplace = $this->model_brands_marketplaces->getBrandsDontSent($integration['int_to']);
            foreach ($brandDontMarketplace as $brand) {
                echo "Marca: {$brand['name']}, associada ao marketplace: {$integration['int_to']}\n";
                // Cria o vínculo da marca com o marketplace.
                $data = [
                    "int_to"                 => $integration['int_to'],
                    "name"                   => $brand['name'],
                    "brand_id"               => $brand['id'],
                    "title"                  => $brand['name'],
                    "isActive"               => 1,
                    "metaTagDescription"     => $brand['name'],
                    "MenuHome"               => 1
                ];
                $this->model_brands_marketplaces->create($data);
            }
        }
    }

}
