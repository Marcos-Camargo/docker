<?php


require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Brands.php";

class BalarotiBrands extends Brands
{

	var $auth_data;

	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

	}

	// php index.php BatchC/SellerCenter/Balaroti/BalarotiBrands run 
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
		$this->int_to='Balaroti';
		echo "Vou Receber \n";
		$this->Sync();

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

}
