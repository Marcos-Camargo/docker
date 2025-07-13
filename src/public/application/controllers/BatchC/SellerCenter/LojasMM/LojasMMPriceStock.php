<?php


require APPPATH . "controllers/BatchC/SellerCenter/Vtex/PriceStock.php";

class LojasMMPriceStock extends PriceStock
{

	public function __construct()
	{
		parent::__construct();
	}

	// php index.php BatchC/SellerCenter/LojasMM/LojasMMPriceStock run 
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
		
		$this->int_to ='LojasMM';
		
		$integrationData = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$this->int_to);
		$this->auth_data = json_decode($integrationData['auth_data']);
		
	    $retorno = $this->notifyPriceAndStockChange();

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

}
