<?php
/*
 * 
Envia os produtos par ao GPA
 * 
*/   
require APPPATH . "controllers/BatchC/Marketplace/Mirakl/SendProducts.php";
 class GPASendProducts extends SendProducts {
	
	
	
	var $attributes = array();
		
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
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
    }
		
	// php index.php BatchC/GPA/GPASendProducts run null
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
		return array('category-code','product-sku','sku','product-title','weight','height','width','depth',
								'variantImage1','variantImage2','variantImage3','variantImage4','variantImage5',
								'variant-key','variant-code','variant-color','variant-second-color',
		        				'variant-size','variant-voltage','ean','description','seller-atributte');
		 * 
		 */
		$header = array('categoria','sku','name','weight','height','width','length','marca', 
								'imagem1','imagem2','imagem3','imagem4','imagem5',
								'ean1','description','is_kit');
		foreach ($this->attributes as $attrkey => $attrvalue) {
			$header[] = $attrkey; 
		}
		
		return $header; 
	}
	function getCSVProduct($new_product) {
		
		$line = array(
				$new_product['category_code'],
				$new_product['product_sku'],
				// $new_product['sku'], Não tem variação na GPA coloco as variações no titulo 
				preg_replace('/\s+/', ' ',trim($new_product['product_title'].' '.$new_product['variant_size'].' '.$new_product['variant_color'].' '.$new_product['variant_voltage'])),
				$new_product['weight'],
				$new_product['height'],
				$new_product['width'],
				$new_product['depth'],
				$new_product['brand'],
				$new_product['variantImage1'],
				$new_product['variantImage2'],
				$new_product['variantImage3'],
				$new_product['variantImage4'],
				$new_product['variantImage5'],
				$new_product['ean'],
				$new_product['description'],
				$new_product['is_kit'],
			);
		$attr_values =  explode("|",$new_product['seller_atributte']);
		foreach ($attr_values as $attr_value) {
		 	$attr = explode(":",$attr_value);
			if (array_key_exists($attr[0], $this->attributes)) {  // se é um atributo novo, incluo
				$this->attributes[$attr[0]] =$attr['1'];
			}
			else {
				echo ' Atributo '.$attr[0].' não mapeado '.PHP_EOL; 
			}
		}
		foreach ($this->attributes as $attrkey => $attrvalue) {
			$line[] = $attrvalue; 
			$this->attributes[$attrkey] = '';  // limpo para o próximo
		}
		return $line;
			
			/* Carrefour
		return array(
				$new_product['category_code'],
				$new_product['product_sku'],
				$new_product['sku'],
				$new_product['product_title'],
				$new_product['weight'],
				$new_product['height'],
				$new_product['width'],
				$new_product['depth'],
				$new_product['variantImage1'],
				$new_product['variantImage2'],
				$new_product['variantImage3'],
				$new_product['variantImage4'],
				$new_product['variantImage5'],
				$new_product['variant_key'],
				$new_product['variant_code'],
				$new_product['variant_color'],
				$new_product['variant_second_color'],
				$new_product['variant_size'],
				$new_product['variant_voltage'],
				$new_product['ean'],
				$new_product['description'],
				$new_product['seller_atributte'],
			);
			 * *
			 */
	}


}
?>
