<?php
/*
 * 
Envia os produtos par ao GPA
 * 
*/   
require APPPATH . "controllers/BatchC/Marketplace/Mirakl/SendOffers.php";
 class GPASendOffers extends SendOffers {

	public function __construct()
	{
		parent::__construct();

		$logged_in_sess = array(
			'id' => 1,
	        'username'  => 'batch',
	        'email'     => 'batch@conectala.com.br',
	        'usercomp' => 1,
	        'userstore' => 0,
	        'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
		$this->load->model( 'model_gpa_last_post');
    }
		
	// php index.php BatchC/GPA/GPASendOffers run null
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
		$this->dateLastInt = date('Y-m-d H:i:s');
		
		if (!is_null($params)) {// se passou parametros, é do HUB e não da ConectaLa e passou a loja 
			if ($params != 'null') {
				$store = $this->model_stores->getStoresData($params);
				if (!$store) {
					$msg = 'Loja '.$params.' passada como parametro não encontrada!'; 
					echo $msg."\n";
					$this->log_data('batch',$log_name,$msg,"E");
					return ;
				}
				$this->int_from = 'HUB';
				$this->int_to='H_'.$this->int_to;
				$this->store_id = $store['id'];
				$this->company_id = $store['company_id'];
			}
		}
		$this->getkeys($this->company_id,$this->store_id);
		$retorno = $this->syncProducts();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function lastPostModel() {
		// $this->last_post_table_name = 'gpa_last_post';
   		return $this->model_gpa_last_post; 
   	}
	
	function getAttributes($new_products){
		/* carrefour 
		return;
		 * */
		foreach($new_products as $new_product) {
			$attr_values =  explode("|",$new_product['seller_atributte']);
			foreach ($attr_values as $attr_value) {
			 	$attr = explode(":",$attr_value);
				if (!array_key_exists($attr[0], $this->attributes)) {  // se é um atributo novo, incluo
					$this->attributes[$attr[0]] = '';
				}
			}
		}
		
	}
	
	function getCSVHeader() {
		/* Carrefour 
		return  array('sku','product-id','product-id-type','description','internal-description','price','quantity',
						'state','update-delete');
		 * 
		 */
		return array('sku','product-id','product-id-type','description','internal-description','price','price-additional-info', 
						'price[channel=GPA]','price[channel=clubeextra]',
						'discount-price[channel=GPA]','discount-price[channel=clubeextra]',
						'discount-start-date[channel=GPA]','discount-end-date[channel=GPA]',
						'discount-start-date[channel=clubeextra]','discount-end-date[channel=clubeextra]',
						'quantity','min-quantity-alert','leadtime-to-ship','state','update-delete');
	}
	
	function getCSVProduct($offer) {
		return array(
				'sku' 										=> $offer['skulocal'],
				'product_id' 								=> $offer['skulocal'],
				'product_id_type' 							=> "SHOP_SKU",
				'description' 								=> '',
				'internal_description' 						=> '',
				'price' 									=> $offer['original_price'], 
				'price-additional-info' 					=> '', 
				'price[channel=GPA]' 						=> $offer['original_price'],
				'price[channel=clubeextra]' 				=> $offer['original_price'],
				'discount-price[channel=GPA]' 				=> ($offer['price'] >= $offer['original_price']) ? '' : $offer['price'],
				'discount-price[channel=clubeextra]' 		=> ($offer['price'] >= $offer['original_price']) ? '' : $offer['price'],
				'discount-start-date[channel=GPA]' 			=> '',
				'discount-end-date[channel=GPA]' 			=> '',
				'discount-start-date[channel=clubeextra]'	=> '',
				'discount-end-date[channel=clubeextra]' 	=> '',
				'quantity' 									=> ($offer['qty']<0) ? 0 : $offer['qty'],
				'min-quantity-alert'						=> '',
				'leadtime-to-ship'							=> $offer['leadtime_to_ship'],
				'state' 									=> '11',
				'update-delete' 							=> 'update'
			);

		return $line;
			
			/* Carrefour
		 $prdcsv = array(
				'sku' 					=> $offer['skulocal'],
				'product_id' 			=> $offer['skulocal'],
				'product_id_type' 		=> "SHOP_SKU",
				'description' 			=> '',
				'internal_description' 	=> '',
				'price' 				=> $offer['price'], 
				'quantity' 				=> ($offer['qty']<0) ? 0 : $offer['qty'],
				'state' 				=> '11',
				'update-delete' 		=> 'update'
			);
			 * 
			 */
	}


}
?>
