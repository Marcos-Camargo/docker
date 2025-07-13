<?php
/*
 
Realiza a atualização de preço, estoque e status Novo Mundo

*/   

require 'NovoMundo/NmIntegration.php';

 class NMSyncProducts extends BatchBackground_Controller 
 {
    private $integration = null;
    private $integration_data = null;
    protected $int_to = 'NM';
    protected $api_keys = null;

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
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_stores');
		$this->load->model('model_integration_last_post');
		
        $this->integration = new NMIntegration();
    }


	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) 
		{
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return ;
		}

        $this->log_data('batch', $log_name,'start '.trim($id." ".$params), "I");
		
		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType(1, $this->int_to, "CONECTALA", "DIRECT", 0);
		$this->api_keys = json_decode($this->integration_data['auth_data'], true);
		
		/* faz o que o job precisa fazer */
		$retorno = $this->syncProducts($this->api_keys);		
		$this->disablePrds($this->api_keys);
		
		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}
	

	function promotions() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$this->model_promotions->activateAndDeactivate();
	}
	

	function campaigns() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$this->model_campaigns->activateAndDeactivate();
	}	 
	

	function syncProducts($api_keys)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch', $log_name, "start", "I");	
		
		$sql = "select id, value , concat(lower(value), '_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		$estoqueIntTo=array();

		foreach ($mkts as $ind => $val)
		{
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value']; 
			$estoqueIntTo[$key_param] = $parm['value'];
		}	
		
		$int_to = $this->integration_data['int_to'];

        $sql = "SELECT i.* FROM integration_last_post i INNER JOIN products p ON p.id = i.prd_id WHERE i.data_ult_envio < p.date_update AND i.int_to = '".$int_to."'";
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
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'], $prd['price'], $int_to, $prd['has_variants']);

			if ($old_price !== $prd['price'])
				echo " Produto ".$prd['id']." tem preço ".$prd['price']." para ".$int_to." e preço base ".$old_price."\n";
			
			// acerto o preço da promoção do produto com o preço da promoção se tiver 
			$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'], $int_to);
							
			// e ai vejo se tem campanha
			if ($prd['promotional_price'] > $prd['price'] )
				$prd['price'] = $prd['promotional_price']; 
			
			$sku = $row['skumkt'];

			$prd['qty'] = $prd['qty'] < 5 ? 0 : $prd['qty'];

			$retorno = $this->updatePrd($api_keys, $sku, $prd, $row);

			if (!$retorno)
			{
				echo " Erro na NOVO MUNDO"; 
				continue;
			}
			
			// Consultar o Tipo_volume do produto aqui para fazer update do mesmo no NMLastPost
			$sql = "SELECT category_id FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$category_id_array = $cmd->row_array();  //Category_id esta como caracter no products
			$cat_id = json_decode ($category_id_array['category_id']);
			$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
						WHERE id =".intval($cat_id[0]).")";
			$cmd = $this->db->query($sql);
			$lido = $cmd->row_array();
			$tipo_volume_codigo = $lido['codigo'];
			
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
				// 'reputacao' => $row['reputacao'],
				// 'NVL' => $row['NVL'],
				// 'mkt_store_id' => $row['mkt_store_id'],
				'data_ult_envio' => $int_date_time,
				//'skulocal' => $row['skulocal'],
				//'skumkt' => $row['skumkt'],
				'skulocal' => $sku,
				'skumkt' => $sku,
				'tipo_volume_codigo' => $tipo_volume_codigo, 
				'qty_atual' => $prd['qty'],
				'largura' => $prd['largura'],
				'altura' => $prd['altura'],
				'profundidade' => $prd['profundidade'],
				'peso_bruto' => $prd['peso_bruto'],
				'store_id' => $prd['store_id'], 
				// 'marca_int_nm' => $row['marca_int_nm'],
				// 'categoria_nm' => "", 
				'crossdocking' => $crossdocking, 
				'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
				'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
				'freight_seller' =>  $loja['freight_seller'],
				'freight_seller_end_point' => $loja['freight_seller_end_point'],
				'freight_seller_type' => $loja['freight_seller_type'],
				
			);
			echo " Success Updating integration_last_post "; 
			// $insert = $this->db->replace('bling_ult_envio', $record);
			$insert = $this->model_integration_last_post->update($record, $row['id']);			
	    }

        return "PRODUCTS Synced VIA BF";
	} 


	private function disablePrds($api_keys) 
	{
		$int_to = $this->integration_data['int_to'];
		
		$categoria = $this->model_category->getCategoryData();

		$where_sku = "  ";

		$sql = "SELECT b.* FROM integration_last_post b ". 
		"INNER JOIN products p ON p.id= b.prd_id ".
		"LEFT JOIN prd_to_integration pi ON pi.skumkt = b.skumkt and pi.int_to ='".$int_to."' ".
		"WHERE  b.int_to='".$int_to."' ". $where_sku ." and pi.id is null and data_ult_envio < p.date_update ORDER BY int_to";
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			echo PHP_EOL . $key . '/'. count($data) . ' - ' . $row['skumkt'];

			$this->integration->disableAll($api_keys, $row['skumkt']);
	    }
	}


	private function updatePrd($api_keys, $sku, $prd, $integration_last_post)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$semerro = false;
		$int_to = $this->integration_data['int_to'];

		if ($prd['has_variants'] != '') 
		{
			$variants = $this->model_products->getVariants($prd['id']); 
			foreach ($variants as $variant) 
			{
				$skuvar = $sku.'-'.$variant['variant'];
				$prd['qty'] = $variant['qty'];
				echo "\n atualizando variant ".$skuvar." qty=".$prd['qty'];
				$response = $this->integration->updatePrices($api_keys, $skuvar, $prd);
				$response = $this->integration->update($api_keys, $skuvar, $prd);
			}

			if (!$semerro) 
			{
				$data_nm['data_ult_envio'] = date('Y-m-d H:i:s');
				$insert = $this->model_integration_last_post->update($data_nm, $integration_last_post['id']);
			} 
			
			return $semerro;

		}
		else
		{
			$response = $this->integration->updatePrices($api_keys, $sku, $prd);
			$response = $this->integration->update($api_keys, $sku, $prd);
				
			if ($response['httpcode'] == 200)
			{
				$semerro = true;
			}

			if ($response['httpcode'] == 404)
			{
				$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
					"VALUES(". $prd["id"] .", '". $sku . "', '".$int_to."', 'Atualização estoque', 'Erro ao atualizar estoque.', 0);";
				$this->db->query($sql);
			}
	
			if (!$semerro) 
			{ 
				$data_nm['data_ult_envio'] = date('Y-m-d H:i:s');
				$insert = $this->model_integration_last_post->update($data_nm, $integration_last_post['id']);
			} 

			return $semerro;
		}
		
	}
}
?>
