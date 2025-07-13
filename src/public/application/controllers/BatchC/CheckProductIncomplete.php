<?php
/*
 
Realiza a atualização de preço e estoque da VIA Varejo

*/   

class CheckProductIncomplete extends BatchBackground_Controller {

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
		
		$this->load->model('model_products');
		$this->load->library('UploadProducts');

		$this->verify();

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	private function verify() {
		$products = $this->model_products->getListIncomplet();

		foreach ($products as $product) {
			$categories = json_decode($product['category_id']);
			if (!is_null($categories)) {
				if (is_array($categories)) {
					if (count($categories) > 0) {
						if ($categories[0] != '') {
							if ($this->uploadproducts->countImagesDir($product['image']) > 0) {
								$this->model_products->markComplete($product['id']);
							}
						}
					}
				}
			}
		}
	}

	
}
?>
