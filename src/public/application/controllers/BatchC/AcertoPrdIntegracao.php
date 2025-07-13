<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   
class AcertoPrdIntegracao extends BatchBackground_Controller {
		
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
		$this->load->model('model_nfes','mynfes');
		$this->load->model('model_quotes_ship','myquotesship');
		$this->load->model('Model_freights','myfreights');
		$this->load->model('model_clients','myclients');
    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			get_instance()->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		get_instance()->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		//$this->CriaSeEstivernoBling ();
		$this->lojasMudaramEmpresas();
		
		/* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function lojasMudaramEmpresas() {
		$sql = 'SELECT * FROM stores';
		$query = $this->db->query($sql);
		$stores = $query->result_array();
		foreach ($stores as $store) {
			$sql = 'SELECT * FROM products WHERE store_id = ? AND company_id!=?';
			$query = $this->db->query($sql,array($store['id'],$store['company_id']));
			$prods = $query->result_array();
			if (count($prods) > 0) {
				foreach ($prods as $prod) {
					echo 'prod '.$prod['id'].' loja '.$store['id'].'-'.$store['name'].' company '.$store['company_id'].' e com '.count($prods).' produtos ainda errados na company '.$prod['company_id']."\n";
				}	
			}
				
			
			$sql = 'SELECT * FROM integrations WHERE store_id = ? AND company_id!=?';
			$query = $this->db->query($sql,array($store['id'],$store['company_id']));
			$errados = $query->result_array();
			foreach($errados as $errado) {
				echo " prd_to_integration ".$errado['id']." store ".$store['id'].'-'.$store['name']." é da empresa ".$store['company_id']." mas já foi da ".$errado['company_id']."\n";
				$sql = 'SELECT * FROM prd_to_integration WHERE int_id = ?';
				$query = $this->db->query($sql,array($errado['id']));
				$prdsint = $query->result_array();
				if (count($prdsint) > 0) {
					echo ' e tem '.count($prdsint).' produtos associados'."\n";
				}
			}
			
		}
	}
		
	function CriaSeEstivernoBling()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 52, ordens que já tem rastrio no bling e envia para o Bling 
 
		$sql = 'select * from bling_ult_envio b where prd_id not in (select prd_id from prd_to_integration p where b.prd_id = p.prd_id and b.int_to = p.int_to)';
		$query = $this->db->query($sql);
		$blings = $query->result_array();
		foreach ($blings as $bling) {
			echo 'procurando produto id='.$bling['prd_id'].' skubling ='.$bling['skubling'].' Int_to='.$bling['int_to']; 
			$sql = "SELECT * FROM products WHERE id =".$bling['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
			
			$sql = "SELECT * FROM integrations WHERE store_id=".$prd['store_id']." AND int_to='".$bling['int_to']."'";
			$cmd = $this->db->query($sql);
			$integration = $cmd->row_array();
			$data = array(
				'int_id' => $integration['id'],
				'prd_Id' => $bling['prd_id'],
				'company_id' => $prd['company_id'],
				'date_update' => $prd['date_update'],
				'date_last_int'=> $bling['data_ult_envio'],
				'status' => 0, 
				'status_int' => 0, 
				'user_id' => 0,
				'int_type' => 13,
				'int_to' => $bling['int_to'],
				'skumkt' => $bling['skumkt'],
				'skubling' => $bling['skubling'],
				'store_id' => $prd['store_id'],
				
			);
			//var_dump($data);
		
			$insert = $this->db->insert('prd_to_integration', $data);
				
		}
	


	}


	
}
?>
