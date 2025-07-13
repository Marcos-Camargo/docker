<?php
/*
 
Realiza o Leilão de Produtos e atualiza o B2W 

*/   
 class SkyHubLeilao extends BatchBackground_Controller {
	
	var $int_to='B2W';
	var $apikey='';
	var $email='';
		
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
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_stores');
		$this->load->model('model_orders');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_products_catalog');
		
    }

	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setApikey($apikey) {
		$this->apikey = $apikey;
	}
	function getApikey() {
		return $this->apikey;
	}
	function setEmail($email) {
		$this->email = $email;
	}
	function getEmail() {
		return $this->email;
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
		
		echo " ESSA ROTINA ESTÁ DESATIVADA \n";
		die;
		/* faz o que o job precisa fazer */
		$this->getkeys(1,0);
		//$retorno = $this->promotions();
		//$retorno = $this->campaigns();
		$retorno = $this->toSkyHubFase1();
		$retorno = $this->syncProducts();
	    $retorno = $this->inactiveProducts();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setEmail($api_keys['email']);
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
	
 	function toSkyHubFase1()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// Em análise
		$sql = "UPDATE prd_to_integration SET status_int = 99 WHERE int_type = 13 AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		// Em limpa tabela temporaria
		//$sql = "DELETE FROM int_processing_skyhub WHERE int_to='".$this->getInt_to()."'";
		$sql = 'TRUNCATE int_processing_skyhub';
		$query = $this->db->query($sql);

		$parms = Array();
		// filtro de estoque minimo de cada marketplace
		/* NÃO BLOQUEIA MAIS POR ESTOQUE MÍNIMO
		$sql = "select id, value ,concat(lower(value),'_estoque_min') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$parms[$val['value']] = $parm['value'];
		}
		 * 
		 * Poder ser que volte... 
		 */
		 
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
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
		
		$sql = "SELECT p.id,int_id,prd_id,i.int_to FROM prd_to_integration p, integrations i WHERE p.int_type = 13 AND status = 1 AND int_id = i.id AND p.int_to='".$this->getInt_to()."' ORDER BY prd_id";
		$query = $this->db->query($sql);
		$mktlkd = $query->result_array();
		$prd_ant = "";
		foreach ($mktlkd as $ind => $val) {
			// Check QTY
			if ($prd_ant!=$val['prd_id']) {
				$prd_ant = $val['prd_id'];
				$sql = "SELECT * FROM products WHERE id = ".$val['prd_id'];
				$query = $this->db->query($sql);
				$prd = $query->row_array();
				if (($prd['status'] == 2) || ($prd['situacao'] == 1)) {
					// está inativo ou incompleto 
					$sql = "UPDATE prd_to_integration SET status = 0 WHERE id = ".$val['id'];
					$query = $this->db->query($sql);
					continue;
				}
			}
			if (!array_key_exists($prd['store_id'],$ufs)) {
				$sql = "UPDATE prd_to_integration SET status = 0 WHERE id = ".$val['id'];
				$query = $this->db->query($sql);
				continue;
			}
			$key_param = $val['int_to'].'PERC'; 
			
			$qty_atual = (int) $prd['qty'] * $parms[$key_param] / 100; 
			$qty_atual = ceil($qty_atual); // arrendoda para cima  
			if ((int) $prd['qty'] < 5) {
			    $qty_atual = (int) $prd['qty'];
				// $qty_atual = 0; será zero se for diferente da B2W
			}
			
			//echo 'mkt= '.$val['int_to'].' qty='.$prd['qty'].' Nova quantidade = '. $qty_atual. ' perc ='.$parms[$key_param].'/n';
		//	if ($qty_atual<$parms[$val['int_to']]) { // Não tem mais estoque mínimo por marketplace por enquanto
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
				}elseif ($ean=='') {
					$ean ='NO_EAN'.$prd['id'];
				}else {
					$ean = substr('0000000000'.$prd['EAN'],-13);
				}
				
				//$sells = $this->model_orders->getSellsOrdersCount($prd['store_id'],$date30->format('Y-m-d'));
				
				// pego o preços do marketplace ou a promação do produto
				$preco = $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
				$preco = $this->model_promotions->getPriceProduct($val['prd_id'],$preco,"B2W");
				// $preco = $this->model_promotions->getPriceProduct($val['prd_id'],$preco);
				$preco = round($preco,2);
				// inserir na tabela temporária
	        	$data = array(
	        		'int_to' => $val['int_to'],
	        		'prd_id' => $val['prd_id'],
	        		'EAN' => $ean,
	        		'price' => $preco,
	        		'qty' => $prd['qty'],
	        		'qty_atual' => $qty_atual,
	        		'sku' => $prd['sku'],
	        		'reputacao' => '100',  //rick - pegar a reputação da empresa
	        		'NVL' => '0',
	        		'to_int_id' => $val['id'],
	        		'company_id' => $prd['company_id'],
	        		'store_id' => $prd['store_id'],
	        		'uf' => $uf,
	        		'sells' => $sells[$prd['store_id']],
	        		);
				$insert = $this->db->insert('int_processing_skyhub', $data);
			}
			$sql = "UPDATE prd_to_integration SET status_int = ".$st_int. ", int_to ='".$val['int_to']."' WHERE id = ".$val['id'];
			$query = $this->db->query($sql);
		}

