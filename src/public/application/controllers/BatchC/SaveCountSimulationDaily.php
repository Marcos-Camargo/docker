<?php
/*

Verifica quais ordens precisam de frete e contrata no frete rápido

*/

require_once "application/libraries/CalculoFrete.php";

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Router $router
 *
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Model_log_count_simulation_daily $model_log_count_simulation_daily
 *
 * @property CalculoFrete $calculofrete
 */

class SaveCountSimulationDaily extends BatchBackground_Controller
{
	public function __construct()
	{
		parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
		$this->session->set_userdata($logged_in_sess);
        $this->load->model('model_settings');
        $this->load->model('model_integrations');
        $this->load->model('model_log_count_simulation_daily');

    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = __CLASS__.'/'.__FUNCTION__;
		if (!$this->gravaInicioJob(__CLASS__,__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}

        $this->saveCountSimulation();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish');
		$this->gravaFimJob();
	}

	private function saveCountSimulation()
    {
        if (!$this->model_settings->getValueIfAtiveByName('enable_log_count_simulation_daily')) {
            echo "Parâmetro enable_log_count_simulation_daily desligado.\n";
            return;
        }

        $sellercenter       = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $integrations       = $this->model_integrations->getIntegrationsbyStoreId(0);
        $date_current_day   = dateNow()->format('Y_m_d');

//        $key_redis = "decathlon:count_request_simulation:ConectaLa:2023_12_21:1:P_001";
//        CacheManager::set($key_redis, '{"sellercenter":"decathlon","marketplace":"ConectaLa","skumkt":"P_001","store_id":"1","integration":"sgpweb","seller":false,"date":"2023-12-21","count":26}');

        foreach ($integrations as $integration) {
            echo "Integração: $integration[int_to]\n";
            $key_redis_current_day = "$sellercenter:count_request_simulation:$integration[int_to]:$date_current_day:";
            $quotes_current_day = \App\Libraries\Cache\CacheManager::getAllByPrefix($key_redis_current_day);
            \App\Libraries\Cache\CacheManager::deleteAllByPrefix($key_redis_current_day);

            $key_redis_last_day = "$sellercenter:count_request_simulation:$integration[int_to]:";
            $quotes_last_day = \App\Libraries\Cache\CacheManager::getAllByPrefix($key_redis_last_day);
            \App\Libraries\Cache\CacheManager::deleteAllByPrefix($key_redis_last_day);

            foreach ($quotes_current_day as $quote) {
                $quote = json_decode($quote);

                $create = $this->getDataToCreate($quote);

                echo "[CREATE]$create[skumkt] = $create[count_request]\n";

                $this->model_log_count_simulation_daily->create($create);
            }

            echo "-----------------------------------------------\n";

            foreach ($quotes_last_day as $quote) {
                $quote = json_decode($quote);

                echo "[UPDATE]$quote->skumkt = $quote->count\n";

                $this->model_log_count_simulation_daily->updateCountByData($quote->count, array(
                    'int_to'    => $quote->marketplace,
                    'skumkt'    => $quote->skumkt,
                    'store_id'  => (int)$quote->store_id,
                    'date'      => $quote->date,
                ), $this->getDataToCreate($quote));
            }
        }
	}

    private function getDataToCreate(object $quote): array
    {
        return array(
            'sellercenter'          => $quote->sellercenter,
            'int_to'                => $quote->marketplace,
            'skumkt'                => $quote->skumkt,
            'store_id'              => (int)$quote->store_id,
            'logistic_integration'  => $quote->integration ?? $quote->logistic_integration,
            'store_integration'     => $quote->store_integration ?? null,
            'seller_integration'    => $quote->seller,
            'date'                  => $quote->date,
            'count_request'         => $quote->count,
        );
    }
}
