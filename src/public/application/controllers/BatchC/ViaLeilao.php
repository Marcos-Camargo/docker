<?php
/*
 
Realiza o Leilão de Produtos 

*/

require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

class ViaLeilao extends BatchBackground_Controller {
        
    private $oAuth2 = null;
    private $integration = null;

	public function __construct()
	{
        parent::__construct();

        echo '[VIA VAREJO]['. strtoupper(__CLASS__) .'] '. strtoupper(__FUNCTION__)  .' ENVIRONMENT: ' . ENVIRONMENT . PHP_EOL;

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore'  => 0,
            'logged_in' => TRUE
        );
        
        $this->session->set_userdata($logged_in_sess);
        $usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
        $this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();

		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_integrations');
		$this->load->model('model_category');
		$this->load->model('model_stores');
		$this->load->model('model_orders');
		$this->load->model('model_errors_transformation');	
		$this->load->model('model_products_marketplace');
		$this->load->model('model_products_catalog');
		$this->load->model('model_atributos_categorias_marketplaces');	
    }

    private function getInt_to() {
		return ViaUtils::getInt_to();
	}

	function run($id=null,$params=null)
	{
		echo '[VIA VAREJO]['. strtoupper(__CLASS__) .'] '. strtoupper(__FUNCTION__) . PHP_EOL;
		
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		// $retorno = $this->promotions();
		// $retorno = $this->campaigns();

		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		$this->executeFase1($authorization);
		$this->syncProducts($authorization);
		$this->inactiveProducts($authorization);
        
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
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

	function lojista($company_id = null) 
	{
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		$this->executeFase1($authorization, $company_id);
		$this->syncProducts($authorization, $company_id);
	}

	function make_bling_ult_envio($skumkt) 
	{
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		$this->insertBlingUltEnvio($authorization, $skumkt);
	}
	

    function executeFase1($authorization, $company_id = null)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		$int_to = $this->getInt_to();

		$where_filter_company = '';
		if (!is_null($company_id)) {
			$where_filter_company = 'company_id = '. $company_id . ' and ';	
		}

		// Em análise
		$sql = "UPDATE prd_to_integration SET status_int = 99 WHERE ". $where_filter_company ." int_type = 13 AND int_to='".$this->getInt_to()."' AND status_int != 22 ";
		$query = $this->db->query($sql);

		// Em limpa tabela temporaria
		$sql = "DELETE FROM int_processing_new WHERE ". $where_filter_company ." int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);

		$parms = Array();

		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value, concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value'].'PERC'; 
			$parms[$key_param] = $parm['value'];
		}	
		
		// Calculo menos 30 dias de hoje 
		$date30=new DateTime();
		$date30->sub(new DateInterval('P30D'));
		
		$sells=array();
		$ufs=array();
		$lojas = $this->model_stores->getAllActiveStore();
		foreach($lojas as $loja) {
			$sells[$loja['id']] = $this->model_orders->getSellsOrdersCount($loja['id'],$date30->format('Y-m-d'));
			$ufs[$loja['id']] = $loja['addr_uf'];
		}
		
		$sql = "SELECT p.id, int_id, prd_id, p.int_to, p.status_int FROM prd_to_integration p, integrations i WHERE ". ($where_filter_company != '' ? 'p.' : '') . $where_filter_company ." p.int_type = 13 AND status = 1 AND status_int=99 AND int_id = i.id AND p.int_to='".$this->getInt_to()."' ORDER BY prd_id";
		$query = $this->db->query($sql);
		$mktlkd = $query->result_array();
		$prd_ant = "";
		$count = 0;
		foreach ($mktlkd as $ind => $val) {
			echo '[LEILÃO] '. ++$count ."/". count($mktlkd) . ' Prd_id: ' .  $val['prd_id'] . PHP_EOL;

			// Check QTY
			if ($prd_ant!=$val['prd_id']) {
				$prd_ant = $val['prd_id'];
				$sql = "SELECT * FROM products WHERE id = ".$val['prd_id'];
				$query = $this->db->query($sql);
				$prd = $query->row_array();
				if (($prd['status'] == 2) || ($prd['situacao'] == 1)) {
					// está inativo ou incompleto 
					continue;
				}
			}
			$key_param = $val['int_to'].'PERC'; 
			
			$qty_atual = (int) $prd['qty'] * $parms[$key_param] / 100; 
			$qty_atual = ceil($qty_atual); // arrendoda para cima  
			if ((int) $prd['qty'] < 5) {
				$qty_atual = 0; // será zero se for diferente da B2W
			}
			
			if ($qty_atual==0) { 
				$st_int = 10;   // SEM ESTOQUE MIN
			} else {
				$st_int = 0;
				
				//$loja  = $this->model_stores->getStoresData($prd['store_id']);
				$uf=0;
				if ($ufs[$prd['store_id']] == 'SP') {
					$uf=1;	
				}
				
				$ean = $prd['EAN'];
				if ($prd['is_kit'] == 1) {
					$ean ='IS_KIT'.$prd['id'];
				}
				if ($ean=='') {
					$ean ='NO_EAN'.$prd['id'];
				}
				//$sells = $this->model_orders->getSellsOrdersCount($prd['store_id'],$date30->format('Y-m-d'));
				
				// pego o preços do marketplace ou a promação do produto
				$preco = $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
				$preco = $this->model_promotions->getPriceProduct($val['prd_id'],$preco,$this->getInt_to());
				//$preco = $this->model_promotions->getPriceProduct($val['prd_id'],$preco);
				
				// inserir na tabela temporária
	        	$data = array(
	        		'int_to' => $val['int_to'],
	        		'prd_id' => $val['prd_id'],
	        		'EAN' => $ean,
	        		'price' => $preco,
	        		'qty' => $prd['qty'],
	        		'qty_atual' => $qty_atual,
	        		'sku' => $prd['sku'],
	        		'reputacao' => '100',  
	        		'NVL' => '0',
	        		'to_int_id' => $val['id'],
	        		'company_id' => $prd['company_id'],
	        		'store_id' => $prd['store_id'],
	        		'uf' => $uf,
	        		'sells' => $sells[$prd['store_id']],
	        		);
				$insert = $this->db->insert('int_processing_new', $data);
			}
			$sql = "UPDATE prd_to_integration SET status_int = ".$st_int. ", int_to ='".$val['int_to']."' WHERE id = ".$val['id'];
			$query = $this->db->query($sql);
		}

		/* Regra nova 
		 * Menor Preço ganha.
		 * Empate:
		 *  Loja de SP Ganha
		 *  Empate 
		 *   Quem tem mais venda
		 *   Empate 
		 * 		Loja mais antiga
		 */
		// Seleciona melhores produtos

		$sql = "SELECT * FROM int_processing_new WHERE ". $where_filter_company ." int_to='".$this->getInt_to()."' ORDER BY EAN ASC, CAST(price AS DECIMAL(12,2)) ASC, uf DESC, sells DESC, store_id ASC";
		$query = $this->db->query($sql);
		$mkt = $query->result_array();
		$int_ant = "";
		$ean_ant = "";
		$price = 0;
		$qty = 0;
		$alterados = array();
		$count = 0;
		foreach ($mkt as $ind => $val) {
			echo PHP_EOL . '[LEILÃO][REGRA] ' . ++$count ."/". count($mkt) . " ";
			if (($int_ant != $val['int_to']) OR ($ean_ant != $val['EAN']) OR (substr($val['EAN'],0,6)=='IS_KIT') OR (substr($val['EAN'],0,6)=='NO_EAN')) {
				echo "SELECIONADO...".$val['prd_id']. " ";
				$status_int = 1;
				$int_ant = $val['int_to'];
				$ean_ant = $val['EAN'];
				$price = $val['price'];
				$qty = $val['qty'];
				$ganhador = $val['prd_id'];
			} else {
				echo "PERDEU... ";
				if ($ean_ant == $val['EAN']) {
					if ($val['price'] > $price) {
						$status_int = 11;  // PREÇO ALTO
					} else {
						$status_int = 14; // critério de desempate
					}
				} 
			}

            $sql = "SELECT * FROM bling_ult_envio WHERE ". $where_filter_company ." int_to = '".$val['int_to']."' AND EAN = '".$val['EAN']."'";
			$cmd = $this->db->query($sql);
			
			$product_sent = false;
			$hasSkuInMkt = false;
			$skuInMkt = null;
			if ($cmd->num_rows() > 0) {
				$product_sent = true;
				$old = $cmd->row_array();
				
				$skuInMkt = $old['skumkt'];

				$variants = $this->getProductVariants($old['prd_id']);
				
				if ($variants['numvars'] != -1)
				{
					$skuInMkt .= '-' . $variants[0]['variant'];
				}

				$product_sent = $this->integration->hasSkuInMkt($authorization, $skuInMkt, true);
			}

			if ($product_sent) {    // Existe um antigo
				echo "EXISTE 1 ENVIADO... ";
				
				// Se é o mesmo produto, mesmo valor e quantidade, não precisa reenviar 
				if (($old['prd_id']==$val['prd_id'])){
					$status_int = 2;
					echo "EH O MESMO... ";
                }
                
				if ($old['prd_id'] != $ganhador)  {
					$this->integration->resetStock($authorization, $old['skumkt'], $old);
				}

				$skubling = $old['skumkt'];
			} else {
				Echo "Produto novo ".$val['prd_id']. " ";  
				if ((substr($val['EAN'],0,6)=='IS_KIT') OR (substr($val['EAN'],0,6)=='NO_EAN')) {
					$skubling = "P".$val['prd_id']."S".$val['store_id'].$this->getInt_to();  // sem ean
				}
				else {
					$skubling = $val['EAN'].$this->getInt_to();   // com ean
				}
			    
				// verifico se já tem algum sendo cadastrado ou que vai cadastrar
				$sql = "SELECT * FROM prd_to_integration WHERE  ". $where_filter_company ." (status_int=22 OR status_int=20) AND  
								int_to='".$val['int_to']."' AND skubling = '".$skubling."'";	
				$query = $this->db->query($sql);
				$prd_to_int = $query->row_array();
				if ($prd_to_int) {  
					if ($prd_to_int['prd_id'] == $val['prd_id']) {
						$status_int = $prd_to_int['status_int']; // se já está em cadastramento, mantem em cadastramento. 
					}
					else {  // não sou eu que está sendo cadastrado 
						// Não posso virar o novo dono até que o produto seja cadastrado. 
						$status_int = 23; // Coloco com em cadastramento temp. CarProdutsStatus acerta quando o primeiro produto for cadastrado
					}
				}
				else {// Não tem ninguém então sou eu
					if ($status_int == 1) { // eu sou o ganhador
						$status_int = 20;   // Vou cadastrar. 
					}
					else { // eu não sou o ganhador
						$status_int = 23; // Coloco com em cadastramento temp. CarProdutsStatus acerta quando o primeiro produto for cadastrado
					}
				}
			}
			
			// $sku_store_id = substr($skubling, strpos($skubling, 'S') + 1, strlen($skubling) - strpos($skubling, 'S') - 4);
			// if ($sku_store_id != $val['store_id'])
			// {
			// 	echo PHP_EOL . PHP_EOL . '[ALERT] PRD_ID: '. $val['prd_id'] .' SKU: ' . $skubling . ' STORE: '. $val['store_id']. ' EAN: '.  $val['EAN'] . PHP_EOL;
			// }
			// Atualiza status_int
			$sql = "UPDATE prd_to_integration SET status_int = ".$status_int. ", skubling = '".$skubling."' , skumkt = '".$skubling."' WHERE id = ".$val['to_int_id'];
			$query = $this->db->query($sql);

			if (($status_int == 1) || ($status_int == 2) || ($status_int == 20))
			{

				$sql = "SELECT * FROM bling_ult_envio WHERE skumkt = '".$skubling."' and int_to = '". $this->getInt_to() ."' and prd_id = ". $val['prd_id'];
				$cmd = $this->db->query($sql);

				if ($cmd->num_rows() <= 0) 
				{
					echo ' Não possui bling_ult_envio... ';

					if (!$this->integration->hasSkuInMkt($authorization, !is_null($skuInMkt) ? $skuInMkt : $skubling))
					{
						echo ' Não está cadastrado no VIA '. !is_null($skuInMkt) ? $skuInMkt : $skubling . $skubling;
						continue;
					}

					// Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
					$sql = "SELECT category_id FROM products WHERE id = ".$val['prd_id'];
					$cmd = $this->db->query($sql);

					$category_id_array = $cmd->row_array();  //Category_id esta como caracter no products
					$cat_id = json_decode ( $category_id_array['category_id']);
					$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
							WHERE id =".intval($cat_id[0]).")";
					$cmd = $this->db->query($sql);
					$lido = $cmd->row_array();
					$tipo_volume_codigo= $lido['codigo'];

					$sql = "SELECT * FROM products WHERE id = ". $val['prd_id'];
					$cmd = $this->db->query($sql);
					$prd = $cmd->row_array();
					
					$int_date = date('Y-m-d H:i:s');

					$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
					$loja  = $this->model_stores->getStoresData($prd['store_id']);
					
					echo ' Calculou dados bling_ult_envio... ';
				
					$data_bling = array(
						'int_to' => $val['int_to'],
						'company_id' => $val['company_id'],
						'EAN' => $val['EAN'],
						'prd_id' => $val['prd_id'],
						'price' => $val['price'],
						'qty' => $val['qty'],
						'sku' => $val['sku'],
						'reputacao' => $val['reputacao'],
						'NVL' => $val['NVL'],
						'mkt_store_id' => 0,         
						'data_ult_envio' => $int_date,
						'skubling' => $skubling,
						'skumkt' => $skubling,
						'tipo_volume_codigo' => $tipo_volume_codigo, 
						'qty_atual' => $val['qty_atual'],
						'largura' => $prd['largura'],
						'altura' => $prd['altura'],
						'profundidade' => $prd['profundidade'],
						'peso_bruto' => $prd['peso_bruto'],
						'store_id' => $prd['store_id'],
						'crossdocking' => $crossdocking, 
						'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        			'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        			'freight_seller' =>  $loja['freight_seller'],
						'freight_seller_end_point' => $loja['freight_seller_end_point'],
						'freight_seller_type' => $loja['freight_seller_type'],
						
					);
					$insert = $this->db->replace('bling_ult_envio', $data_bling);
					echo ' Cadastrou... ';
				}
				else {
					if ($status_int == 2) {
						$prd = $cmd->row_array();

						$preco = $this->model_promotions->getPriceProduct($prd['prd_id'],$prd['price'],$this->getInt_to());
						$prd["promotional_price"] = $preco;
						$prd["prazo_operacional_extra"] = $prd['crossdocking'];
						$prd["status"] = 1;

						$this->integration->updatePricesV2($authorization, $skubling, $prd);
						$this->integration->update($authorization, $skubling, $prd);
					}
				}
			}
		}
		
		$not_int_to = " AND int_to = '". $int_to ."' ";
		

		// vejo os produtos que mudaram de EAN e derrubo eles do marketplace
		$sql = "SELECT * FROM bling_ult_envio b WHERE ". $where_filter_company ." qty>=0 ".$not_int_to." AND substr(EAN,1,6) !='IS_KIT'";

		$query = $this->db->query($sql);
		$prods_derr = $query->result_array();
		$count = 0;
		foreach ($prods_derr as $prd_derr) {
			echo PHP_EOL . "[DERRUBA EAN] ". $count++ . "/". count($prods_derr);
			$sql = "SELECT * FROM products WHERE id = ".$prd_derr['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			if ($prd['EAN'] != $prd_derr['EAN']) {
				if (($prd['EAN'] != '') OR (substr($prd_derr['EAN'],0,6)!='NO_EAN')) {
					echo " prd_id = ".$prd_derr['prd_id']." mudou de EAN e será derrubado";
					$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE id = ".$prd_derr['id'];
					$cmd = $this->db->query($sql);
				}
			}
		}

		echo PHP_EOL . "Fim leilão" . PHP_EOL;

		return ; 
	}

    function syncProducts($authorization, $company_id = null)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		
		$int_to= $this->getInt_to();

		$where_filter_company = '';
		if (!is_null($company_id)) {
			$where_filter_company = 'company_id = '. $company_id . '  and ';	
		}
		
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
		

		$sql = "SELECT * FROM prd_to_integration WHERE ". $where_filter_company ." status_int=20 AND status=1 AND int_type=13 AND int_to='".$int_to."' ";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		
		$count = 0;
		foreach ($data as $key => $row) 
	    {
			echo PHP_EOL . '[SYNC] ' . ++$count ."/". count($data) . ' SKUMKT: ' . $row['skumkt'];
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
			// pego os dados do catálogo do produto se houver 
			if (!is_null($prd['product_catalog_id'])) {
				$prd_catalog = $this->model_products_catalog->getProductProductData($prd['product_catalog_id']); 
				$prd['name'] = $prd_catalog['name'];
				$prd['description'] = $prd_catalog['description'];
				$prd['EAN'] = $prd_catalog['EAN'];
				$prd['largura'] = $prd_catalog['width'];
				$prd['altura'] = $prd_catalog['height'];
				$prd['profundidade'] = $prd_catalog['length'];
				$prd['peso_bruto'] = $prd_catalog['gross_weight'];
				$prd['ref_id'] = $prd_catalog['ref_id']; 
				$prd['brand_code'] = $prd_catalog['brand_code'];
				$prd['brand_id'] = '["'.$prd_catalog['brand_id'].'"]'; 
				$prd['category_id'] = '["'.$prd_catalog['category_id'].'"]';
				$prd['image'] = $prd_catalog['image'];
			}
			
			$prd_variants = array();
			$hasSkuInMkt = false;

			if ($prd['has_variants'] != '')
			{
				echo ' Produto com Variação... ';
				$category = $this->getCategoriaVia((int)json_decode($prd['category_id'])[0]);		
				$attributesVia = $this->integration->getAttributesVia($authorization, $category['category_marketplace_id']);

				$this->model_errors_transformation->setStatusResolvedByProductId($prd['id'], $this->getInt_to());

				$found_all_variants = true;
				foreach (explode(';', $prd['has_variants']) as $variant) {
					if (!$this->integration->hasVariants($variant, $attributesVia))
					{
						$found_all_variants = false;
						$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
						"VALUES(". $prd["id"] .", '". $row['skumkt'] . "', '".$this->getInt_to()."', 'Cadastro Via Varejo', 'Variação: ". $variant ." não encontrada para essa categoria.', 0);";
						$this->db->query($sql);
					}
				}

				if (!$found_all_variants)
				{
					echo ' Contem erro de transformação (Variação)... ';
					continue;
				}

				$sql = "SELECT * FROM prd_variants WHERE prd_id = ".$row['prd_id'];
				$cmd = $this->db->query($sql);
				$variants = $cmd->result_array();

				foreach ($variants as $variant) {
					$item_variant = array();
					$item_variant['variant'] = $variant['variant'];
					$item_variant['qty'] = $variant['qty'];
					foreach (explode(';', $prd['has_variants']) as $key_has_variant => $has_variant) {
						if (strtoupper($has_variant) == 'COR')
						{
							$item_variant['color'] = explode(';', $variant['name'])[$key_has_variant];
						} else if (strtoupper($has_variant) == 'VOLTAGEM')
						{
							$item_variant['voltage'] = explode(';', $variant['name'])[$key_has_variant];
						} else if (strtoupper($has_variant) == 'TAMANHO')
						{
							$item_variant['size'] = explode(';', $variant['name'])[$key_has_variant];
						}
					}

					$skuInMkt = $row['skumkt']. '-' .$variant['variant'];
					
					if ($this->integration->hasSkuInMkt($authorization, $skuInMkt))
					{
						$hasSkuInMkt = true;
					}
					
					if (!$hasSkuInMkt) 
					{
						array_push($prd_variants, $item_variant);
					}
				}
			}
			else {
				echo ' Produto sem Variação... ';
				if ($this->integration->hasSkuInMkt($authorization, $row['skumkt']))
				{
					$hasSkuInMkt = true;
				}
			}
			
			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// acerto o preço do produto com o preço da promoção se tiver 
			$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA");
			$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'],"VIA");
			// $prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
			// $prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price']);
			// e ai vejo se tem campanha 
			// $prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'], $prd['promotional_price'], $int_to);
			if ($prd['promotional_price'] > $prd['price'] ) {
				$prd['price'] = $prd['promotional_price']; 
			}
			
    		$sku = $row['skumkt'];
			
			// leio o int_processing 
			$sql = "SELECT * FROM int_processing_new WHERE to_int_id = ".$row['id'];
			$cmd = $this->db->query($sql);
			$row_int_pro = $cmd->row_array();
			
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$prd['qty'] = $row_int_pro['qty_atual'];
			
			$status_int = 0;
			if (!$hasSkuInMkt) {
				echo 'No Has Sku in Mkt... ';
				$retorno = $this->inserePrd($authorization, $prd, $prd_variants, $sku, $estoqueIntTo[$int_to], $int_to);    
				if ($retorno === true) {
					$status_int = 22;
				}
			}
			else {
				echo 'Has Sku in Mkt... ';
				$retorno = true;
				$status_int = 2;
			}
			
			if (!$retorno) {
				echo " Erro na Via Varejo";
				// return "Erro na Via Varejo"; 
			} else { 
				//$nprds = count($retorno['produtos']);
				echo ' Sucesso ';
				// TROUXE PRA DENTRO DO SUCESSO
				$int_date = date('Y-m-d H:i:s');
				echo ' Status_int '. $status_int;
				$sql = "UPDATE prd_to_integration SET status_int='".$status_int."', date_last_int = '".$int_date."' WHERE id = ".$row['id'];
				$cmd = $this->db->query($sql);
				$xsku = $sku;
				

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
				
				$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$int_to."' AND prd_id = ".$row['prd_id'];
				$cmd = $this->db->query($sql);
				$bling_ult_envio = $cmd->row_array();
				$marca_int_bling= null;
				$categoria_bling = null;
				$mkt_store_id= ''; 
				if ($bling_ult_envio) {
					$marca_int_bling = $bling_ult_envio['marca_int_bling'];
					$categoria_bling = $bling_ult_envio['categoria_bling'];
					$mkt_store_id = $bling_ult_envio['mkt_store_id'];
				}
				$loja  = $this->model_stores->getStoresData($prd['store_id']);
				
	        	$data_bling = array(
	        		'int_to' => $row_int_pro['int_to'],
	        		'company_id' => $row_int_pro['company_id'],
	        		'EAN' => $row_int_pro['EAN'],
	        		'prd_id' => $row_int_pro['prd_id'],
	        		'price' => $prd['promotional_price'],
	        		'qty' => $row_int_pro['qty'],
	        		'sku' => $row_int_pro['sku'],
	        		'reputacao' => $row_int_pro['reputacao'],
	        		'NVL' => $row_int_pro['NVL'],
	        		'mkt_store_id' => $mkt_store_id,         
	        		'data_ult_envio' => $int_date,
	        		'skubling' => $sku,
	        		'skumkt' => $sku,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $row_int_pro['qty_atual'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id'], 
	        		'marca_int_bling' => $marca_int_bling, 
					'categoria_bling'=> $categoria_bling,
	        		'crossdocking' => $crossdocking, 
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
					
				);
				$insert = $this->db->replace('bling_ult_envio', $data_bling);
			}
			
		}
		echo PHP_EOL . "PRODUCTS Synced with VIA VAREJO" . PHP_EOL;
        return "PRODUCTS Synced with VIA VAREJO";
	} 
	
	function getCategoriaVia($product_category_id)
	{
		$sql = "SELECT * FROM categorias_marketplaces cm  WHERE int_to='".$this->getInt_to()."' AND category_id = ".$product_category_id;
		$cmd = $this->db->query($sql);
		return $cmd->row_array();
	}

	function inserePrd($authorization, $prd, $variants, $skumkt, $estoqueIntTo, $int_to) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		
		// pego a categoria 
		$categoria = $this->getCategoriaVia((int)json_decode($prd['category_id'])[0]);		
		
		$brand_id = json_decode($prd['brand_id']);
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $this->db->query($sql, $brand_id);
		$brand = $query->row_array();	

		if (!is_null($prd['product_catalog_id'])) {
			$atributos = $this->model_products_catalog->getAllProdutosAtributos($prd['product_catalog_id']);	
		} else {
			$atributos = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($prd['id']);
		}
		//  $atributos = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($prd['id']);
		$atributos_via = [];
		foreach($atributos as $key => $atributo) {
			if ($atributo["int_to"] == 'VIA') {
				array_push($atributos_via, $atributo);
			}
		}

		$retorno = $this->integration->register($authorization, $skumkt, $prd, $variants, $brand['name'], $categoria['category_marketplace_id'], $atributos_via);

		if ($retorno['httpcode'] >= 300) {
			$prod_data = $retorno['reqbody'];
            echo " Erro URL: /import/itens httpcode=".$retorno['httpcode']."\n"; 
            echo " RESPOSTA VIA ".print_r($retorno,true)." \n"; 
            echo " Dados enviados: ".print_r($prod_data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site: Via Varejo - httpcode: '.$retorno['httpcode']." RESPOSTA VIA: ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($prod_data,true),"E");
			
			return false;
		}

		$this->model_errors_transformation->setStatusResolvedByProductId($prd['id'], $this->getInt_to());

		return true;
	
	} 

	private function insertBlingUltEnvio($authorization, $skumkt)
	{
		$sql = "SELECT * FROM prd_to_integration WHERE skumkt = '". $skumkt . "' and int_to = 'VIA'";
		$cmd = $this->db->query($sql);
		$prd_to_integration = $cmd->row_array();

		$sql = "SELECT * FROM products WHERE id = ". $prd_to_integration['prd_id'];
		$cmd = $this->db->query($sql);
		$prd = $cmd->row_array();
		
		// pego o preço por Marketplace 
		$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
		// acerto o preço do produto com o preço da promoção se tiver 
		$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"VIA");
		// $prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
		// e ai vejo se tem campanha 
		// $prd['price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$this->getInt_to());
		
		$cat_id = json_decode ( $prd['category_id']);
		$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
				WHERE id =".intval($cat_id[0]).")";
		$cmd = $this->db->query($sql);
		$lido = $cmd->row_array();
		$tipo_volume_codigo= $lido['codigo'];

		$int_date = date('Y-m-d H:i:s');

		$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];

		$loja  = $this->model_stores->getStoresData($prd['store_id']);
		echo ' Calculou dados bling_ult_envio... ';

		$data_bling = array(
			'int_to' => 'VIA',
			'company_id' => $prd['company_id'],
			'EAN' => $prd['EAN'],
			'prd_id' => $prd['id'],
			'price' => $prd['price'],
			'qty' => $prd['qty'],
			'sku' => $prd['sku'],
			'NVL' => 0,
			'mkt_store_id' => 0,         
			'data_ult_envio' => $int_date,
			'skubling' => $skumkt,
			'skumkt' => $skumkt,
			'tipo_volume_codigo' => $tipo_volume_codigo, 
			'qty_atual' => $prd['qty'],
			'largura' => $prd['largura'],
			'altura' => $prd['altura'],
			'profundidade' => $prd['profundidade'],
			'peso_bruto' => $prd['peso_bruto'],
			'store_id' => $prd['store_id'],
			'crossdocking' => $crossdocking, 
			'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        'freight_seller' =>  $loja['freight_seller'],
			'freight_seller_end_point' => $loja['freight_seller_end_point'],
			'freight_seller_type' => $loja['freight_seller_type'],
			
		);
		$insert = $this->db->replace('bling_ult_envio', $data_bling);
		echo ' Cadastrou... ';
	}

	private function getProductVariants($id)
	{
		$product = $this->model_products->getProductData(0, $id);
		return $this->model_products->getProductVariants($id, $product['has_variants']);
	}

	function hasShipCompany($prd) {
		$this->load->library('calculoFrete');
		
		$store = $this->model_stores->getStoresData($prd['store_id']);
		$cat_id = json_decode ( $prd['category_id']);
		$sql = "SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id =".intval($cat_id[0]).")";
		$cmd = $this->db->query($sql);
		$lido = $cmd->row_array();
		$tipo_volume_codigo= $lido['codigo'];		
					
		$prd_info = array (
			'peso_bruto' =>(float)$prd['peso_bruto'],
			'largura' =>(float)$prd['largura'],
			'altura' =>(float)$prd['altura'],
			'profundidade' =>(float)$prd['profundidade'],
			'tipo_volume_codigo' => $tipo_volume_codigo,
		);
		return ($this->calculofrete->verificaCorreios($prd_info) ||
				$this->calculofrete->verificaTipoVolume($prd_info,$store['addr_uf'],$store['addr_uf']) ||
				$this->calculofrete->verificaPorPeso($prd_info,$store['addr_uf'])) ; 
	}
	
	function inactiveProducts($authorization = null)
	{
		// verifico os produtos que ficaram 99, provavelmente pois não tem mais transportadoras mas tem tabém os inativos
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$int_date_time = date('Y-m-d H:i:s');
		
		if (is_null($authorization)) {
			$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
			$api_keys = json_decode($integration['auth_data'], true);
			
			$client_id = $api_keys['client_id'];
	        $client_secret = $api_keys['client_secret']; 
	        $grant_code = $api_keys['grant_code']; 
			
			$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);
		}
		
		
		$sql = "SELECT * FROM prd_to_integration WHERE status = 0 AND status_int = 99 AND int_to='VIA'";
		$query = $this->db->query($sql);
		$prds_int = $query->result_array();
		foreach($prds_int as $prd_int) {
			
			echo "Processando produto ".$prd_int['prd_id']."\n";
			$sql = "SELECT * FROM products WHERE id = ?";
			$query = $this->db->query($sql,array($prd_int['prd_id']));
			$prd = $query->row_array();
			
			if (!is_null($prd_int['skubling'])) {
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to='VIA'";
				$query = $this->db->query($sql,array($prd_int['skubling']));
				$bling = $query->row_array();
			}
			
			if (is_null($prd_int['skubling']) || (!$bling)) {  // nunca integrou. 
				$status_int = false;
				if ($prd['status'] != 1) { // produto está inativo. 
					$status_int = 90;
				} 
				elseif ($prd['qty'] <= 0) { // produto está sem estoque 
					$status_int = 10;
				}
				elseif ($prd['situacao'] != 2) { // produto está incompleto
					$status_int = 90;
				}
				elseif (!$this->hasShipCompany($prd)) { // não tem transportadora.
					$status_int = 91 ;
				}else {
					// não sei o motivo melhor apagar o registro. 
					echo " não sei pq entrou aqui 1 - ".$prd_int['prd_id']."\n";
					// $sql = "DELETE FROM prd_to_integration WHERE id = ?";
					// $query = $this->db->query($sql, array($prd_int['id']));
				}
				if ($status_int) {
					$sql = "UPDATE prd_to_integration SET status_int = ?  WHERE id = ?";
					$query = $this->db->query($sql, array($status_int, $prd_int['id']));
				}
			} 
			else {
				
				if ($prd_int['prd_id'] !=  $bling['prd_id']) { // não é o ganhador do leilao, então posso marcar como desempate  
					echo '---- diferente '.$prd_int['prd_id']." ".$bling['prd_id']. "\n";
					$sql = "UPDATE prd_to_integration SET status_int = 14  WHERE id = ?";
					$query = $this->db->query($sql, array($prd_int['id']));
				}
				else { // é o ganhador então tem que descobrir pq não foi enviado e zerar a quantidade
					$status_int = false;
					if ($prd['status'] != 1) { // produto está inativo. 
						$status_int = 90;
					} 
					elseif ($prd['qty'] <= 0) { // produto está sem estoque 
						$status_int = 10;
					}
					elseif ($prd['situacao'] != 2) { // produto está incompleto
						$status_int = 90;
					}
					elseif ($this->hasShipCompany($prd)) { // não tem transportadora.
						echo " não sei pq entrou aqui 2 - ".$prd_int['prd_id']."  ".$prd_int['skubling']."\n";
					}else {
						$status_int =91;
					}
					if ($status_int) { // zera o estoque no marketplace
						
						
						if ($prd['has_variants']=="") {
							echo 'vou fazer reset do estoque '.$prd_int['skubling']. "\n";
							$response = $this->integration->resetStock($authorization, $prd_int['skubling'], $prd);
							$respVia = json_decode($response["content"], true);
							if (($response['httpcode'] == 204)  || (($response['httpcode'] == 422) && ($respVia['errors'][0]['code'] == "006.999"))){
								$sql = "UPDATE bling_ult_envio SET qty = 0, data_ult_envio = ? WHERE id = ?";
								$cmd = $this->db->query($sql,array($int_date_time,$bling['id']));
								$sql = "UPDATE prd_to_integration SET status_int=?, date_last_int = ? WHERE id= ?";
								$cmd = $this->db->query($sql,array($status_int,$int_date_time,$prd_int['id']));
							} 
							else {
								echo ' erro ao zerar o estoque  '.print_r($response["content"],true)."\n";
					            echo " RESPOSTA VIA ".print_r($response,true)." \n"; 
								$this->log_data('batch',$log_name, 'ERRO ao zerar estoque de '. $prd_int['skubling'].' Via Varejo - httpcode: '.$response['httpcode']." RESPOSTA VIA: ".print_r($respVia,true),"E");
							}
						}
						else {
							$prd_vars = $this->model_products->getVariants($prd_int['prd_id']);
							$tipos = explode(";",$prd['has_variants']);
							//var_dump($tipos);
							$ok =true;
							foreach($prd_vars as $prd_var) {
								$sku = $prd_int['skubling']."-".$prd_var['variant'];
								echo 'vou fazer reset do estoque '.$sku. "\n";
								$response = $this->integration->resetStock($authorization, $sku, $prd);
								$respVia = json_decode($response["content"], true);
								if (($response['httpcode'] != 204)  && (($response['httpcode'] != 422) || ($respVia['errors'][0]['code'] != "006.999"))){
									$ok =false;
									echo ' erro ao zerar o estoque  '.print_r($response["content"],true)."\n";
						            echo " RESPOSTA VIA ".print_r($response,true)." \n"; 
									$this->log_data('batch',$log_name, 'ERRO ao zerar estoque de '.$sku.' Via Varejo - httpcode: '.$response['httpcode']." RESPOSTA VIA: ".print_r($respVia,true),"E");
								}				
							}
							if ($ok) {
								$sql = "UPDATE bling_ult_envio SET qty = 0, data_ult_envio = ? WHERE id = ?";
								$cmd = $this->db->query($sql,array($int_date_time,$bling['id']));
								$sql = "UPDATE prd_to_integration SET status_int=?, date_last_int = ? WHERE id= ?";
								$cmd = $this->db->query($sql,array($status_int,$int_date_time,$prd_int['id']));
							}
							
						}
					} 
				}
			}
		}

	}
	
}