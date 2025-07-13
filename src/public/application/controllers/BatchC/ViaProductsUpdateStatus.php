<?php
/*
 
Sincroniza os produtos que foram alterados e que são ganhadores de leilão

*/   
require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

 class ViaProductsUpdateStatus extends BatchBackground_Controller {
	
	var $int_to='VIA';
	var $apikey='';
	var $email='';
	var $pos = 0;

	private $oAuth2 = null;
	private $integration = null;

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
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_stores');

		$this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();
    }
	
	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setApikey($apikey) {
		$this->apikey = $apikey;
	}
	function getApikey() {
		return $this->apikey;
	}
	function setEmail($email) {
		$this->email = $email;
	}
	function getEmail() {
		return $this->email;
	}
	
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
		
		$int_to = $this->getInt_to();
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		/* faz o que o job precisa fazer */
		$retorno = $this->sync($authorization, 0);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
    function sync($authorization, $offset, $limit = 10)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	

		$response = $this->integration->getProducts($authorization, $offset, $limit);

		if ($response['httpcode']=="429")  {  
			echo "Estourei o limite \n";
			sleep(60);
			$response = $this->integration->getProducts($authorization, $offset, $limit);
		}

		if (($response['httpcode']=="502") || ($response['httpcode']=="500"))  { 
			echo "Erro de Gateway tentar novamente em 1 minuto \n";
			sleep(60);
			$response = $this->integration->getProducts($authorization, $offset, $limit);
		}

		if ($response['httpcode']!="200")   {  
			echo "Erro na respota do ".$this->getInt_to().' - offset: '. $offset . ' - limit: '. $limit .". httpcode=".$response['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($response['content'],true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get URL no '.$this->getInt_to().' - offset: '. $offset . ' - limit: '. $limit .' - httpcode: '.$response['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($response['content'],true),"E");
			return ;
		} 

		$data = json_decode($response['content'],  true);

		if (count($data['sellerItems']) > 0) {

			foreach($data['sellerItems'] as $item) {
				$skumkt = explode('-', $item['skuSellerId'])[0];
				$prd_to_integration = $this->model_products->getProductIntegrationSkumkt($skumkt);

				echo ++$this->pos . '/' . strval($offset + $limit) . ' - SKU: ' . $item['skuSellerId'];
				if (count($prd_to_integration) == 0) {
					echo ' - disabling ';
					$this->integration->disableAll($authorization, $item['skuSellerId']);
				} else {
					foreach($prd_to_integration as $pti) {
						$prd = $this->model_products->getProductData(0, $pti['prd_id']);
						$update = false;

						if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($prd['variant'])) {
							$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'], $this->getInt_to(), $prd['variant']);
						}
						else
						{
							$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'], $this->getInt_to());
						}


						echo "PRD_ID={$pti['prd_id']}\npromotional_price={$prd['promotional_price']}\n";
						foreach($item['prices'] as $price) {
							echo "price_offer=".json_encode($price['offer'])."\n";
							if (round($prd['promotional_price'], 2) != round($price['offer'], 2)) {
								$update = true;
							}
						}
						echo "irá atualizar?".json_encode($update)."\n";

						if ($update) {
							$this->model_products->insertQueue($pti['prd_id'], $this->getInt_to());
						}
					}
				}
				echo PHP_EOL;
			}

			$this->sync($authorization, $offset + $limit);
		}
    } 
}
?>
