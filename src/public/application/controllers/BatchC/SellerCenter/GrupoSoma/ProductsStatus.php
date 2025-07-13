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

	// php index.php BatchC/SellerCenter/GrupoSoma/ProductsStatus run null Farm
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
		if (!is_null($params)) {
			$this->int_to = $params;
			
			$catalog = $this->model_catalogs->getCatalogByName($this->int_to);
			if (!$catalog) {
				echo "Este catálogo ".$this->int_to." não existe\n";
				die;
			}

			$this->productStatus();
		}
		else {
			echo "Informe o int_to do marketplace para enviar produtos\n";
		}
		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

}
