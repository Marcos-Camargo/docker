<?php


require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Brands.php";

class ShophubBrands extends Brands
{

	var $auth_data;

	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

	}

	// php index.php BatchC/SellerCenter/Vertem/ShophubBrands run 
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
		
		/// echo "Vou enviar \n";
		/// $this->upload();
		$this->int_to='SH';
		echo "Vou Receber \n";
		$this->Sync();

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

	function sync()
	{
		$this->int_to = 'SH';
        $integrationData = $this->model_integrations->getIntegrationsbyCompIntType(1,$this->int_to,"CONECTALA","DIRECT",0);


		if ($integrationData) {
			echo 'Sync: ' . $integrationData['int_to'] . "\n";

			$this->syncIntTo($integrationData);
			// coloco o brand como ativo se tiver pelo menos 1 ativo ou inativo se não tiver nenhum ativo
			$this->model_brands_marketplaces->setBrandsActiveOrInactive();
		}
		
	}

}
