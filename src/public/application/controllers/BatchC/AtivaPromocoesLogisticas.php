<?php
/*
 
Batch que ativa e desativa promoções logísticas do módulo Frete conforme a data inicial e final.

*/   
require APPPATH . "controllers/Meli.php";

 class AtivaPromocoesLogisticas extends BatchBackground_Controller {
	
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('Model_promotionslogistic');
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
		
		/* executa as funções para ativar e inativar promoções logísticas */

		$retorno = $this->batchPromotionsActivate();
		$retorno = $this->batchPromotionsInactivate();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
		
	function batchPromotionsActivate() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$result = $this->Model_promotionslogistic->batchActivate();
		if ($result == True) {
			$this->log_data('batch',$log_name,'Promoções Ativadas: IDs = '.$result.'',"I");
		}
		else {
			$this->log_data('batch',$log_name,'Não existem promoções para ativar',"I");
		}
	}
	
	function batchPromotionsInactivate() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$result = $this->Model_promotionslogistic->batchInactivate();
		if ($result == True) {
			$this->log_data('batch',$log_name,'Promoções Inativadas: IDs = '.$result.'',"I");
			$id_array = (explode(',', $result));
			foreach($id_array as $key) {
				//inativa a promoção na loja
				$result_stores = $this->Model_promotionslogistic->batchInactivateStoresbyID($key);
				if ($result == True) {
					$this->log_data('batch',$log_name,'Promoções desativadas para o ID = '.$key.' nas lojas '.$result.'',"I");
				}
				else {
					$this->log_data('batch',$log_name,'Não existem promoções ativas em lojas para o id = '.$key.'',"I");
				}
				//inativa todos os itens participantes da promoção.
				//testar a inativação
				
				$this->Model_promotionslogistic->batchInactivateItensStoresByPromoId($key);

			};
		}
		else {
			$this->log_data('batch',$log_name,'Não existem promoções para inativar',"I");
		}
	}
}
?>
