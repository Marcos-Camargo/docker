<?php
/*
 
Envia estoque  e preço para todos os produtos ativos para a skyhub

*/   
class SkyHubPriceStock extends BatchBackground_Controller {
	
	var $int_to='B2W';
	var $apikey='';
	var $email='';
	var $prd;
	var $variants;
	var $store = array(); 
	var $auth_data;
	var $integration_store;
	var $integration_main;
		
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
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_stores');
		$this->load->model('model_orders');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_b2w_ult_envio');

		$this->load->model('model_products_catalog');
		$this->load->model('model_log_integration_product_marketplace');
		
    }
	
	// php index.php BatchC/SkyHubPriceStock run null B2W
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		if (!is_null($params)) {
			$this->int_to='B2W';
		}
		
		/* faz o que o job precisa fazer */
		if (date('d')==27) {
			$retorno = $this->orphanProducts();
		}
		$retorno = $this->syncPriceQty();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function syncPriceQty()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$offset = 0;
		$limit = 500;
		$exist = true;
		while($exist) {
			$prds_to = $this->model_integrations->getPrdIntegrationByIntToStatus($this->int_to,'1',array('2','10'),$offset,$limit );
			if (!$prds_to) {
				echo "acabou\n";
				break;
			}
			$offset += $limit; 
			foreach ($prds_to as $prd_to) {
				if (is_null($prd_to['skumkt'])) {
					continue;
				}
				$this->prd=$this->model_products->getProductData(0,$prd_to['prd_id']);
				$this->variants = $this->model_products->getVariants($prd_to['prd_id']);
				$this->store['id'] = $this->prd['store_id'];
				
				$this->getkeys();
				
				$this->prd['qty_original'] = $this->prd['qty'];
				if ((int)$this->prd['qty'] < 0) { 
					$this->prd['qty']  = 0;
				}
				$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arredondo para cima 
				// Pego o preço do produto
				$this->prd['promotional_price'] = $this->getPrice(null);
				if ($this->prd['promotional_price'] > $this->prd['price'] ) {
					$this->prd['price'] = $this->prd['promotional_price']; 
				}
				
				// se tiver Variação,  acerto o estoque de cada variação
		    	if ($this->prd['has_variants']!='') {
		    		$variações = explode(";",$this->prd['has_variants']);
					
					// Acerto o estoque
					foreach ($this->variants as $key => $variant) {
						$this->variants[$key]['qty_original'] =$variant['qty'];
						if  ((int)$this->variants[$key]['qty'] < 0) { 
							$this->variants[$key]['qty'] = 0;
						}
						$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
						if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
							$this->variants[$key]['price'] = $this->prd['price'];
						}

						$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
						if ($this->variants[$key]['promotional_price'] > $this->variants[$key]['price'] ) {
							$this->variants[$key]['price'] = $this->variants[$key]['promotional_price']; 
						}

						//ricardo, por enquanto, o preço da variação é igual ao do produto. REMOVER DEPOIS QUE AS INTEGRAÇÔES ESTIVEREM CONCLUIDAS
						$this->variants[$key]['price'] = $this->prd['price'];
						$this->variants[$key]['promotional_price'] = $this->prd['promotional_price']; 
					}
				}

				if ($this->prd['is_kit']) {  // B2W consegue mostrar o preço original dos produtos que o componhe 
					$productsKit = $this->model_products->getProductsKit($this->prd['id']);
					$original_price = 0; 
					foreach($productsKit as $productkit) {
						$original_price += $productkit['qty'] * $productkit['original_price'];
					}
					$this->prd['price'] = $original_price;
					echo ' KIT '.$this->prd['id'].' preço de '.$this->prd['price'].' por '.$this->prd['promotional_price']."\n";  
				}
				// atualiza preço e estoque primeiro antes de alterar o resto do produto.
				if ($this->changeB2WPriceQty($prd_to['skumkt'], $prd_to['prd_id'])==false) {die;}
			}
		}
		
	}
	
	function changeB2WPriceQty($skumkt, $prd_id) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Lendo ".$skumkt."\n";
		
		$prdSkyHub = $this->getSkyHubProduct($skumkt, $prd_id);
		if ($prdSkyHub === false) {
			echo 'não foi possível ler o sku '.$skumkt.' na skyhub'."\n"; 
			return true; 
		}
		
		if ($this->prd['has_variants']!='') {
            $tipos = explode(';',$this->prd['has_variants']);
            $variation_attributes = array();
			$variations = array();
			foreach($this->variants as $variant) {
				if (isset($variant['sku'])) {
					$sku = $skumkt.'-'.$variant['variant'];
					
					$product = Array (
						'variation' => array(
						    'qty' => (int)$variant['qty']
						),
						'specifications' => array(
							array (
								'key' => 'price',
								'value'=> (float)$variant['price']
							), 
							array (
								'key' => 'promotional_price',
								'value'=> (float)$variant['promotional_price']
							),
							
						), 
					);
					$varpromoprice =  $prdSkyHub['promotional_price'];
					$varprice =  $prdSkyHub['price'];
					foreach ($prdSkyHub['variations'] as $skyHubVar) {
						if($skyHubVar['sku'] == $sku) {
							if (((int)$skyHubVar['qty'] != (int)$variant['qty']) ||
								((float)$varpromoprice != (float)$variant['promotional_price']) ||
								((float)$varprice != (float)$variant['price'])) {
											
								echo 'Produto:'.$this->prd['id'].' Variação: '.$variant['variant'].' Sku: '.$sku.' estoque:'.$variant['qty'].' De: '.(float)$variant['price'].' Por: '.(float)$variant['promotional_price']."\n";
								
								echo "  na skyhub, qty:  ".$skyHubVar['qty']." price ".$varprice." promo: ".$varpromoprice."\n";
								$url = 'https://api.skyhub.com.br/variations/'.$sku;

								$json_data = json_encode($product);
								var_dump($json_data);
								$retorno = $this->skyHubHttp($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque Variacao '.$variant['variant']);
								if ($this->responseCode !='204')  {  // created
									echo 'Erro url:'.$url.' httpcode='.$this->responseCode .' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true)." \n"; 
									$this->log_data('batch',$log_name, 'ERRO ao alterar estoque variação '.$sku.' url:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),'E');
									return false;
								}
							}
						}
						
					}
					
				}
			}	
		}
		else {

			$product = Array (
				'product' => array(
    				'price' => (float)$this->prd['price'],
   					'promotional_price' => (float)$this->prd['promotional_price'], 
				    'qty' => (int)$this->prd['qty']
				) 
			);
			
		//	var_dump($prdSkyHub);
			if (((int)$prdSkyHub['qty'] != (int)$product['product']['qty'] ) ||
				((float)$prdSkyHub['promotional_price'] != (float)$product['product']['promotional_price'] ) ||
				((float)$prdSkyHub['price'] != (float)$product['product']['price'] )) {
				
				$url = 'https://api.skyhub.com.br/products/'.$skumkt;
			
				echo 'Produto:'.$this->prd['id'].' Sku:'.$skumkt.' estoque:'.$this->prd['qty'].' De: '.(float)$this->prd['price'].' Por: '.(float)$this->prd['promotional_price']."\n";
				echo "  na skyhub, qty:  ".$prdSkyHub['qty']." price ".$prdSkyHub['price']." promo: ".$prdSkyHub['promotional_price']."\n";
				$json_data = json_encode($product);
				var_dump($json_data);
				$retorno = $this->skyHubHttp($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque');
				if ($this->responseCode !='204')  {  // created
					echo 'Erro url:'.$url.'. httpcode='.$this->responseCode .' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO ao alterar estoque '.$skumkt.' url:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r($this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),'E');
					return false;
				}
					
			}
			
		}	
		return true;
	} 
	
	function getSkyHubProduct($sku, $prd_id) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://api.skyhub.com.br/products/'.$sku;
		$retorno = $this->skyHubHttp($url,'GET');

		if ($this->responseCode !="200") {
			
			if  ($this->responseCode =="404") {
				echo ' não encontrou o produto '.$sku."\n";
				$prds_int = $this->model_integrations->getPrdToIntegrationBySkyblingAndInttoMulti($sku, $this->int_to);
				foreach($prds_int as $prd_int) {
					$data = array(
						'ad_link' 	=> null,
						'skumkt' 	=> null,
						'skubling' 	=> null, 
					);	
					$this->model_integrations->updatePrdToIntegration($data, $prd_id);
				}						
				$blings = $this->model_blingultenvio->getDataByPrdIdAndIntTo($prd_id, $this->int_to);
				foreach($blings as $bling) {
					$this->model_blingultenvio->remove($bling['id']);
				}
				 
				$b2w_ult = $this->model_b2w_ult_envio->getBySku($sku);
				if ($b2w_ult) {
					$this->model_b2w_ult_envio->remove($b2w_ult['id']);
				}
				return false;
			}
			echo "Erro na respota do ".$this->int_to.". httpcode=".$this->responseCode." RESPOSTA ".$this->int_to.": ".print_r($this->result,true)." \n"; 
			return false;
		}
		$resposta = json_decode($this->result,true);
		return $resposta;
	}
	
	protected function skyHubHttp($url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null )
    {

        $this->header = [
            'content-type: application/json',
            'accept: application/json',
            'x-accountmanager-key: YdluFpAdGi', 
			'x-api-key: '.$this->auth_data->apikey,
			'x-user-email: '.$this->auth_data->email
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		if ($this->responseCode == 429) {
		    $this->log('Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->skyHubHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
		if ($this->responseCode == 504) {
		    $this->log('Deu Timeout httpcode=504. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->skyHubHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
        if ($this->responseCode == 503) {
		    $this->log('Site com problemas httpcode=503. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->skyHubHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'function' => $function,
				'url' => $url,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
		
        return;
    }

	function percEstoque() {
		
		$percEstoque = $this->model_settings->getValueIfAtiveByName(strtolower($this->int_to).'_perc_estoque');
		if ($percEstoque)
		   	return $percEstoque;
		else 
			return 100;
	} 

	public function getPrice($variant = null) 
	{
		$this->prd['price'] = round($this->prd['price'],2);
		// pego o preço por Marketplace 
		$old_price = $this->prd['price'];
		
		// pego o preço da variant 
		if (!is_null($variant)) {
			if ((float)trim($variant['price']) > 0) {
				$old_price = round($variant['price'],2);
				if ($old_price !== $this->prd['price']) {
					$this->log(" Produto ".$this->prd['id']." Variaçao ".$variant['variant']. " tem preço ".$old_price." na variação e preço normal ".$this->prd['price']);
				}
			}
		}
		
		// altero o preço para acertar o DE POR do marketplace. 
		$old_price  =  $this->model_products_marketplace->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $this->prd['has_variants']);
		if ($old_price !== $this->prd['price']) {
			$this->log(" Produto ".$this->prd['id']." tem preço ".$old_price." para ".$this->int_to." e preço normal ".$this->prd['price']);
		}

		// Pego o preço a ser praticado se tem promotion
		if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
			$price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $variant);
		}
		else
		{
			$price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to);
		}

		if ($old_price !== $price) {
			$this->log(' Produto '.$this->prd['id'].' tem preço promoção '.$price.' para '.$this->int_to.' e preço base '.$old_price);
		}
		return round($price,2);
	}

	public function log($msg)
	{
		echo $msg."\n";
	}
	
	function getkeys() {
		//pega os dados da integração. 
		$this->getIntegration(); 
		$this->auth_data = json_decode($this->integration_main['auth_data']);

	}

	function getIntegration() 
	{
		
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				if ($this->integration_store['int_from'] == 'CONECTALA') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto('0',$this->int_to);
				}elseif ($this->integration_store['int_from'] == 'HUB') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
				} 
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
	}	
	
	function orphanProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo "Procurando produtos perdidos \n";
		$this->store['id'] = 0;
		$this->getkeys();
		$url = 'https://api.skyhub.com.br/products?filters[status]=enabled&cursor';
		$lidos = 0;
		while (true){
			// $url = 'https://api.skyhub.com.br/products?filters[status]=enabled&filters[qty_from]=1&page='.$page.'&per_page=100';
			$this->skyHubHttp($url, 'GET', array());
			if ($this->responseCode != '200')  { 
				$erro = "Erro na respota do ".$url.". httpcode=".$this->responseCode." RESPOSTA: ".print_r($this->result,true); 
				echo $erro."\n";
				$this->log_data('batch',$log_name,$erro,"E");
				return;
			}
			$products = json_decode($this->result ,true);

			
			if (empty($products['next'])) {
				echo "acabou\n";
				break;
			}
			$url = $products['next'];
			
			foreach ($products['products'] as $product) {
				if ($product['status'] == "enabled") {
					$lidos++;
					//var_dump($product);
					$sku = $product['sku'];
					// echo "Verificado ".$sku;
					$sql ="SELECT * FROM bling_ult_envio WHERE skumkt = ? AND int_to=?";
					$query = $this->db->query($sql, array($sku,$this->int_to));
					$bling = $query->row_array();
					if (!$bling) {
						echo $sku." não existe - colocando em Disable\n";
					    $this->disableB2W($sku);
						continue;
					}
					else {
						// echo $sku." OK \n"; 
					}
					
				}
			}
			// echo "Lidos: ".$lidos."\n";
		}
		echo "Lidos: ".$lidos."\n";
    } 

	function disableB2W($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$disable = Array (
			'product' => array(
					'status' => 'disabled'
				)
			);
								
		$json_data = json_encode($disable);
		
		$url = 'https://api.skyhub.com.br/products/'.$skumkt;
		$retorno = $this->skyHubHttp($url, 'PUT', $json_data);
		if ($this->responseCode !='204')  {  // created
			echo 'Erro url:'.$url.'. httpcode='.$this->responseCode .' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true)."\n"; 
			$this->log_data('batch',$log_name, 'ERRO ao alterar estoque '.$sku.' url:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r($this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),'E');
			die;
			return false;
		}
	}
}
?>
