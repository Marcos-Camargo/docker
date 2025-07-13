<?php
  
 class CreateLogisticSummary extends BatchBackground_Controller {
	
	public function __construct()
	{
		parent::__construct();
		ini_set('display_errors', 1);
		
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
		$this->load->model('model_monitor_logistic_summary');
    }
	
	// php index.php BatchC/CreateLogisticSummary run null
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
		$this->writeTable();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
		
	function writeTable() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

		if ($settingSellerCenter) {
            $sellercenter = $settingSellerCenter['value'];
        }
		else {
			echo "não achei o sellercenter\n";
			return;			
		}

		$control = $this->model_monitor_logistic_summary->getLogisticSummaryControl($sellercenter, ENVIRONMENT);
		if (!$control) {
			$control = array(
				'sellercenter' 	=> $sellercenter,
				'environment'	=> ENVIRONMENT,
				'last_id'		=> 0,
			);
		}

		$limit = 1000; 
		while (true) {
			echo "Lendo ".$control['last_id']."\n";
			$sql = 'SELECT * FROM log_quotes WHERE id > ? ORDER BY id LIMIT '.$limit;
			$query = $this->db->query($sql, array($control['last_id']));
			$logs = $query->result_array();
			if (!$logs) {
				break;
			}
			foreach($logs as $log) {
				$control['last_id'] = $log['id'];
				
				$dt = DateTime::createFromFormat("Y-m-d H:i:s", $log['updated_at']);
				$data = $dt->format('Y-m-d H:00:00'); 
				$summary = $this->model_monitor_logistic_summary->getLogisticSummary($sellercenter, ENVIRONMENT, $log['marketplace'], $log['integration'], $data);
				$min = true;
				if (!$summary) {
					$min = false;
					$summary = array(
						'sellercenter'  				=> $sellercenter,
  						'environment' 					=> ENVIRONMENT,
  						'marketplace' 					=> $log['marketplace'],
  						'integration' 					=> $log['integration'],
  						'date' 							=> $data,
  						'total'  						=> 0,
  						'total_success' 				=> 0,
  						'total_contingency' 			=> 0,
  						'response_total_time' 			=> 0,
						'response_max_time' 			=> 0,
						'response_min_time' 			=> 0,
  						'total_db_time' 				=> 0,
  						'max_db_time' 					=> 0,
  						'min_db_time' 					=> 0,
  						'integration_api_total_time' 	=> 0,
  						'integration_api_max_time' 		=> 0,
  						'integration_api_min_time' 		=> 0,
  						'identify_sku_total_db_time' 	=> 0,
  						'identify_sku_max_db_time'		=> 0,
  						'identify_sku_min_db_time' 		=> 0,
  						'integration_total_db_time' 	=> 0,
  						'integration_max_db_time' 		=> 0,
  						'integration_min_db_time'		=> 0,
  						'internal_table_total_db_time' 	=> 0,
  						'internal_table_max_db_time'	=> 0,
  						'internal_table_min_db_time'	=> 0,
  						'contingency_total_db_time' 	=> 0,
  						'contingency_max_db_time' 		=> 0,
  						'contingency_min_db_time' 		=> 0,
  						'promotion_total_db_time' 		=> 0,
  						'promotion_max_db_time' 		=> 0,
  						'promotion_min_db_time' 		=> 0,
  						'auction_total_db_time' 		=> 0,
  						'auction_max_db_time' 			=> 0,
  						'auction_min_db_time' 			=> 0,
  						'price_rules_total_db_time'		=> 0,
  						'price_rules_max_db_time' 		=> 0,
  						'price_rules_min_db_time' 		=> 0,
  						'redis_total_db_time' 			=> 0,
  						'redis_max_db_time' 			=> 0,
  						'redis_min_db_time' 			=> 0
					);
				}
				
				$times = $this->round_array(json_decode($log['response_details_time'],true));
				if (array_key_exists('time_start_query_sku', $times)) {
					echo $log['id']." no formato antigo\n";
					continue; 
				}

				$summary['total']++; 
				$summary['total_success'] = $log['success']==1 ? $summary['total_success']+1 : $summary['total_success'];
				$summary['total_contingency'] = $log['contingency']==1 ? $summary['total_contingency']+1 : $summary['total_contingency'];

				$this->checkValues($summary['response_total_time'], $summary['response_max_time'], 
					$summary['response_min_time'], $times['total']);
				$this->checkValues($summary['integration_api_total_time'], $summary['integration_api_max_time'], 
					$summary['integration_api_min_time'], $times['integration']);				
				
				$totaldb = 0;
				$totaldb += $this->checkValues($summary['identify_sku_total_db_time'], $summary['identify_sku_max_db_time'], 
					$summary['identify_sku_min_db_time'], $times['query_sku']);
				$totaldb += $this->checkValues($summary['integration_total_db_time'], $summary['integration_max_db_time'], 
					$summary['integration_min_db_time'], $times['integration_instance']);
				$totaldb += $this->checkValues($summary['internal_table_total_db_time'], $summary['internal_table_max_db_time'], 
					$summary['internal_table_min_db_time'], $times['internal_table']);
				$totaldb += $this->checkValues($summary['contingency_total_db_time'], $summary['contingency_max_db_time'], 
					$summary['contingency_min_db_time'], $times['contingency']);
				$totaldb += $this->checkValues($summary['promotion_total_db_time'], $summary['promotion_max_db_time'], 
					$summary['promotion_min_db_time'], $times['promotion']);
				$totaldb += $this->checkValues($summary['auction_total_db_time'], $summary['auction_max_db_time'], 
					$summary['auction_min_db_time'], $times['auction']);
				$totaldb += $this->checkValues($summary['price_rules_total_db_time'], $summary['price_rules_max_db_time'], 
					$summary['price_rules_min_db_time'], $times['price_rules']);
				$totaldb += $this->checkValues($summary['redis_total_db_time'], $summary['redis_max_db_time'], 
					$summary['redis_min_db_time'], $times['redis']);
				
				$this->checkValues($summary['total_db_time'], $summary['max_db_time'], 
					$summary['min_db_time'], $totaldb);

				if (array_key_exists('id',$summary)) { 
					$this->model_monitor_logistic_summary->updateLogisticSummary($summary, $summary['id']);
				}
				else {
					$this->model_monitor_logistic_summary->createLogisticSummary($summary);
				}	
				$control['last_id'] = $log['id'];
				if (array_key_exists('id',$control)) {
					$this->model_monitor_logistic_summary->updateLogisticSummaryControl($control, $control['id']);
				}
				else {
					$control['id'] = $this->model_monitor_logistic_summary->createLogisticSummaryControl($control);
				}
			}

			if (array_key_exists('id',$control)) {
				$this->model_monitor_logistic_summary->updateLogisticSummaryControl($control, $control['id']);
			}
			else {
				$control['id'] = $this->model_monitor_logistic_summary->createLogisticSummaryControl($control);
			}
		}


	}

	private function checkValues(&$total, &$max, &$min, $value)
	{
		$total += $value;
		if ( $value > $max) {
			$max = $value;					
		}
		if (($min==0) || ( $value < $min && $value != 0)) {
			$min = $value;
		}
		return $value;
	}

	private function round_array($array) {
		$newarr = array(); 
		foreach($array as $key => $value) {
			$newarr[$key] = round($value);
		}
		return $newarr;
	}

}
?>
