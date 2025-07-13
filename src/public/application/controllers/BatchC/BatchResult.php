<?php

require_once 'system/libraries/Vendor/autoload.php';

// use Aws\S3\S3Client;

class BatchResult extends BatchBackground_Controller {
	var $sellercenter;
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
		$this->load->model('model_jobs_logs');
		$this->load->model('model_calendar');
		
    }
	
	// php index.php BatchC/BatchResult
	function run($ymd, $success=null,$job_id=null,$job=null,$log_file=null)
	{
		/* inicia o job */
		/* params 
		php $2 BatchResult Success $1 $3 $4
		echo ' $1 ->  id do job'
		echo ' $3 ->  prgrama a ser executado com seus parametros'
		echo ' $4 ->  arquivo de log para enviar para o S3'
		*/
		
		if (is_null($ymd) || is_null($success) || is_null($job_id) || is_null($job) || is_null($log_file)) {
			echo " Informe todos os parametros\n";
			echo "   - Infomae a data de referencia no padrão YYYYMMDD\n";
			echo "   - Success ou Failure no 1o parametro\n";
			echo "   - ID do job que estava executando no 2o parametro\n";
			echo "   - programa que estava executando no 3o parametro\n";
			echo "   - arquivo de log no 4o parametro\n";
			return ;
		}

		$settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

		if ($settingSellerCenter) {
            $this->sellercenter = $settingSellerCenter['value'];
        } else {
			echo "não achei o sellercenter\n";
			return;			
		}

		$this->registerBatchResult($ymd, $success,$job_id,$job,$log_file);

	}

	private function logsite($log) {

		return site_url(substr($log,strpos($log,'/app/')+4));
	}

	function registerBatchResult($ymd, $success=null,$job_id=null,$job=null,$log_file=null) {

		$job =str_replace('---','/',$job);
		$log_file =str_replace('---','/',$log_file);
		$logS3 = $this->sendFileToS3($log_file);
		$log = array (			
			'sellercenter'  	=> $this->sellercenter, 
			'environment'   	=> ENVIRONMENT, 
			'date_reference'	=> $ymd, 
			'server'			=> gethostbyname(gethostname()),
			'job_id' 			=> $job_id, 
			'job'				=> $job,
			'log_url'			=> ($logS3['success']) ? $logS3['url'] : $this->logsite($log_file),
			'success'			=> ($success == 'Success'),
		);

		$this->model_jobs_logs->create($log);

		if ($success != 'Success') {
			$this->model_calendar->update_job($job_id, array("status" => 7));
		}
	}

	function sendFileToS3($file) {
		if (!$this->model_settings->getStatusbyName('send_log_s3')) {
			return array(
				'success' => false,
				'url'     => 'Parametro send_log_s3 inativo ou inexistente'
			);
		}
		try {       

			// cria o objeto do cliente, necessita passar as credenciais da AWS
			$clientS3 = S3Client::factory(array(
				'key'    => $this->config->item('s3_access_key_id'),
				'secret' => $this->config->item('s3_access_key_secret'),
			));

			$response = $clientS3->putObject(array(
				'Bucket' 		=> "jobs_logs",
				'Key'    		=> date('Y-m-d').'_'.basename($file),
				'SourceFile' 	=> $file,
			));
	
			return array (
				'success' => true,
				'url'     => $response['ObjectURL']
			);

		} catch (Exception $e) {
			echo "Erro > {$e->getMessage()}";
			return array (
				'success' => false,
				'url'     => $e->getMessage()
			);
		}
	}

}
?>