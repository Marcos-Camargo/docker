<?php
/*
 
Atualiza os pedidos do Vertem Store 
 * 
 * Vertem Store não enviamos nada para eles, basta avancar os status; 

*/   

class VSOrdersStatus extends BatchBackground_Controller {

	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp'  => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_frete_ocorrencias');
		$this->load->model('model_integrations');
		$this->load->model('model_orders_item');
		
		$this->load->library('ordersMarketplace');
    }

	// php index.php BatchC/SellerCenter/Vertem/VSOrdersStatus run 
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
		$this->store_id=0;
		$this->int_to = 'VS';
		
		$this->mandaNfe();
		$this->mandaTracking();
		$this->mandaEnviado();
		$this->mandaEntregue();
		$this->mandaCancelados();
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}

	function mandaNfe()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '52';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de nfe da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

			echo 'pedido de troca'."\n";
			$order['paid_status'] = 50; // agora tudo certo para contratar frete 
			$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
		    $this->model_orders->updateByOrigin($order['id'],$order);
			
		} 
	}

	function mandaTracking()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de tracking da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

			$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Tracking enviado para '.$this->int_to."\n";
		} 
	}

	function mandaEnviado()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}
		
			if ($order['exchange_request']) { // pedido de troca não é atualizado no Madeira Madeira 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 5; // agora tudo certo para contratar frete 
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}

			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Envio enviado para '.$this->int_to."\n";
		} 
	}

	function mandaEntregue()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '60';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}
		
			if ($order['exchange_request']) { // pedido de troca não é atualizado no Madeira Madeira 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 6; // agora tudo certo para contratar frete 
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}

			$order['paid_status'] = 6; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Entregue enviado para '.$this->int_to."\n";
		} 
	}

	function mandaCancelados()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 99, ordens que canceladas que tem que ser avisadas no Marketplace
		$paid_status = '99';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem a cancelar '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'Cancelando pedido ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}
			
			$this->ordersmarketplace->cancelOrder($order['id'], true);
			echo 'Cancelado em '.$this->int_to."\n";
		} 

	}
}
?>