// Fase 2
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

		$sql = "SELECT * FROM int_processing_skyhub WHERE int_to='".$this->getInt_to()."' ORDER BY EAN ASC, CAST(price AS DECIMAL(12,2)) ASC, uf DESC, sells DESC, store_id ASC";
		//$sql = "SELECT * FROM int_processing_skyhub WHERE int_to='".$this->getInt_to()."' ORDER BY int_to ASC, EAN ASC, NVL DESC, company_id ASC";
		$query = $this->db->query($sql);
		$mkt = $query->result_array();
		$int_ant = "";
		$ean_ant = "";
		$price = 0;
		$qty = 0;
		foreach ($mkt as $ind => $val) {
			//echo $val['sku']."\n";
			if (($int_ant != $val['int_to']) OR ($ean_ant != $val['EAN']) OR (substr($val['EAN'],0,6)=='IS_KIT') OR (substr($val['EAN'],0,6)=='NO_EAN')) {
				echo "SELECIONADO...".$val['prd_id']."\n";
				$status_int = 1;
				$int_ant = $val['int_to'];
				$ean_ant = $val['EAN'];
				$price = $val['price'];
				$qty = $val['qty'];
				$ganhador = $val['prd_id'];
			} else {
				echo "PERDEU...".$val['prd_id']."\n";
				if ($ean_ant == $val['EAN']) {
					if ($val['price'] > $price) {
						$status_int = 11;  // PREÇO ALTO
					} else {
						$status_int = 14; // critério de desempate
					}
					/* Era assim... 
					} elseif ($val['qty'] < $qty) {
						$status_int = 12;  // ESTOQUE MENOR  
					} else {
						$status_int = 13;  // REPUTACAO
					}
					 * 
					 */							
				} 
			}
			// Verifica se precisa sair
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to = '".$val['int_to']."' AND EAN = '".$val['EAN']."'";
			//echo $sql."\n";
			$cmd = $this->db->query($sql);
			if($cmd->num_rows() > 0) {    // Existe um antigo
				//echo "EXISTE 1 ENVIADO...\n";
				$old = $cmd->row_array();
				// Se mesma empresa e mesmo valor , não precisa reenviar 
				if (($old['company_id']==$val['company_id']) && ($old['price']==$val['price']) && ($old['qty']==$val['qty'])){
					//echo "EH O MESMO... \n";
					$status_int = 1; // era 2 mas quero re-enviar todos novamente. 
				}
				if ($old['prd_id'] != $ganhador)  {
				// if ((($old['company_id']==$val['company_id']) && ($status_int!=2)) OR (($old['company_id']!=$val['company_id'])  && ($status_int==1))){
					// precisa cair do bling	
					echo "TEM QUE CAIR...".$old['prd_id']." ".$old['skubling']." ".$this->getInt_to()."\n";
					if (!$this->zeraEstoque($old['skumkt'], $old['prd_id'])) {
						// deu erro na B2W encerro por aqui.... 
						//$this->gravaFimJob();
						// die; 
					}
					
					$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE int_to = '".$val['int_to']."' AND EAN = '".$val['EAN']."'";
					$cmd = $this->db->query($sql);
					//echo "DERRUBADO da ".$this->getInt_to()."\n-------------------\n";
					
				}
				$skubling = $old['skubling'];
			} else {
				// Produto novo 
				//$skubling = strtoupper(substr(md5(uniqid(mt_rand(9999999,9999999999), true)), 0, 13));
				if ((substr($val['EAN'],0,6)=='IS_KIT') OR (substr($val['EAN'],0,6)=='NO_EAN')) {
					$skubling = "P".$val['prd_id']."S".$val['store_id'].$this->getInt_to();
				}
				else {
					$skubling = $val['EAN'].$this->getInt_to();
				}
				// recupero o SKU se já existia antes e o bling_ult_envio foi removido
				/*
				$sql = "SELECT * FROM prd_to_integration WHERE int_to = '".$val['int_to']."' AND prd_id = '".$val['prd_id']."'";
				$cmd = $this->db->query($sql);
				$prd_to = $cmd->row_array();
				if ($prd_to) {
					if (!is_null($prd_to['skubling'])) {
						$skubling = $prd_to['skubling'];
					}
				}
				*/
			}
			
			// Atualiza status_int
			$sql = "UPDATE prd_to_integration SET status_int = ".$status_int. ", skubling = '".$skubling."' , skumkt = '".$skubling."' WHERE id = ".$val['to_int_id'];
			$query = $this->db->query($sql);
			
		}
		
		$not_int_to = " AND int_to = 'B2W' ";  // Só trago a B2W 
		// $not_int_to = "";
		
		// vejo os produtos que mudaram de EAN e derrubo eles do marketplace
		$sql = "SELECT * FROM bling_ult_envio b WHERE qty>0 ".$not_int_to." AND substr(EAN,1,6) !='IS_KIT'";
		$query = $this->db->query($sql);
		$prods_derr = $query->result_array();
		foreach ($prods_derr as $prd_derr) {
			$sql = "SELECT * FROM products WHERE id = ".$prd_derr['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			//echo "ID =".$prd_derr['id']." EAN B=".$prd_derr['EAN']." EAN PROD=".$prd['EAN']."\n"; 
			if ($prd['EAN'] != $prd_derr['EAN']) {
				//echo "Diferente\n"; 
				if (($prd['EAN'] != '') OR (substr($prd_derr['EAN'],0,6)!='NO_EAN')) {
					echo "prd_id = ".$prd_derr['prd_id']." mudou de EAN e será derrubado\n";
					if (!$this->zeraEstoque($prd_derr['skumkt'], $prd_derr['prd_id'])) {
							// deu erro na B2W encerro por aqui.... 
							//$this->gravaFimJob();
						 die; 
					}
					else {
						$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE id = ".$prd_derr['id'];
						$cmd = $this->db->query($sql);
					}
				}
			}
		}
		
		// marco todos para enviar novamente
		$sql = "select * from prd_to_integration WHERE status_int=2 ".$not_int_to;
		$query = $this->db->query($sql);
		$prds_to_int = $query->result_array();
		foreach ($prds_to_int as $prd_to_int) {
			$sql = "select * from products WHERE id =".$prd_to_int['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			echo "produto ".$prd['id'].' data do produto = '.$prd['date_update'].' Ultima integração em '.$prd_to_int['date_last_int']."\n";
			if ($prd_to_int['date_last_int'] < $prd['date_update']) {
				$sql = "UPDATE prd_to_integration SET status_int = 1, status=1 WHERE id = ".$prd_to_int['id'];
				$query = $this->db->query($sql);
			}
		}
		return; 

	}	

	function zeraEstoque($sku, $prd_id)
	
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$url = 'https://api.skyhub.com.br/products/'.$sku;

		// zera o Pai
		$zera_estoque = Array (
						'product' => array(
						    "qty" => 0
						) 
					);

		$json_data = json_encode($zera_estoque);
		$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
		if (($resp['httpcode'] == 429) || ($resp['httpcode'] == 504)) { // estourou o limite
			sleep(60);
			$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
		}
		if ($resp['httpcode']!="204")  {  // created
			echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
			$this->log_data('batch',$log_name, "ERRO ao zerar estoque no ".$this->getInt_to()." - httpcode: ".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return false;
		}
		
		$sql = "SELECT * FROM products WHERE id = ?";
		$query = $this->db->query($sql,array($prd_id));
		$prd = $query->row_array();
		 // zera os filhos
		if ($prd['has_variants']!="") {
			$variations = array();
	        $prd_vars = $this->model_products->getProductVariants($prd['id'],$prd['has_variants']);
	        $tipos = explode(";",$prd['has_variants']);
	        $variation_attributes = array();
			foreach($prd_vars as $value) {
				if (isset($value['sku'])) {
					$qty = 0; 
					$skumkt = $sku.'-'.$value['variant'];
					echo "Variação: Estoque id:".$prd['id']." ".$skumkt." estoque:".$prd['qty']." enviado:".$qty."\n";
					
					$product = Array (
						'variation' => array(
						    "qty" => ceil($qty)
						) 
					);
					$url = 'https://api.skyhub.com.br/variations/'.$skumkt;
	
					$json_data = json_encode($product);
					$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
					if (($resp['httpcode'] == 429) || ($resp['httpcode'] == 504))  { // estourou o limite
						sleep(60);
						$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
					}
					if ($resp['httpcode']!="204")  {  // created
						echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
						$this->log_data('batch',$log_name, "ERRO ao alterar estoque variação ".$skumkt." no ".$this->getInt_to()." - httpcode: ".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
					}
				}
			}	
		}
		return true;
	}
	
    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$this->getkeys(1,0);
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
		
		//$categoria = $this->model_category->getCategoryData();
		
		$sql = "SELECT * FROM prd_to_integration WHERE status_int=1 AND status=1 AND int_type=13 AND int_to='".$this->getInt_to()."'";	
        //$sql = "SELECT * FROM prd_to_integration WHERE date_update > date_last_int AND status_int=1 AND status=1 AND int_type=13 AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
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
		
			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// acerto o preço do produto com o preço da promoção se tiver 
			$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"B2W");
			// $prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
			// e ai vejo se tem campanha 
		    // $prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$this->getInt_to());
			if ($prd['promotional_price'] > $prd['price']) {
				$prd['price'] = $prd['promotional_price']; 
			}
			$prd['price'] = round($prd['price'],2);
			$prd['promotional_price'] = round($prd['promotional_price'],2);
			
			if ($prd['is_kit']) {
				$prd['promotional_price'] = $prd['price']; 
				$productsKit = $this->model_products->getProductsKit($prd['id']);
				$original_price = 0; 
				foreach($productsKit as $productkit) {
					$original_price += $productkit['qty'] * $productkit['original_price'];
				}
				$prd['price'] = $original_price;
				echo " KIT ".$prd['id'].' preço de '.$prd['price'].' por '.$prd['promotional_price']."\n";  
			}
			
    		$sku = $row['skubling'];
			
			if ($prd['category_id'] == '[""]') {	
				$msg= 'Categoria não vinculada';
				$this->model_products->update(array('situacao'=>1), $prd['id']);
				$this->errorTransformation($prd['id'],$row['skubling'],$msg, $row['id']);
				continue;
			}
			// leio o int_processing 
			$sql = "SELECT * FROM int_processing_skyhub WHERE to_int_id = ".$row['id'];
			$cmd = $this->db->query($sql);
			$row_int_pro = $cmd->row_array();
			
			if (!$row_int_pro) {
				$msg=  "*** Não foi possivel encontrar o registro do produto no int_processing_skyhub para o produto ".$prd['id']." e to_int_id = ".$row['id']; 
				echo $msg."\n";
				$this->log_data('batch',$log_name, $msg,"E");
				continue;
			}
			
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$prd['qty'] = $row_int_pro['qty_atual'];
			
			$retorno = $this->inserePrd($prd,$sku,$estoqueIntTo[$this->getInt_to()]);    
			
			if (!$retorno) {
				continue; 
			} else { 
				//$nprds = count($retorno['produtos']);
				
				// TROUXE PRA DENTRO DO SUCESSO
				$int_date_time = date('Y-m-d H:i:s');
				$sql = "UPDATE prd_to_integration SET status_int=2 , date_last_int = ? WHERE id = ".$row['id'];
				$cmd = $this->db->query($sql,array($int_date_time));
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
				
				$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$row['prd_id']." AND EAN = '".$row_int_pro['EAN']."'";
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
				
	        	$data = array(
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
	        		'data_ult_envio' => $int_date_time,
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
				if ($bling_ult_envio) {
					$insert = $this->model_blingultenvio->update($data, $bling_ult_envio['id']);
				}else {
					//$insert = $this->model_blingultenvio->create($data);
					$insert = $this->db->replace('bling_ult_envio', $data);
				}
			}
			
	    }
        echo " ------- Processo de envio de produtos terminou\n";
        return "PRODUCTS Synced with B2W";
    } 

	function inserePrd($prd,$skumkt,$estoqueIntTo) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		
		$catP = json_decode($prd['category_id']);
		// pego a categoria 
		$categoria = $this->model_category->getCategoryData($catP);
		
		// Verifico se é catálogo para pegar a imagem do lugar certo
		if (!is_null($prd['product_catalog_id'])) {
			$pathImage = 'catalog_product_image';
		}
		else {
			$pathImage = 'product_image';
		}
		
		$brand_id = json_decode($prd['brand_id']);
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $this->db->query($sql, $brand_id);
		$brand = $query->row_array();
		
		$description = substr(htmlspecialchars(strip_tags(str_replace("<br>"," \n",$prd['description'])), ENT_QUOTES, "utf-8"),0,3800);
		$description = str_replace("&amp;amp;"," ",$description);
		$description = str_replace("&amp;"," ",$description);
		$description = str_replace("&nbsp;"," ",$description);
		if (($description=='') || (trim(strip_tags($prd['description'])," \t\n\r\0\x0B\xC2\xA0")) == ''){
			$description= substr(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8"),0,98);
		}
		$produto = array(
			"sku" 			=> $skumkt,
			"name"			=> substr(strip_tags(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8")," \t\n\r\0\x0B\xC2\xA0"),0,98),
			"description" 	=> $description,
			"status" 		=> "enabled",
			"price" 		=> (float)$prd['price'], 
			"promotional_price" => (float)$prd['promotional_price'],
			"weight"  		=> (float)$prd['peso_bruto'],
			"height"		=> (float)($prd['altura'] < 2) ? 2 : $prd['altura'],
			"width"			=> (float)($prd['largura'] < 11) ? 11 : $prd['largura'],
			"length"		=> (float)($prd['profundidade'] < 16) ? 16 : $prd['profundidade'],
			"qty"			=> (int)$prd['qty'],
			"brand"			=> substr($brand['name'],0,29), // limite da B2w
			"ean"			=> $prd['EAN'],
			"nbm"			=> $prd['NCM'],
			"categories"	=> Array(array(
				"code"			=> $categoria['id'],
				"name"			=> $categoria['name'],
			)),
			"specifications" => array(
				array(
					"key" => "CrossDocking",
					"value" => $prd['prazo_operacional_extra'],
				),
				array(
					"key" => "store_stock_cross_docking",
					"value" => $prd['prazo_operacional_extra'],
				),
				array(
					"key" => "Garantia",
					"value" => $prd['garantia'],
				),
			)
		);
		// echo "IMAGENS:".$prd['image']."\n";
		$imagens = array();
		if ($prd['image']!="") {
			$numft = 0;
			if (strpos("..".$prd['image'],"http")>0) {
				$fotos = explode(",", $prd['image']);	
				foreach($fotos as $foto) {
					$imagens[$numft++] = $foto;
					if ($numft==5) { // limite de 5 fotos na skyhub
						break;
					} 
				}
			} else {
				$fotos = scandir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image']);	
				foreach($fotos as $foto) {
					if (($foto!=".") && ($foto!="..")) {
						if(!is_dir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image'].'/'.$foto)) {
							$imagens[$numft++] = base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $foto);
						}
					}
					if ($numft==5) { // limite de 5 fotos na skyhub
						break;
					} 
				}
			}	
		}
		$produto['images'] = $imagens;
		
		// TRATAR VARIANTS		
		if ($prd['has_variants']!="") {
			$variations = array();
            $prd_vars = $this->model_products->getProductVariants($prd['id'],$prd['has_variants']);
            // var_dump($prd_vars);
            $tipos = explode(";",$prd['has_variants']);
            // var_dump($tipos);
            $variation_attributes = array();
			foreach($prd_vars as $value) {
				// var_dump($value);
			  	if (isset($value['sku'])) {
					$apelido = "";
					$specficiation = array();
					$i=0;
					foreach ($tipos as $z => $campo) {
						if ($apelido!="") {
							$apelido .= ";";
							//$SKU .= "-";
						}
						//$SKU .= $value[$campo];
						$specficiation[$i++] = array(
							"key" => $campo,
							"value" => $value[$campo]
						);
						if (!in_array($campo, $variation_attributes)) {
							$variation_attributes[] = $campo;
						}
					}
					$specficiation[$i++] = 
						array(
							"key" => "store_stock_cross_docking",
							"value" => $prd['prazo_operacional_extra'],
						);
					if ($prd['qty'] < 5) {
						$qty_atual = (int)$value['qty'];  // para B2W manda todos se for menor q 5 
					}
					else {
						$qty_atual = (int)$value['qty'] * $estoqueIntTo / 100; 
					}
					$variacao = array(
						"sku" => $skumkt.'-'.$value['variant'],
						"qty" => ceil($qty_atual),
						"ean" => $prd['EAN'],
						"specifications" => $specficiation, 
						"images" => array(),
						);
					$variations[] = $variacao;
				 }	
			}
			$produto['variation_attributes'] =$variation_attributes;
			$produto['variations'] = $variations;
		}
		
		$prod_data = array("product" => $produto);
		$json_data = json_encode($prod_data);
			
		echo "Incluindo o produto ".$prd['id']." ".$prd['name']."\n";
		var_dump($json_data);
		if ($json_data === false) {
			// a descrição está com algum problema . tento reduzir... 
			$produto['name'] = substr(strip_tags(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8")," \t\n\r\0\x0B\xC2\xA0"),0,96);
			$produto['description'] = substr($description,0,3000);
			$prod_data = array("product" => $produto);
			$json_data = json_encode($prod_data);
			var_dump($json_data);
			if ($json_data === false) {
				$msg = "Erro ao fazer o json do produto ".$prd['id']." ".print_r($produto,true);
				echo $msg."\n";
				$this->log_data('batch',$log_name, $msg,"E");
				return false;;
			}
		}
		echo "\n";
	
		$url = 'https://api.skyhub.com.br/products';
		$retorno = $this->postSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
		// var_dump($retorno);
		if (($retorno['httpcode'] == 429) || ($retorno['httpcode'] == 504))  { // estourou o limite
			sleep(60);
			$retorno = $this->postSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
		}		
		
		if ($retorno['httpcode'] != 201) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($prod_data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($prod_data,true),"E");
			
			return false;
		}
		return true;
	
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
	
	function inactiveProducts()
	{
		// verifico os produtos que ficaram 99, provavelmente pois não tem mais transportadoras mas tem tabém os inatviso
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		
		$this->getkeys(1,0);
		$int_date_time = date('Y-m-d H:i:s');
		$sql = "SELECT * FROM prd_to_integration WHERE status = 0 AND status_int = 99 AND int_to=?";
		$query = $this->db->query($sql, array($this->getInt_to()));
		$prds_int = $query->result_array();
		foreach($prds_int as $prd_int) {
			
			echo "Processando produto ".$prd_int['prd_id']."\n";
			$sql = "SELECT * FROM products WHERE id = ?";
			$query = $this->db->query($sql,array($prd_int['prd_id']));
			$prd = $query->row_array();
			
			if (!is_null($prd_int['skubling'])) {
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to=?";
				$query = $this->db->query($sql,array($prd_int['skubling'],$this->getInt_to()));
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
					if ($status_int) {
						if ($this->zeraEstoque($prd_int['skumkt'], $prd_int['prd_id'])) {  // zera o estoque no marketplace
							$sql = "UPDATE bling_ult_envio SET qty = 0, data_ult_envio = ? WHERE id = ?";
							$cmd = $this->db->query($sql,array($int_date_time,$bling['id']));
							$sql = "UPDATE prd_to_integration SET status_int=?, date_last_int = ? WHERE id= ?";
							$cmd = $this->db->query($sql,array($status_int,$int_date_time,$prd_int['id']));
						}; 
					} 
				}
			}
		}

	}

	function atualizaEstoque()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value'].'PERC'; 
			$parms[$key_param] = $parm['value'];
		}	
		$key_param = $this->getInt_to().'PERC'; 
		
		$this->getkeys(1,0);
		$sql = "SELECT * FROM bling_ult_envio WHERE int_to=?";
		$query = $this->db->query($sql, array($this->getInt_to()));
		$blings = $query->result_array();
		
		$encerrar = false;
		foreach($blings as $bling) {
			if ($encerrar) { die; }
			
			$sql = "SELECT * FROM products WHERE id = ?";
			$query = $this->db->query($sql,array($bling['prd_id']));
			$prd = $query->row_array();
			
			$zeraEstoque = false;
			if (($prd['status'] == 1) && ($prd['situacao'] == 2)) { // produto está ativo. 
				$sku = $bling['skubling'];
				$ean = $prd['EAN'];
				if ($prd['is_kit'] == 1) {
					$ean ='IS_KIT'.$prd['id'];
				}elseif ($ean=='') {
					$ean ='NO_EAN'.$prd['id'];
				}else {
					$ean = substr('0000000000'.$prd['EAN'],-13);
				}
				if ($ean != $bling['EAN']) {
					echo "Mudou de EAN: ";
				 	$zeraEstoque = true;
					// $encerrar = true;	
				}elseif (!$this->hasShipCompany($prd)) {
					echo "Sem Logistica: ";
				 	$zeraEstoque = true;
				}else {
					echo "OK: ";
					$zeraEstoque = false;
				}
			}
			else {
				echo "Inativo ou incompleto: ";
			 	$zeraEstoque = true;
			}
			if ($prd['has_variants']!="") {
				$variations = array();
	            $prd_vars = $this->model_products->getProductVariants($prd['id'],$prd['has_variants']);
	            // var_dump($prd_vars);
	            $tipos = explode(";",$prd['has_variants']);
	            // var_dump($tipos);
	            $variation_attributes = array();
				$skumkt= $bling['skubling'];
				foreach($prd_vars as $value) {
					if (isset($value['sku'])) {
						if ($zeraEstoque) {
							$qty = 0; 
						}
						else {
							if ($prd['qty'] < 5) {
								$qty = (int)$value['qty'];  // para B2W manda todos se for menor q 5 
							}
							else {
								$qty = (int)$value['qty'] * $parms[$key_param] / 100; 
							}
							$qty = ceil($qty);
						} 
						
						$sku = $skumkt.'-'.$value['variant'];
						echo "Variação: Estoque id:".$bling['prd_id']." ".$sku." estoque:".$prd['qty']." enviado:".$qty."\n";
						
						$product = Array (
							'variation' => array(
							    "qty" => ceil($qty)
							) 
						);
						$url = 'https://api.skyhub.com.br/variations/'.$sku;
	
						$json_data = json_encode($product);
						$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
						if (($resp['httpcode'] == 429) || ($resp['httpcode'] == 504)) { // estourou o limite
							sleep(60);
							$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
						}
						if ($resp['httpcode']!="204")  {  // created
							echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
							$this->log_data('batch',$log_name, "ERRO ao alterar estoque variação ".$sku." no ".$this->getInt_to()." - httpcode: ".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
						}
					}
				}	

			}
			else {
				$sku = $bling['skubling'];
				$sky_qty = 'SEM';
				$resp = $this->getSkyHubProduct($sku);
				//echo print_r($resp,true);	
				if ($resp) {
					$sky_status = $resp['status'];
					$sky_price = $resp['price'];
					$sky_qty = $resp['qty'];
				}
				if ($zeraEstoque) {
					$qty = 0; 
				}
				else {
					$qty = (int) $prd['qty'] * $parms[$key_param] / 100; 
					$qty = ceil($qty); 
					if ((int) $prd['qty'] < 5) {
					    $qty = (int) $prd['qty'];
					} 
				}
				
				if ($sky_qty != $qty) {
					echo  "************";
				}
				echo "Simples: Estoque id:".$bling['prd_id']." ".$sku." estoque:".$prd['qty']." enviado:".$qty." era:".$sky_qty."\n";
				
				$product = Array (
					'product' => array(
					    "qty" => $qty
					) 
				);
				$url = 'https://api.skyhub.com.br/products/'.$sku;
				
				$json_data = json_encode($product);
				$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
				if (($resp['httpcode'] == 429) || ($resp['httpcode'] == 504)) { // estourou o limite
					sleep(60);
					$resp = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
				}
				if ($resp['httpcode']!="204")  {  // created
					echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
					$this->log_data('batch',$log_name, "ERRO ao alterar estoque ".$sku." no ".$this->getInt_to()." - httpcode: ".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				}
			}	
	
		}
		
	}
	
	function getSkyHubProduct($sku) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://api.skyhub.com.br/products/'.$sku;
		$retorno = $this->getSkyHub($url,$this->getApikey(), $this->getEmail());
		if (($retorno['httpcode'] == 429) || ($retorno['httpcode'] == 504)) {
			sleep(5);
			$retorno = $this->getSkyHub($url,$this->getApikey(), $this->getEmail());
		}  
		if (!($retorno['httpcode']=="200") )  {  
			echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['content'],true)." \n"; 
			//$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['content'],true),"E");
			return false;
		}
		$resposta = json_decode($retorno['content'],true);
		return $resposta;
	}
	
	function getSkyHub($url, $api_key, $login){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}

	function postSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function putSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "PUT",
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function deleteSkyHub($url, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "DELETE",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function errorTransformation($prd_id, $sku, $msg, $prd_to_integration_id = null, $mkt_code = null)
	{
		$this->model_errors_transformation->setStatusResolvedByProductId($prd_id,$this->getInt_to());
		$trans_err = array(
			'prd_id' => $prd_id,
			'skumkt' => $sku,
			'int_to' => $this->getInt_to(),
			'step' => "Preparação para envio",
			'message' => $msg,
			'status' => 0,
			'date_create' => date('Y-m-d H:i:s'), 
			'reset_jason' => '', 
			'mkt_code' => $mkt_code,
		);
		echo "Produto ".$prd_id." skubling ".$sku." int_to ".$this->getInt_to()." ERRO: ".$msg."\n"; 
		$insert = $this->model_errors_transformation->create($trans_err);
		
		if (!is_null($prd_to_integration_id)) {
			$sql = "UPDATE prd_to_integration SET date_last_int = now() WHERE id = ?";
			$cmd = $this->db->query($sql,array($prd_to_integration_id));
		}
	}

}
?>
