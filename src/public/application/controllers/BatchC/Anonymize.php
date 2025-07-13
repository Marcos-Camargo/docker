<?php
/*
 

*/   

class Anonymize extends BatchBackground_Controller {
		
	private $count = 0;

	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_clients');
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_nfes');
		
		$this->load->model('model_settings');
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
		
		/* faz o que o job precisa fazer */
		$retorno = $this->syncCanceladosPre(0, 1000);

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
		 
    function syncCanceladosPre($offset, $limit)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$days = $this->model_settings->getValueIfAtiveByName('dias_para_anonimizar_pedidos');
		if ($days === false) {
			echo PHP_EOL . "Parametro não encontrado ou inativo: dias_para_anonimizar_pedidos";
			return ;
		}

		$orders = $this->model_orders->listOrdersCanceledPreToAnonymize($days, $offset, $limit);

		foreach ($orders as $order) {
			echo PHP_EOL . ++$this->count . ' - order_id: ' . $order['order_id'] . '... client_id: '. $order['client_id'] . '... ';
			if ($this->model_clients->anonymizeByClientId($order['client_id'])){
				$this->model_freights->anonymizeByOrderId($order['order_id']);
				$this->model_nfes->anonymizeByOrderId($order['order_id']);
				$this->model_orders->anonymizeByOrderId($order['order_id']);
			}
		}

		if (count($orders) == $limit) {
			$this->syncCanceladosPre($offset + $limit, $limit);
		}

		return ;
	} 
}
?>
