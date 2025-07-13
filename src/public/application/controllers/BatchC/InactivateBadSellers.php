<?php
/*
 
*/   
class InactivateBadSellers extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();

        $logged_in_sess = array(
            'id' 		=> 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' 	=> 1,
            'userstore' => 2,
            'logged_in' => TRUE
        );
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job

    }

    // php index.php BatchC/InactivateBadSellers run null
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return;
		}
		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
		
		/* faz o que o job precisa fazer */
		$this->inativa('B2W');
		$this->ativa('B2W');
		
		/* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function inativa($int_to) 
	{

		$sql = 'SELECT s.* FROM stores s, seller_index si WHERE s.id = si.store_id AND s.phase_id = 1 AND s.active =1 AND (si.seller_index =1 or si.seller_index =2)';
		$query = $this->db->query($sql);
        $stores = $query->result_array();
		foreach ($stores as $store) {
		
			$sql = "SELECT * FROM integrations WHERE active = 1 AND int_to=? AND store_id=?";
			$query = $this->db->query($sql, array($int_to,$store['id']));
			$integrations = $query->result_array();
			foreach ($integrations as $integration) {  
				if ($integration['active']) {
					echo "Inativando LOJA  ".$integration['store_id']."\n";
					$sql = "SELECT * FROM prd_to_integration WHERE store_id = ? AND int_to= ? and status = 1";
					$query = $this->db->query($sql,array($integration['store_id'],$int_to));
					$prd_tos = $query->result_array();
					foreach($prd_tos as $prd_to) {
						echo "Inativando ".$prd_to['id'].' prdid='.$prd_to['prd_id'].' intto='.$prd_to['int_to'].' store_id='.$prd_to['store_id']."\n";
						$this->model_integrations->updatePrdToIntegration(array('status'=>0),$prd_to['id']);
					}
					echo "Inativando ".$store['id']." ".$store['name']." na ".$int_to."\n";
					$this->model_integrations->update(array('active'=>0),$integration['id']);
				}
			}
		}
	}

	function ativa($int_to) 
	{

		$sql = 'SELECT s.* FROM stores s, seller_index si WHERE s.id = si.store_id AND s.phase_id = 1 AND s.active =1 AND si.seller_index >= 3';
		$query = $this->db->query($sql);
        $stores = $query->result_array();
		foreach ($stores as $store) {			
			$sql = "SELECT * FROM integrations WHERE active != 1 AND int_to=? AND store_id=?";
			$query = $this->db->query($sql, array($int_to,$store['id']));
			$integrations = $query->result_array();
			foreach ($integrations as $integration) {  
				if (!$integration['active']) {
					echo "Ativando LOJA  ".$integration['store_id']."\n";		
					$this->model_integrations->update(array('active'=>1),$integration['id']);
				}
			}
		}
	}
	
}
?>
