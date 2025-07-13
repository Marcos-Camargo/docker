<?php
/*
SW Serviços de Informática 2019
 
Atualiza pedidos que chegaram no BLING

*/   
class EnviaProdutosDuplicadosParaLixeira extends BatchBackground_Controller {
    /**
     * @var DeleteProduct
     */
    public $deleteProduct;

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
        $this->load->model('model_orders');
		$this->load->model('model_products');
        $this->load->library('DeleteProduct', [
            'productModel' => $this->model_products,
            'lang' => $this->lang
        ], 'deleteProduct');

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
		$cancel = $this->EnviaProdutosDuplicadosParaLixeira();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	


    function EnviaProdutosDuplicadosParaLixeira()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		
		$sql = "select count(name), name, store_id from products WHERE has_variants = '' AND date_create > '2023-10-03' AND status != 3 group by name, store_id having count(name)>1;";
		$query = $this->db->query($sql);
		$duplicados = $query->result_array();
		foreach ($duplicados as $produtos) {
            echo 'Produto repetido: '.$produtos['name'];
            echo "\n";
            $sql = 'select * from products where name = ? AND store_id = ? ' ;
            $query = $this->db->query($sql, array($produtos['name'], $produtos['store_id']));
            $produtos = $query->result_array();
            $retorno = $this->deleteProduct->moveToTrash($produtos);
			
		}
	}

}

?>
