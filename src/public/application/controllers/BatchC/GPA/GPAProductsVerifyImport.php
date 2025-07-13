<?php
/*
 * 
Envia os produtos par ao GPA
 * 
*/   
require APPPATH . "controllers/BatchC/Marketplace/Mirakl/ProductsVerifyImport.php";
 class GPAProductsVerifyImport extends ProductsVerifyImport {

	var $attributes = array();
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');
		
		// carrega os modulos necessários para o Job
		$this->load->model( 'model_gpa_last_post');
    }
		
	// php index.php BatchC/GPA/GPAProductsVerifyImport run null
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->int_to='GPA';
		$this->getkeys(1,0);
		$this->lastPostModel();
		$retorno = $this->checkProductStatus();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function lastPostModel() {
		$this->last_post_table_name = 'gpa_last_post';
   		return $this->model_gpa_last_post; 
   	}
}
?>
