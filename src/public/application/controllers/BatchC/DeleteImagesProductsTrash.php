<?php
/*
 
Verifica os produtos na lixeria e remove as imagens 

*/   
class DeleteImagesProductsTrash extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' 		=> 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' 	=> 1,
		        'userstore' => 0,
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

	// php index.php BatchC/DeleteImagesProductsTrash/run/null
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
		$this->findProductsTrash();
		
		/* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function findProductsTrash()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		/// falta ainda ver os produtos que tem mais de 14 dias na lixeira
		//return; ///
		$root = FCPATH . 'assets/images/product_image/trash';
		$delfolder = FCPATH . 'assets/images/delete_image/';

		if (!file_exists($root)) {
			mkdir($root); 
		}
		$offset = 0; 
		$limit  = 1000; 
		$cnt = 0; 
		$limpos = 0; 
		$exist = true; 
		$two_Weeks = time() - 60 * 60 * 24 * 14;
		while($exist) {
			$sql = 'SELECT * FROM products WHERE status = 3 AND image !="trash" AND date_update < DATE_SUB(NOW(), INTERVAL 15 DAY) LIMIT '.$limit.' OFFSET '.$offset;
			$query = $this->db->query($sql);
			$prds = $query->result_array();
			if (!$prds) {
				$exist =false; 
				break;
			}
			foreach ($prds as $prd) {
				$cnt++;
				echo $cnt." prd_id=".$prd['id']." removendo as images \n"; 
				if ((trim($prd['image']) != '') && (!is_null($prd['image']))) {
					$folder = FCPATH . 'assets/images/product_image/'.$prd['image'];

					$dir_verify = trim($folder); 
					$last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13); 						  
					if ($last_path_delete =='product_image') {
						log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
						die; 
					}

					echo $folder." \n";
					shell_exec("sudo /bin/mv  '".$folder."' '".$delfolder."'");					
					$this->db->where('id', $prd['id']);
					$update = $this->db->update('products', array('image' => 'trash'));
					$limpos++;
					
				}	
			}
			// $offset += $limit; 
		}
		echo "Examinados ".$cnt." acertados ".$limpos."\n";
	} 
	
}
?>
