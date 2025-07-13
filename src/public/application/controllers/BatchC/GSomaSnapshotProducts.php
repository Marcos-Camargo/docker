<?php
/*
Marca todos os produtos de todos os lojista para participarem do Leilão
 
Executa uma vez por dia
*/   
 class GSomaSnapshotProducts extends BatchBackground_Controller {
		
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
		$this->load->model('model_products');
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
		$retorno = $this->shapshot();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function shapshot() 
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$offset =0; 
		$limit = 20; 
		$today = (new DateTime())->setTime(0,0);
		
		echo "Lendo todos os produtos de hoje ".$today->format('Y-m-d H:i:s')."\n";
		while (true) {
			$products  = $this->model_products->getDataProductsAndVariants( $offset, $limit);
			if (!$products) {  // acabou ou deu erro....
				break;
			}
			$offset += $limit;
			foreach($products as $product) {
				if (!is_null($product['variant'])) {
					$product['qty']=$product['varqty'];
				}
				if (is_null($product['variant'])) {
					$product['variant'] = '';
				}
				$data = array(
					'date' => $today->format('Y-m-d H:i:s'),
					'prd_id' => $product['id'],
					'variant' => $product['variant'],
					'int_to' => '',  // por enquanto....
					'qty' => $product['qty'],
					'price' => $product['price'],
					'status' => $product['status'],
				);
				$insert = $this->db->replace('snapshot_products', $data);
			}
		}		
	}
}
?>