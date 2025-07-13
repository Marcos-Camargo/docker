<?php
/*
 
Verifica os produtos na lixeria e remove as imagens 
e verifica lojas inativas a mais de 90 dias e manda os produtos para a lixeira 

*/   
class StoreInactiveProductsTrash extends BatchBackground_Controller {

	public $deleteProduct;
		
	public function __construct()
	{
		parent::__construct();

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
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_orders');

		$this->load->library('DeleteProduct', [
            'productModel' => $this->model_products,
            'ordersModel' => $this->model_orders,
            'lang' => $this->lang
        ], 'deleteProduct');

		$this->deleteProduct->setModelsData([
            'usercomp' => 1,
            'userstore' => 0,
        ]);

    }

	// php index.php BatchC/StoreInactiveProductsTrash/run/null
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
        $this->findStoresInactives($days=90);
		
		/* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}

	function findStoresInactives($days=90) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;


		$sql = "SELECT * FROM stores WHERE active !=1 AND date_update < DATE_SUB(NOW(), INTERVAL ".$days." DAY) ";
		$query = $this->db->query($sql);
		$stores =  $query->result_array();		

		foreach($stores as $store) {
			echo " Mandando para a lixeira os produtos da Loja ".$store['id'].'-'.$store['name']."\n";

            $found = false; 
			$offset = 0;
			$limit = 500;
			while (true) {
				echo "Lendo produtos da loja ".$store['id']." no offset ".$offset."\n";
				$products = $this->model_products->getProductsByStore($store['id'], $offset, $limit);
				if (!$products) {
					echo "Acabou \n";
					break;
				}
				$offset += $limit;
				
				foreach($products as $product) {
					if ($product['status'] == Model_products::DELETED_PRODUCT) {
						continue; 
					}
					echo "Mandando para a Lixeria o produto ".$product['id']." da loja ".$store['id']."  -  ";
                    
					$response = $this->deleteProduct->moveToTrash([$product]);
					if (isset($response['errors'])) {
						$err = current($response['errors']) ?? '';
						var_dump($err);
					}
					else {
                        $found = true; 
						echo $response['message']."\n";	
                        $offset--; //apagou 1 então volto 1 no offset				
					}
           
				}
				
				$cnt = $this->model_queue_products_marketplace->countQueue();		
				
				while($cnt['qtd'] > 400) {
					echo "Dormindo pois tem ".$cnt['qtd']." na fila \n";
					sleep(60);
					$alterou = 0;
					$cnt = $this->model_queue_products_marketplace->countQueue();
				}					
				
			}
            if ($found) {
                $this->findProductsTrash(14, $store['id']);
            }
			
		}
	}
	
	function findProductsTrash($days=14, $store_id = null)
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

		while($exist) {
			if (is_null($store_id)) {
				$sql = 'SELECT * FROM products WHERE status = 3 AND image !="trash" AND date_update < DATE_SUB(NOW(), INTERVAL '.$days.' DAY) LIMIT '.$limit.' OFFSET '.$offset;
			} else {
				$sql = 'SELECT * FROM products WHERE status = 3 AND image !="trash" AND store_id='.$store_id.' LIMIT '.$limit.' OFFSET '.$offset;
			}

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
