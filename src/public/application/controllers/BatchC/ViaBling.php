<?php
/*
 
Realiza a atualização de preço e estoque da VIA Varejo

*/   

require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

 class ViaBling extends BatchBackground_Controller {
		
	private $oAuth2 = null;
    private $integration = null;

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
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_stores');
		$this->load->model('model_blingultenvio');

		$this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();
    }

	function getInt_to() {
		return ViaUtils::getInt_to();
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
		
		$int_to = $this->getInt_to();
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA_D', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);
		
		/* faz o que o job precisa fazer */
		$retorno = $this->criaBling($authorization, $id);	
		// $retorno = $this->zeraVIA($authorization, $id);	
		
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	

	function criaBling($authorization, $sku_run)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		$estoqueIntTo=array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value']; 
			$estoqueIntTo[$key_param] = $parm['value'];
		}	
		
		$int_to = $this->getInt_to();
		
		$categoria = $this->model_category->getCategoryData();

		$where_sku = '';
		// if (!is_null($sku_run)) {
		// 	$where_sku = " and skumkt = '". $sku_run . "'";
		// }
		
		$cnt = 0;
		$offset = 9266;  // 7896911303865
		$offset = 9273; // 7898127606051
		$offset = 9308; // 7898562614246
		$offset = 0; 

		$sql = "SELECT * FROM prd_to_integration WHERE int_to='VIA' AND skumkt is not null  LIMIT 40000 offset ".$offset;
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
	    	if (($key== 9265) || ($key== 9272) || ($key== 9307)){
	    		continue;
	    	}
	    	echo "\n";
			echo PHP_EOL . $offset+$key . '/'. count($data) . ' ';
			
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to='VIA' AND skumkt = ?";
			$cmd = $this->db->query($sql, array($row['skumkt']));
			$bling_ult_envio = $cmd->row_array();
			if ($bling_ult_envio) { // se tem a bling_ult_envio, pego o ganhador do leilao para verificar 
				echo $row['skumkt']. " já está no bling";
				continue;
			}

			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();

			$response_status = $this->integration->getProduct($authorization, $row['skumkt']);
			if ($response_status['httpcode'] == 404) {
				if ($prd['has_variants'] == '') {
					echo $row['skumkt'] . ' Not Found ';
					continue;
				}
				// vejo se tem a variação zero
				$response_status = $this->integration->getProduct($authorization, $row['skumkt'].'-0');
				if ($response_status['httpcode'] == 404) {
				 	echo $row['skumkt'] . ' Not Found ';
					continue;
				}
				echo " uma variaçao "; 
			}
			
			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price;
			}
			// acerto o preço da promoção do produto com o preço da promoção se tiver
			if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($prd['variant'])) {
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA", $prd['variant']);
			}
			else
			{
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA");
			}

			// $prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
							
			// e ai vejo se tem campanha 
			//$prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$int_to);
			if ($prd['promotional_price'] > $prd['price'] ) {
				$prd['price'] = $prd['promotional_price']; 
			}
			
			$sku = $row['skumkt'];
			if (($prd['status']==2)  || ($prd['situacao'] == '1')) {
				echo 'Produto Inativo ou incompleto '.$prd['id'].' sku '.$row['skubling']." - zerando";

				$this->integration->disableAll($authorization, $row['skumkt']);
				$prd['qty'] = 0;
				continue;
			}
			if ($prd['qty'] < 5) {
				$prd['qty'] = 0;
			}

			$retorno = $this->updatePrdBF($authorization, $sku, $prd, $row);
			
			echo " ";
			if (!$retorno) {
				echo "Erro na VIA Varejo"; 
				// die; 
			} else { 				
				// Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
				$sql = "SELECT category_id FROM products WHERE id = ".$row['prd_id'];
				$cmd = $this->db->query($sql);
				$category_id_array = $cmd->row_array();  //Category_id esta como caracter no products
				$cat_id = json_decode ( $category_id_array['category_id']);
				$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
						 WHERE id =".intval($cat_id[0]).")";
				$cmd = $this->db->query($sql);
				$lido = $cmd->row_array();
				$tipo_volume_codigo= $lido['codigo'];
				// echo 'SQL = '. $sql."\n";
				// echo 'lido ='. print_r($lido,true)."\n";
				
				$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
				$int_date_time = date('Y-m-d H:i:s');
				$loja  = $this->model_stores->getStoresData($prd['store_id']);
				
				$ean = $prd['EAN'];
				if ($prd['is_kit'] == 1) {
					$ean ='IS_KIT'.$prd['id'];
				}
				if ($ean=='') {
					$ean ='NO_EAN'.$prd['id'];
				}
				
	        	$record = array(     	
	        		'int_to' => $row['int_to'],
	        		'company_id' => $prd['company_id'], 
	        		'prd_id' => $row['prd_id'],
	        		'EAN' => $ean, 
	        		'price' => $prd['promotional_price'],
	        		'qty' => $prd['qty'],
	        		'sku' => $prd['sku'],
	        		'reputacao' => '',
	        		'NVL' => '',
	        		'mkt_store_id' => '',
	        		'data_ult_envio' => $int_date_time,
	        		//'skubling' => $row['skubling'],
	        		//'skumkt' => $row['skumkt'],
	        		'skubling' => $sku,
	        		'skumkt' => $sku,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $prd['qty'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id'], 
	        		'marca_int_bling' => 'NA_MAO',
	        		'categoria_bling' => 'NA_MAO', 
	        		'crossdocking' => $crossdocking, 
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
					
	        	);
				
				if ($prd['qty'] > 0) {
					//var_dump($record);
					$insert = $this->db->insert('bling_ult_envio', $record);
					echo $row['skumkt']." cadastrado no bling_ult_envio";
				
					$cnt++;
				}else {
					$row['skumkt']." qty=0 ";
				};
				
			}
			
	    }
	    echo "\n alterei $cnt \n";
        return "PRODUCTS Synced with BLING";
	} 

	 function zeraVIA($authorization, $sku_run)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		$estoqueIntTo=array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value']; 
			$estoqueIntTo[$key_param] = $parm['value'];
		}	
		
		$int_to = $this->getInt_to();
		
		$categoria = $this->model_category->getCategoryData();

		$where_sku = '';
		// if (!is_null($sku_run)) {
		// 	$where_sku = " and skumkt = '". $sku_run . "'";
		// }

		$cnt = 0; 
		
		$sql = "SELECT * FROM prd_to_integration WHERE int_to='VIA' AND skumkt is not null";
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			echo PHP_EOL . $key . '/'. count($data) . ' ';

			$sql = "SELECT * FROM bling_ult_envio WHERE int_to='VIA' AND skumkt = ?";
			$cmd = $this->db->query($sql, array($row['skumkt']));
			$bling_ult_envio = $cmd->row_array();
			if ($bling_ult_envio) { // se tem a bling_ult_envio, pego o ganhador do leilao para verificar 
				$row = $bling_ult_envio; 
				echo $row['skumkt'] . ' cadastro bling ';
			}
			else {
				echo $row['skumkt'] . ' sem cadastro bling ';
			}

			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();

			$response_status = $this->integration->getProduct($authorization, $row['skumkt']);
			if ($response_status['httpcode'] == 404) {
				echo $row['skumkt'] . ' Not Found NA VIA ';
				//$this->log_data('batch',$log_name, 'INFO SKU: '. $row['skumkt']. ' não foi localizado no VIA Varejo. - httpcode: '.$response_status['httpcode'].' RESPOSTA: '.print_r($response_status,true),"I");
				//var_dump($response_status);
				continue; 
			}
			else {
				echo $row['skumkt'] . ' existe ';
			}

			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// acerto o preço da promoção do produto com o preço da promoção se tiver
			if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($prd['variant'])) {
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA", $prd['variant']);
			}
			else
			{
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA");
			}

			// $prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
							
			// e ai vejo se tem campanha 
			//$prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$int_to);
			if ($prd['promotional_price'] > $prd['price'] ) {
				$prd['price'] = $prd['promotional_price']; 
			}
			
			$sku = $row['skumkt'];
			if (($prd['status']!=1) || ($prd['situacao'] != '2') || ($prd['qty'] < 5) ) {
				echo 'Produto Inativo ou incompleto ou sem estoque '.$prd['id'].' sku '.$row['skubling']." - disable ";

				$this->integration->disableAll($authorization, $row['skumkt']);
			}
			else {
				echo ' com estoque ';
			}
			echo "\n";
			$cnt++;
		//	if ($cnt> 100) { die;}
			continue; 
			
			
	    }
        return "PRODUCTS Synced with BLING";
	} 
	
	function syncProductsBF($authorization, $sku_run)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		$estoqueIntTo=array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value']; 
			$estoqueIntTo[$key_param] = $parm['value'];
		}	
		
		$int_to = $this->getInt_to();
		
		$categoria = $this->model_category->getCategoryData();

		$where_sku = '';
		// if (!is_null($sku_run)) {
		// 	$where_sku = " and skumkt = '". $sku_run . "'";
		// }

		$sql = "SELECT b.* FROM bling_ult_envio b ". 
		"INNER JOIN products p ON p.id= b.prd_id ".
		"INNER JOIN prd_to_integration pi ON pi.skumkt = b.skumkt and pi.int_to ='".$int_to."' ".
		"WHERE  b.int_to='".$int_to."' ". $where_sku ." and data_ult_envio < p.date_update ORDER BY int_to";
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			echo PHP_EOL . $key . '/'. count($data) . ' ';

			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();

			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// acerto o preço da promoção do produto com o preço da promoção se tiver
			if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($prd['variant'])) {
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA", $prd['variant']);
			}
			else
			{
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA");
			}

			// $prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
							
			// e ai vejo se tem campanha 
			//$prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$int_to);
			if ($prd['promotional_price'] > $prd['price'] ) {
				$prd['price'] = $prd['promotional_price']; 
			}
			
			$sku = $row['skumkt'];

			if ($prd['qty'] < 0) {
				$prd['qty'] = 0;
			}

			$retorno = $this->updatePrdBF($authorization, $sku, $prd, $row);

			if (!$retorno) {
				echo " Erro na VIA Varejo"; 
			} else { 				
				// Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
				$sql = "SELECT category_id FROM products WHERE id = ".$row['prd_id'];
				$cmd = $this->db->query($sql);
				$category_id_array = $cmd->row_array();  //Category_id esta como caracter no products
				$cat_id = json_decode ( $category_id_array['category_id']);
				$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
						 WHERE id =".intval($cat_id[0]).")";
				$cmd = $this->db->query($sql);
				$lido = $cmd->row_array();
				$tipo_volume_codigo= $lido['codigo'];
				// echo 'SQL = '. $sql."\n";
				// echo 'lido ='. print_r($lido,true)."\n";
				
				$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
				$int_date_time = date('Y-m-d H:i:s');
				$loja  = $this->model_stores->getStoresData($prd['store_id']);
				
	        	$record = array(
	 				'id'=> $row['id'],       	
	        		'int_to' => $row['int_to'],
	        		'company_id' => $prd['company_id'], 
	        		'prd_id' => $row['prd_id'],
	        		'price' => $prd['promotional_price'],
	        		'qty' => $prd['qty'],
	        		'sku' => $prd['sku'],
	        		'reputacao' => $row['reputacao'],
	        		'NVL' => $row['NVL'],
	        		'mkt_store_id' => $row['mkt_store_id'],
	        		'data_ult_envio' => $int_date_time,
	        		//'skubling' => $row['skubling'],
	        		//'skumkt' => $row['skumkt'],
	        		'skubling' => $sku,
	        		'skumkt' => $sku,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $prd['qty'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id'], 
	        		'marca_int_bling' => $row['marca_int_bling'],
	        		'categoria_bling' => $row['categoria_bling'], 
	        		'crossdocking' => $crossdocking, 
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
					
				);
				echo " Success Updating Bling_ult_envio "; 
				$insert = $this->db->replace('bling_ult_envio', $record);
			}
			
	    }
        return "PRODUCTS Synced VIA BF";
	} 

	private function updatePrd($authorization, $sku, $prd, $bling_ult_envio)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$response = $this->integration->update($authorization, $sku, $prd);

		if ($response['httpcode'] == 422) {
			$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
				"VALUES(". $prd["id"] .", '". $sku . "', '".$this->getInt_to()."', 'Atualização estoque/preços', 'Alteração não permitida, variação de preço muito grande.', 0);";

			$this->db->query($sql);

			$bling_ult_envio['data_ult_envio'] = date('Y-m-d H:i:s');
		   	$insert = $this->db->replace('bling_ult_envio', $bling_ult_envio);
		}

		return $response['httpcode'] == 204;
	}

	private function updatePrdBF($authorization, $sku, $prd, $bling_ult_envio)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if ($prd['has_variants'] != '') {
			$semerro = false;
			$variants = $this->model_products->getVariants($prd['id']); 
			foreach ($variants as $variant) {
				$skuvar = $sku.'-'.$variant['variant'];
				$prd['qty'] = $variant['qty'];
				echo "\n atualizando variant ".$skuvar." qty=".$prd['qty'];
				$response = $this->integration->updateBF($authorization, $skuvar, $prd);
				 
				if ($response['httpcode'] == 422) {
					//$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
					//	"VALUES(". $prd["id"] .", '". $sku . "', '".$this->getInt_to()."', 'Atualização estoque', 'Erro ao atualizar o estoque da variação ".$variant['variant'].".', 0);";
		
					//$this->db->query($sql);
				}
				if ($response['httpcode'] == 204) { // se pelo menos 1 passou é que já tem a variação lá 
					$semerro = true;
				}
				
			}
			if (!$semerro) { // houve algum erro marco para parar de tentar enviar. 
				// RAFAEL rever esta parte. Se dá erro na atualização de estoque, provavelmente o produto nem está cadastrado  na Via varejo. 
				$databling['data_ult_envio'] = date('Y-m-d H:i:s');
				$insert = $this->model_blingultenvio->update($databling, $bling_ult_envio['id']);
			} 
			return $semerro;
		}else {
			$response = $this->integration->updateBF($authorization, $sku, $prd);

			if ($response['httpcode'] == 422) {
				$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
					"VALUES(". $prd["id"] .", '". $sku . "', '".$this->getInt_to()."', 'Atualização estoque', 'Erro ao atualizar estoque.', 0);";
	
				$this->db->query($sql);
				//$bling_ult_envio['data_ult_envio'] = date('Y-m-d H:i:s');
			   	//$insert = $this->db->replace('bling_ult_envio', $bling_ult_envio);
			   	$databling['data_ult_envio'] = date('Y-m-d H:i:s');
				$insert = $this->model_blingultenvio->update($databling, $bling_ult_envio['id']);
			}
	
			return $response['httpcode'] == 204;
		}
		
	}
}
?>
