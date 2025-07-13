<?php
/*
 
Realiza a atualização de contratos para que passem a bloquear após a data

*/   

class CheckContracts extends BatchBackground_Controller {

	public function __construct()
	{
		parent::__construct();

		$logged_in_sess = array(
			'id' => 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' => 1,
			'logged_in' => TRUE
		);

		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
    }

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
		
		$this->load->model('model_contracts');

		$this->verify();

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	private function verify() {

		$contracts = $this->model_contracts->getExpiredContracts();
        echo json_encode($contracts);
        $data = array(           
            'block' =>  1,
        );

		foreach ($contracts as $contract) {
			$response = $this->model_contracts->update($data, $contract['id']);
            echo PHP_EOL .$response;
		}
	}

	
}
?>