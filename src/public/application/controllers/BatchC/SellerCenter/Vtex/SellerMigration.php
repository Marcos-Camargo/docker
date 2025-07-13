<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') || exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

class SellerMigration extends Main
{

  var $auth_data;
    

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
		$this->load->model('model_settings');
        $this->load->model('model_products_seller_migration');

	}

	// php index.php BatchC/SellerCenter/Vtex/SellerMigration run null 005 216 CasaeVideoTeste
	function run($id = null, $seller_id = null , $store_id = null, $int_to = null)
	{

		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $seller_id)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return;
		}
		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $seller_id), "I");	

		 /* faz o que o job precisa fazer */
		 if(is_null($seller_id)  || ($seller_id == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O sellerId NO PARAMS". PHP_EOL;
        }
        if(is_null($int_to)  || ($int_to == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
			if($integration){
				$this->int_to=$integration['int_to'];
				echo 'Sync: '. $integration['int_to']."\n";
                $this->auth_data = json_decode($integration['auth_data']);
				$this->syncSellerProducts($this->auth_data, $seller_id,$store_id, $int_to);
            }
			else {
				echo PHP_EOL .$seller_id." não tem integração definida". PHP_EOL;
			}
		}

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

	function syncSellerProducts($integrationData, $seller_id, $store_id, $int_to)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $perPage     = 49;
        $regStart    = 0;
        $regEnd      = $perPage;
        $count_error = 0;
            
        while (true) {
            $urlGetProducts = "api/catalog_system/pub/products/search?_from=$regStart&_to=$regEnd&fq=sellerId:$seller_id";
            echo $urlGetProducts . PHP_EOL;
            $this->processNew($integrationData, $urlGetProducts);
            if ($this->responseCode < 200 || $this->responseCode >= 300 ) {
                $erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $urlGetProducts . " para pegar produtos. Error=$this->result";
                echo $erro . "\n";
                $this->log_data('batch', $log_name, $erro, "E");
                $count_error++;
                if ($count_error > 5) {
                    break;
                }
                continue;
            }
            $count_error = 0;
            $regProducts = json_decode($this->result);

            // Não tem produto na listagem, fim da lista
            if (!count($regProducts)) {
                break;
            }
            ECHO "\n##### INÍCIO PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n\n";

            foreach ($regProducts as $productId => $sku) {

                // Descodificar content recuperado
                $product =  $sku ?? false;

                foreach ($product->items as $keySku => $skus) {
                    $urlGetSku = "api/catalog_system/pvt/sku/stockkeepingunitbyid/{$skus->itemId}";
                    $this->processNew($integrationData, $urlGetSku);
                    if ($this->responseCode < 200 || $this->responseCode >= 300 ) {
                        $erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $urlGetSku . " para pegar produtos";
                        echo $erro . "\n";
                        $this->log_data('batch', $log_name, $erro, "E");
                        continue;
                    }
                    $regSkus = json_decode($this->result);
                    foreach ($regSkus->SkuSellers as $key => $seller) {
                        if ($seller->SellerId == $seller_id) {
                            $checkIfExists = $this->model_products_seller_migration->getProductDatabySkuId($seller->SellerStockKeepingUnitId, $store_id);
                            if(!$checkIfExists){
                                $data = [
                                    "seller_id"     => $seller_id,
                                    "product_name"  => $product->productName,
                                    "sku_name"      => $skus->name,
                                    "id_sku"        => $seller->SellerStockKeepingUnitId,
                                    "category_id"   => $sku->categoryId,
                                    "brand_id"      => $sku->brandId,
                                    "brand_name"    => $sku->brand,
                                    "store_id"      => $store_id,
                                    "int_to"        => $int_to,
                                ];    
				                $this->model_products_seller_migration->create($data);
				                echo 'Salvou Novo: '.  $seller->SellerStockKeepingUnitId . PHP_EOL;
			                }
			                else {
                                $data = [
                                "category_id" =>$sku->categoryId,
                                "brand_id" => $sku->brandId,
                                "brand_name" =>$sku->brand                    
                                ];
                                $this->model_products_seller_migration->update($data, $checkIfExists['id']);
                                echo 'Já Existe: '.  $seller->SellerStockKeepingUnitId . PHP_EOL;
                            }
                            //echo  "------------------------------------------------------------\n";
                        }
                    }
                 
                }
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }
        return true;

	}	

}
