<?php

require_once APPPATH . "libraries/Helpers/StringHandler.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

/**
 * Class SellerV2
 * @property CI_Loader $load
 * @property Microservices\v1\Logistic\Shipping $ms_shipping
 * @property Model_stores $model_stores
 */
class Seller extends Main
{
    public function __construct()
    {
        parent::__construct();
		
		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		
        $this->load->model('model_stores');
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", [], 'ms_shipping');

    }
	
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
		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        if (is_null($params) || $params === 'null') {
            echo "Informe o parâmetro[INT] que representa o tempo em minutos.\n";
        } else {
            $this->updateSeller((int)$params);
        }

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish');
		$this->gravaFimJob();
	}

	public function updateSeller(int $minutes)
    {
        if (!$this->ms_shipping->use_ms_shipping) {
            return;
        }
		
		$stores = $this->model_stores->getStoresUpdatedLastMinutes($minutes);

		foreach ($stores as $store) {
            try {
                $data = array(
                    'store_id'                          => $store['id'],
                    'CNPJ'                              => $store['CNPJ'],
                    'zipcode'                           => $store['zipcode'],
                    'additional_operational_deadline'   => $store['additional_operational_deadline'],
                    'inventory_utilization'             => $store['inventory_utilization'],
                    'type_store'                        => $store['type_store']
                );
                $this->ms_shipping->updateDataStoresInPivot($data);
                echo "Loja $store[id] atualizada.\n";
            } catch (Exception $exception) {
                echo "Erro para atualizar a loja $store[id]. {$exception->getMessage()}. " . json_encode($data) . "\n";
            }
		}
    }
}