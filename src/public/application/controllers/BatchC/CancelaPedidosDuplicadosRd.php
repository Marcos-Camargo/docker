<?php
/*
SW Serviços de Informática 2019
 
Atualiza pedidos que chegaram no BLING

*/   
class CancelaPedidosDuplicadosRd extends BatchBackground_Controller {
		
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
		$this->load->model('model_orders','myorders');
		$this->load->model('model_products','myproducts');
		$this->load->model('model_company','mycompany');
		$this->load->model('model_stores','mystores');
		$this->load->model('model_clients');
		$this->load->model('model_integrations','myintegrations');
		$this->load->model('model_contratar_fretes','mycontratarfretes');
		$this->load->model('model_blingultenvio','myblingultenvio');
		$this->load->model('model_promotions','mypromotions');
        $this->load->library('ordersMarketplace');

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
		$cancel = $this->cancelaPedidosDuplicados();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	


    function cancelaPedidosDuplicados()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		
		$sql = 'select 
        count(numero_marketplace), 
        numero_marketplace 
        from orders 
        group by numero_marketplace 
        having count(numero_marketplace)>1;';
		$query = $this->db->query($sql);
		$duplicados = $query->result_array();
		foreach ($duplicados as $pedido) {
            echo 'Pedido repetido: '.$pedido['numero_marketplace'];
            echo "\n";
            $sql = 'select *  from orders where numero_marketplace = ? order by id' ;
            $query = $this->db->query($sql, array($pedido['numero_marketplace']));
            $errados = $query->result_array();
            foreach($errados as $index => $dp){
                if($index == 0){
                    echo 'Mantendo o Pedido id=  '. $dp['id'].'  Status = '.$dp['paid_status'] .'  Numero Marketplace = '.$dp['numero_marketplace']; 
                    echo "\n"; 
                    continue;
                }
                if (!in_array($dp['paid_status'], [95, 96, 97, 98])) {
                echo 'CANCELANDO --- Pedido id= '. $dp['id'].'  Status = '.$dp['paid_status'] .' Numero Marketplace = '.$dp['numero_marketplace'];
                echo "\n";
                $this->ordersmarketplace->cancelOrder($dp['id'], false, false);
                }
            }
			
		}
	}

}

?>
