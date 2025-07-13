<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class ProductDiary extends Main
{
	var $int_to;
	var $auth_data;
	
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_products');
        $this->load->model('model_brands');
        $this->load->model('model_category');
		$this->load->model('model_products_catalog');
		$this->load->model('model_integrations');
		$this->load->model('model_vtex_ult_envio');
		$this->load->model('model_stores');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_promotions');
			
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
    }

    // php index.php BatchC/SellerCenter/Vtex/ProductDiary run null Farm
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
		if (!is_null($params)) {
			$this->int_to = $params;
			$integrationData = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$this->int_to);
			$this->auth_data = json_decode($integrationData['auth_data']);
			
		    $retorno = $this->notifyPriceAndStockChange();
		}
		else {
			echo "Informe o int_to do marketplace para enviar produtos\n";
		}
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}


    // php index.php BatchC/SellerCenter/Vtex/Product notifyPriceAndStockChange
    public function notifyPriceAndStockChange()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Buscando produtos com estoque alterado para notificar ".$this->int_to."\n";
		
		$offset = 0;
		$limit = 50; 
		$exists = true; 
		while ($exists) {
			$dateLastInt = date('Y-m-d H:i:s');
			$products = $this->model_integrations->getProductsRefresh($this->int_to, $offset, $limit);
			if (count($products)==0) {
				echo "Encerrou \n";
				$exists = false;
				break;
			}
			
			foreach ($products as $key => $product) {
				echo $offset + $key + 1 . " - ";
				
				// var_dump($product);
				
				// Leio os dados da integração desta loja deste produto
				$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto($product['store_id'],$this->int_to);
				$auth_data = json_decode($integration['auth_data']);
            	$sellerId = $auth_data->seller_id;
				
				if (($product['status'] != 1) || ($product['situacao'] != 2)) {
	                echo "Produto ".$product['id']." não está ativo. Estoque na Vtex será alterado para zero.\n";
	                $product['qty'] = 0;
	            }
	            // pego o preço da promotion se houver
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['variant'])) {
                    $product['price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to, $product['variant']);
                }
                else
                {
                    $product['price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to);
                }

				$product['price'] = round($product['price'],2);
				
				$this->model_vtex_ult_envio->updateByIntTo($this->int_to, $product['id'], $product['variant'], array('qty_atual' => $product['qty'], 'price' =>$product['price']));
				
				$data = [];
				$bodyParams = json_encode($data);
	            $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$product['pi_skumkt'];
	            
	            echo "Verificando se o produto ".$product['id']." sku ".$product['pi_skumkt']." existe no marketplace ".$this->int_to." para o seller ".$sellerId.".\n";

	            $skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams);
		            
				if ($this->responseCode == 404) {
					$erro = "O produto ".$product['id']." não está cadastrado no marketplace ".$this->int_to." para o seller ".$sellerId.".";
		            echo $erro."\n";
		            $this->log_data('batch', $log_name, $erro,"E");
					$this->model_integrations->updatePrdToIntegration(array('status_int' => 90, 'date_last_int' => $dateLastInt), $product['pi_id']);
					
					// deveria retirar o registro do prd_to_integration e do vtex_ult_envio se isso acontecer 
					continue;
				}
				if ($this->responseCode !== 204) {
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;;
				}
				
	            $notice = "Notificação de alteração concluída para o produto ".$product['id']." sku: ".$product['pi_skumkt'];
                echo $notice."\n";
                $this->model_integrations->updatePrdToIntegration(array(
	                	'int_id'=> $integration['id'], 
	                	'seller_id'=> $sellerId, 
	                	'status_int' => 2, 
	                	'date_last_int' => $dateLastInt, 
	                	'skumkt' => $product['pi_skumkt'], 
	                	'skubling' => $product['pi_skumkt']
					), $product['pi_id']);
				
				// se for produto de catálogo, puxo as informações do catálogo. 
				if (!is_null($product['product_catalog_id'])) {
					$prd_catalog = $this->model_products_catalog->getProductProductData($product['product_catalog_id']); 
					$product['EAN'] = $prd_catalog['EAN'];
					$product['largura'] = $prd_catalog['width'];
					$product['altura'] = $prd_catalog['height'];
					$product['profundidade'] = $prd_catalog['length'];
					$product['peso_bruto'] = $prd_catalog['gross_weight'];
					$product['ref_id'] = $prd_catalog['ref_id']; 
				}
				else {
					$product['ref_id'] = null;
				}
				
				$loja  = $this->model_stores->getStoresData($product['store_id']);
				$toSaveUltEnvio = [
                    'int_to' => $this->int_to,
                    'company_id' => $product['company_id'],
                    'EAN' => $product['EAN'],
                    'prd_id' => $product['id'],
                    'price' => $product['price'],
                    'sku' => $product['sku'],
                    'data_ult_envio' => $dateLastInt, 
                    'qty_atual' => $product['qty'],
                    'largura' => $product['largura'],
                    'skumkt' => $product['pi_skumkt'],
                    'altura' => $product['altura'],
                    'profundidade' => $product['profundidade'],
                    'peso_bruto' => $product['peso_bruto'],
                    'store_id' => $product['store_id'],
                    'seller_id' => $sellerId,
                    'crossdocking' => $product['prazo_operacional_extra'],
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']), 
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
					'variant' => $product['variant'],
                ];

                $savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($product['id'], $this->int_to, $product['variant'], $toSaveUltEnvio);
				
			}	
			$offset += $limit;
		} 

    }

}
