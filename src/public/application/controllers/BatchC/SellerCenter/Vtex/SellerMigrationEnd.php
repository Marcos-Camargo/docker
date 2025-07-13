<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') || exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

class SellerMigrationEnd extends Main
{

  var $auth_data;
    

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

		// carrega os modulos necessários para o Job
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_calendar');
		$this->load->model('model_stores');
        $this->load->model('model_products_seller_migration');
        $this->load->model('model_seller_migration_register');

	}

	// php index.php BatchC/SellerCenter/Vtex/SellerMigrationEnd run null 216 CasaeVideoTeste
	function run($id = null, $store_id = null, $int_to = null)
	{

		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store_id)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return;
		}
		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store_id), "I");	

		 /* faz o que o job precisa fazer */
		 if(is_null($store_id)  || ($store_id == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O sellerId NO PARAMS". PHP_EOL;
        }
        if(is_null($int_to)  || ($int_to == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto($store_id,$int_to);
			if($integration){
				$sync = $this->syncEndIntegration($store_id, $int_to);
				$params = "$store_id" . " " . "$int_to";
				if($sync == true){
					$job_event = $this->model_calendar->getEventModuleParam($modulePath ,$params);
					$this->model_calendar->delete_event($job_event['ID']);
					echo PHP_EOL .$store_id." Evento excluído do calendário de eventos". PHP_EOL;
				}
            }
			else {
				echo PHP_EOL .$store_id." não tem integração definida". PHP_EOL;
			}
		}

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

	function syncEndIntegration($store_id, $int_to)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $store_integration_data = $this->model_integrations->getIntegrationbyStoreIdAndInto($store_id, $int_to);
		$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);


		if($store_integration_data['active'] == '1'){
			echo PHP_EOL . "Loja: " . $store_id." já esta com sua integração ativada". PHP_EOL;
		} else{
			$data = array(
				'active' => 1,
				'auto_approve' => (int) $integration['auto_approve'],
			);
			$this->model_integrations->update($data,  $store_integration_data['id']);
			$this->model_stores->update(['date_update'=> date("Y-m-d H:i:s")],  $store_integration_data['store_id']);
			$data = [
				'user_id' =>  $this->session->userdata('id'),
				'end_date' => date("Y-m-d H:i:s"),
				'status' => 1
			];
			$procura = " WHERE store_id = $store_id";
			$started_migration = $this->model_seller_migration_register->getData(null, $procura);
			if ($started_migration) {
				$this->model_seller_migration_register->update($data, $started_migration[0]['id']);
			}
			echo PHP_EOL . "Loja: " . $store_id." teve sua integração ativada". PHP_EOL;
		}
        return true;
	}
	

}
