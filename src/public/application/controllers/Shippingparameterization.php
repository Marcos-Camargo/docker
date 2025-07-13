<?php
/*
SW Serviços de Informática 2019

Controller de Fornecedores

 */
defined('BASEPATH') or exit('No direct script access allowed');

class Shippingparameterization extends Admin_Controller
{
 public function __construct()
 {
  parent::__construct();

  $this->not_logged_in();

  $this->data['page_title'] = $this->lang->line('parameterization_shipping');

  $this->load->model('model_auction');
  $this->load->model('model_blingultenvio');
  $this->load->model('model_integration_logistic');
  $this->load->model('model_stores');
  $this->load->model('model_billet');

  $this->load->library('JWT');

  $this->load->helper('download');
 }

 /**
  * It only redirects to the manage providers page
  */
public function index()
{ 
	$this->data['seller'] = $this->model_stores->getActiveStore();

	$this->data['status'] = [
		'1' => 'Permitir enviar todos os resultados',
		'2' => 'Permitir enviar apenas o menor preço',
		'3' => 'Permitir enviar apenas o menor prazo',
		'4' => 'Permitir envia de menor e menor prazo'
	];

	$this->render_template('shipping_parameterization/index', $this->data);
}

public function validateSeller($id)
{
	$result['logisticaPropria'] = $this->model_auction->getHasProviderLogistic($id);

	$result['rules'] = $this->model_auction->getRuleAuction($id);

	echo json_encode($result);
}

public function saveRules() 
{
	$result['rules'] = $this->model_auction->configRule($this->postClean(NULL,TRUE));
	echo json_encode($result);
}

public function betterRote($sku, $cep)
{
	$mkt = $this->model_blingultenvio->blingUltEnvioBySkuMkt($sku);

	if (is_null($mkt)) {
		echo json_encode([
			'mensagem' => 'Falha ao encontrar o Sku'
		]);
		return;
	}

	$paramsTableShipping =[
		'store_id' => $mkt['store_id'],
		'cep_end' => $cep,
		'cep_start' => $mkt['zipcode'],
		'peso' => $mkt['peso_bruto']
	];

	$regraLeilao = $this->model_auction->getRuleAuction($mkt['store_id']);

	$shippingParams = $this->betterShippingSeller($paramsTableShipping);
	

	if (!empty($regraLeilao) && $regraLeilao['rules_seller_conditions_status_id'] == 1) {

	}

	//preco
	if (!empty($regraLeilao) && $regraLeilao['rules_seller_conditions_status_id'] == 2) {
		
	}
	//melhor prazo
	if (!empty($regraLeilao) && $regraLeilao['rules_seller_conditions_status_id'] == 3) {
		
	}
	//melhor e pior prazo 
	if (!empty($regraLeilao) && $regraLeilao['rules_seller_conditions_status_id'] == 3) {
		
	}
dd('foi');
	// dd($inforTableShipping);
	//$integrationSeller = $this->model_integration_logistic->getIntegrationSeller($mkt['store_id']);

	// if (is_null($integrationSeller)) {

	// }
	//echo json_encode($result);
}

public function betterShippingSeller($params)
{
	return $this->model_auction->searchTableShipping($params);
}

public function betterShippingSellerCenter($params)
{
	return $this->model_auction->searchTableShippingCenter($params);
}


public function save() {
    echo "<pre>";
    var_dump($_POST);
    exit;
}

}
