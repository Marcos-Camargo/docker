<?php


require APPPATH . "controllers/BatchC/SellerCenter/Vtex/ProductsStatus.php";

class ProductsStatus extends ProductsStatus
{

	var $auth_data;

	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

	}

	// php index.php BatchC/Marketplace/Vtex/ProductsStatus run null ZAAZ
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

		$this->int_to = $params;
		 if (!is_null($params) && ($params != 'null')) {
        	$integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$params,"CONECTALA","DIRECT",0);
			if (!$integration) {
				echo " Marektplace Vtex ".$params." não encontrado!";
				die;
			}
			$this->int_to = $params;
			$this->productStatus();
        }
		else {
			echo "Informe um marketplace Vtex válido. \n";
		}
		
		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

}
