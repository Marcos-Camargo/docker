<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class SyncProductSpecification extends Main
{
    // Quantidade de produtos por página (máximo de 50);
    const REGISTERS = 50;
	
	var $catalog;
	var $int_to = '';
	
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
 
		 $this->load->model('model_products');
		 $this->load->model('model_atributos_categorias_marketplaces');
   }

    // php index.php BatchC/SellerCenter/Vtex/Catalog run null Farm
	function run($id=null,$int_to=null,$seller=null)
	{
		/* inicia o job */
		$this->setIdJob($id); 

		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$int_to." ".$seller),"I");
		
		/* faz o que o job precisa fazer */
		if (!is_null($int_to)) {
			$this->int_to = $int_to;
			if (!is_null($seller)) {
				$separateIntegrationData = $this->getSeparateIntegrationData($int_to);
				if (!is_null($separateIntegrationData)) {
					$retorno = $this->syncSpecification($separateIntegrationData, $int_to , $seller, 0, 100);
				}
			}
			else {
				echo "Informe o seller do marketplace para sincronizar os atributos dos seus produtos\n";
			}
		}
		else {
			echo "Informe o int_to do marketplace para sincronizar os atributos e estoque dos seus produtos\n";
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	private function getSeparateIntegrationData($int_to) {
		$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);

		return $separateIntegrationData;
	}

	private function syncSpecification($separateIntegrationData, $int_to, $sellerId, $limit, $offset) {
		$products = $this->model_products->getProductsVtexPagination($sellerId, $limit, $offset);

		foreach ($products as $product) {
			echo $limit . '/' . $offset . ' ' . $product['import_seller_id'] . ' - ' . $product['import_seller_id'] . ' ' .$product['name'] . PHP_EOL;

			if (!is_null($product['variant'])) {
				if ($product['variant'] != 0) {
					continue ;
				}
			}

			$specifications = $this->getSpecificationProduct($separateIntegrationData, $product);
			$attributes = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($product['id']);
			foreach ($specifications as $specification) {
				if (!$this->hasSpecification($attributes, $specification)) {
					if (!$this->isVariation($specification->FieldId)) {
						$this->updateSpecification($product, $specification);
					}
				}
			}
		}

		if (count($products) >= $offset) {
			$limit = $limit + $offset;
			$this->syncSpecification($separateIntegrationData, $int_to, $sellerId, $limit, $offset);
		}

		echo PHP_EOL . PHP_EOL . 'FIM SYNC' . PHP_EOL . PHP_EOL;
	}

	private function getSpecificationProduct($separateIntegrationData, $product) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$sku = $product['sku'];
		if (!is_null($product['sku_variant'])) {
			$sku = $product['sku_variant'];
		}

		$endPoint = 'api/catalog/pvt/stockkeepingunit/'. $sku .'/specification';

		$this->processNew($separateIntegrationData, $endPoint);
		if ($this->responseCode == 429) {
			sleep(60);
			$this->processNew($separateIntegrationData, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint . ' result: '. $this->result;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return [null, null];
		}
		if ($this->result == "") return [];

		$specifications = json_decode($this->result);
		
		return $specifications;
	}

	private function updateSpecification($product, $specification) {
		$data = array(
			"id_product" => $product["id"],
			"id_atributo" => $specification->FieldId,
			"valor" => $specification->Text,
			"int_to" => $this->int_to
		);
		$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data);
	}

	private function hasSpecification($attributes, $specification) {
		$hasSpecification = false;

		foreach ($attributes as $attr) {
			if ($attr['id_atributo'] == $specification->FieldId) {
				$hasSpecification = true;
			}
		}

		return $hasSpecification;
	}

	private function isVariation($specification_id) {
		$attr = $this->model_atributos_categorias_marketplaces->getAtributoByAttrIdMkt($specification_id, $this->int_to);
		return $attr['variacao'] == true;
	}
}
