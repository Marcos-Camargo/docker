<?php
/*
 
Realiza o Leilão de Produtos e atualiza o CAR 

*/   
require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';


class ViaProductsStatus extends BatchBackground_Controller {
	
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
		        'userstore'  => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_campaigns');
		
		$this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();
	}
	
	function getInt_to() {
		return ViaUtils::getInt_to();
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
		
		/* faz o que o job precisa fazer */
		// $this->getkeys(1,0);
		$this->checkProductsStatus();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function check($prd_id, $sku)
	{
		$int_to = $this->getInt_to();
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		$response = $this->integration->getProdutcStatus($authorization, $sku, $prd_id);

		echo PHP_EOL . $response['content'] . PHP_EOL . PHP_EOL;
		echo json_decode($response['content'], true)['skus'][0]['skuStatus']  . PHP_EOL . PHP_EOL;
	}

    function checkProductsStatus()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$company_id = 1; // somente da conecta-la
		$store_id = 0;
		$int_to = $this->getInt_to();

		$sql = "select * from prd_to_integration pti where status_int in (22) and int_to = '".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$arr_products = $query->result_array();
		
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		$count_item = 0;
		foreach($arr_products as $prd) {
			echo PHP_EOL . '[CHECK] ' . ++$count_item . '/' . count($arr_products);
			$this->checkProductStatus($authorization, $prd);
		}
		echo PHP_EOL . ' FIM DA ROTINA '. PHP_EOL;
	}				

	function getProductVariants($id)
	{
		$product = $this->model_products->getProductData(0, $id);
		return $this->model_products->getProductVariants($id, $product['has_variants']);
	}

	function checkProductStatus($authorization, $product)
	{
		echo ' SKUMKT: ' . $product['skumkt'] . ' Prd_id: '. $product['prd_id'];

		$skumkt = $product['skumkt'];
		
		$variants = $this->getProductVariants($product['prd_id']);
		if ($variants['numvars'] != -1)
		{
			$skumkt .= '-' . $variants[0]['variant'];
		}

		$response = $this->integration->getProdutcStatus($authorization, $skumkt, $product['prd_id']);

		$data = json_decode($response['content'], true);
		
		if ($response['httpcode'] < 300) {
			echo ' Success Get Status... ';

			if (count($data['itens']) == 0) 
			{
				echo ' Not found... ';
				$violations = array();
				$this->setStatusInvalido($authorization, $product['skumkt'], $product, $violations);
				return ;
			}

			$this->model_errors_transformation->setStatusResolvedByProductId($product['prd_id'], $this->getInt_to());
			foreach ($data['itens'] as $item) {
				$status = $item['skuStatus'];
				$sku = $item['idSkuLojista'];

				$arr = explode('-', $item['idSkuLojista']);

				echo 'SKU Variant: '. $sku .' Status: '. $status . '... ';
				//se for diferente de AGUARDANDO_PROCESSAMENTO verifica o status
				//caso seja igual a AGUARDANDO_PROCESSAMENTO não faz nada, somente aguarda

				if ($product['skumkt'] == $arr[0]) {
					if ($status != 'AGUARDANDO_PROCESSAMENTO')
					{
						if ($status == 'INVALIDO') 
						{
							$this->setStatusInvalido($authorization, $product['skumkt'], $product, $data['itens'][0]['violacoes']);
						}
						else if ($status == 'VALIDO') 
						{
							$this->setStatusValido($authorization, $sku, $product);
						} 
						else if ($status == 'FICHA_INTEGRADA') 
						{
							$this->setStatusFichaIntegrada($authorization, $product['skumkt'], $product);
						}
					}
				}
			}			
		}
	}

	private function setStatusInvalido($authorization, $sku, $prd, $violations) 
	{	
		$sql = "UPDATE prd_to_integration SET status_int = 20 WHERE int_type = 13 AND int_to='".$this->getInt_to()."' AND prd_id = ". $prd["prd_id"] ." ";
		$query = $this->db->query($sql);

		foreach ($violations as $violation) 
		{
			$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
				"VALUES(". $prd["prd_id"] .", '". $sku . "', '".$this->getInt_to()."', 'Cadastro Via Varejo', '". $violation['codigo']. " - " . str_replace("'", "\"", $violation['mensagem']) ."', 0);";

			$this->db->query($sql);
		}
	}
	

	private function setStatusValido($authorization, $sku, $prd) 
	{
		$this->integration->choose($authorization, $sku, $prd['prd_id']);
	}

	private function setStatusFichaIntegrada($authorization, $sku, $prd) 
	{
		$sql = "UPDATE prd_to_integration SET status_int = 2 WHERE int_type = 13 AND int_to='".$this->getInt_to()."' AND prd_id = ". $prd["prd_id"] ." ";
		$query = $this->db->query($sql);
	}
}
?>
