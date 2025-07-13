<?php
/*
 
Realiza o Leilão de Produtos e atualiza o CAR 

*/   
 class CarLeilao extends BatchBackground_Controller {
	
	var $int_to='CAR';
	var $apikey='';
	var $site='';
	
	public $zera_estoque = array();
	
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
		$this->load->model('model_products_marketplace');
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
	function setSite($site) {
		$this->site = $site;
	}
	function getSite() {
		return $this->site;
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
		echo "Rotina desativada\n";
		die;
		/* faz o que o job precisa fazer */
		$this->getkeys(1,0);
		//$retorno = $this->promotions();
		//$retorno = $this->campaigns();
		$retorno = $this->fase1();
		$retorno = $this->syncProducts();
		$retorno = $this->syncOfertas();
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
		$this->setSite($api_keys['site']);
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
	
 	function fase1()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// reseto o que já está em cadastramento a mais de 21 dias, algo aconteceu e o carrefour não avisou. 
		$sql = "UPDATE prd_to_integration SET status_int = 99 WHERE int_type = 13 AND int_to='".$this->getInt_to()."' AND (status_int = 20 OR status_int = 22)  AND date_update < date_sub(now(), interval 21 day)";
		$query = $this->db->query($sql);
		
		// Em análise
		$sql = "UPDATE prd_to_integration SET status_int = 99 WHERE int_type = 13 AND int_to='".$this->getInt_to()."' AND status_int != 20 AND status_int != 22 ";
		$query = $this->db->query($sql);
		// Em limpa tabela temporaria
		//$sql = "DELETE FROM int_processing_car WHERE int_to='".$this->getInt_to()."'";
		$sql = "TRUNCATE TABLE int_processing_car";
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
		
		$sql = "SELECT p.id,int_id,prd_id,i.int_to,p.status_int FROM prd_to_integration p, integrations i WHERE p.int_type = 13 AND status = 1 AND status_int=99 AND int_id = i.id AND p.int_to='".$this->getInt_to()."' ORDER BY prd_id";
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
			    // $qty_atual = (int) $prd['qty'];
				$qty_atual = 0; // será zero se for diferente da B2W
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
				}
				else {
					$ean = substr('0000000000'.$prd['EAN'],-13);
				}
				// $sells = $this->model_orders->getSellsOrdersCount($prd['store_id'],$date30->format('Y-m-d'));
				
				// pego o preços do marketplace ou a promação do produto
				$preco = $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
				$preco = $this->model_promotions->getPriceProduct($val['prd_id'],$preco,"CAR");
				//$preco = $this->model_promotions->getPriceProduct($val['prd_id'],$preco);
			//	$preco = $this->model_campaigns->getPriceProduct($prd['id'],$preco,$this->getInt_to());
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
	        		'reputacao' => '100',  
	        		'NVL' => '0',
	        		'to_int_id' => $val['id'],
	        		'company_id' => $prd['company_id'],
	        		'store_id' => $prd['store_id'],
	        		'uf' => $uf,
	        		'sells' => $sells[$prd['store_id']],
	        		);
				$insert = $this->db->insert('int_processing_car', $data);
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

		$sql = "SELECT * FROM int_processing_car WHERE int_to='".$this->getInt_to()."' ORDER BY EAN ASC, CAST(price AS DECIMAL(12,2)) ASC, uf DESC, sells DESC, store_id ASC";
		//$sql = "SELECT * FROM int_processing_car WHERE int_to='".$this->getInt_to()."' ORDER BY int_to ASC, EAN ASC, NVL DESC, company_id ASC";
		$query = $this->db->query($sql);
		$mkt = $query->result_array();
		$int_ant = "";
		$ean_ant = "";
		$price = 0;
		$qty = 0;
		$alterados = array();
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
				echo "PERDEU...\n";
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
				echo "EXISTE 1 ENVIADO...\n";
				$old = $cmd->row_array();
				// Se é o mesmo produto, mesmo valor e quantidade, não precisa reenviar 
				if (($old['prd_id']==$val['prd_id']) && ($old['price']==$val['price']) && ($old['qty']==$val['qty'])){
					echo "EH O MESMO... \n";
					///$status_int = 2; Carrefour deve sempre enviar o preço e estoque
					
				}
				if ($old['prd_id'] != $ganhador)  {
					// mudou o ganhador 	
					echo "Tem que abrir chamado para alterar os dados do produto...".$old['prd_id']." ".$old['skubling']." ".$this->getInt_to()."\n";
					$alterados[$ganhador]= array(
						'prd_id'   => $ganhador,
						'skubling' => $old['skubling']
					);
				}
				else {
					//verifico se mudou algo que precise de chamado no Carrefour 
					// tem que ver quais atributos querem mandar para o Carrefour.  
				}
				$skubling = $old['skubling'];
			} else {
				Echo "Produto novo ".$val['prd_id']."\n";  
				//$skubling = strtoupper(substr(md5(uniqid(mt_rand(9999999,9999999999), true)), 0, 13));
				if ((substr($val['EAN'],0,6)=='IS_KIT') OR (substr($val['EAN'],0,6)=='NO_EAN')) {
					$skubling = "P".$val['prd_id']."S".$val['store_id'].$this->getInt_to();  // sem ean
				}
				else {
					$skubling = $val['EAN'].$this->getInt_to();   // com ean
				}
			    
				// verifico se já tem algum sendo cadastrado ou que vai cadastrar
				$sql = "SELECT * FROM prd_to_integration WHERE (status_int=22 OR status_int=20) AND  
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
					
			// Atualiza status_int
			$sql = "UPDATE prd_to_integration SET status_int = ".$status_int. ", skubling = '".$skubling."' , skumkt = '".$skubling."' WHERE id = ".$val['to_int_id'];
			$query = $this->db->query($sql);
		
		}
		
		$not_int_to = " AND int_to = 'CAR' ";  // Só trago a B2W 
		// $not_int_to = "";
		
		// vejo os produtos que mudaram de EAN e derrubo eles do marketplace
		$sql = "SELECT * FROM bling_ult_envio b WHERE qty>=0 ".$not_int_to." AND substr(EAN,1,6) !='IS_KIT'";
		$query = $this->db->query($sql);
		$prods_derr = $query->result_array();
		foreach ($prods_derr as $prd_derr) {
			$sql = "SELECT * FROM products WHERE id = ".$prd_derr['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			if ($prd['EAN'] != $prd_derr['EAN']) {
				if (($prd['EAN'] != '') OR (substr($prd_derr['EAN'],0,6)!='NO_EAN')) {
					echo "prd_id = ".$prd_derr['prd_id']." mudou de EAN e será derrubado\n";
					$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE id = ".$prd_derr['id'];
					$cmd = $this->db->query($sql);
				}
			}
		}

		// Crio o arquivo CSV para os produtos que mudaram para abrir chamado. 
		if (count($alterados)>0) {
			$this->productsChanged($alterados);
		}
		
		return; 

	}
	
    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$company_id = 1; // somente da conecta-la
		$store_id = 0;

		echo "Processando produtos que precisam de cadastro no ".$this->getInt_to()." \n";
        $sql = "SELECT * FROM prd_to_integration WHERE status_int=20 AND int_type=13 AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		if (count($data) > 0) {
			$table_carga = "carrefour_carga_produtos_".$store_id;
			if ($this->db->table_exists($table_carga) ) {
				$this->db->query("TRUNCATE $table_carga");
			} else {
				$model_table = "carrefour_carga_produtos_model";
				$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
			}
			$file = '0';
			
		} 
		else {
			echo "Nenhum produto novo \n";
			return true;
		}
		
		$file_prod = $this->criarCsv($data,$table_carga, $company_id, $store_id, $file, 21); //Status_int =21 para faciliar a troca de todos se der certo ou errado
		
		if (!$file_prod) {
			return ;
		}
		
		$url = 'https://'.$this->getSite().'/api/products/imports';
		
		$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod);
		if ($retorno['httpcode'] != 201) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true),"E");
			
			$sql = "UPDATE prd_to_integration SET status_int=20, date_last_int = now() WHERE status_int=21 AND int_type=13 AND int_to='".$this->getInt_to()."'";
			$cmd = $this->db->query($sql);	
			return false;
		}
		//var_dump($retorno['content']);
		$resp = json_decode($retorno['content'],true);
		$import_id= $resp['import_id'];
		
		While(true) {
			sleep(10);
			$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id;
			echo "chamando ".$url." \n";
			
			$restorno_get = $this->getCarrefour($url,$this->getApikey());
			if ($restorno_get['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
				
				$sql = "UPDATE prd_to_integration SET status_int=20, date_last_int = now() WHERE status_int=21 AND int_type=13 AND int_to='".$this->getInt_to()."'";
				$cmd = $this->db->query($sql);	
				return false;
			}
			$resp = json_decode($restorno_get['content'],true);
			//var_dump($restorno_get['content']);
			var_dump($resp);
			if ($resp['import_status'] == "SENT") {
				break;
			}
		}
		$log_import = array(
			'company_id'=> $company_id,
			'store_id' => $store_id,
			'file' => $file_prod,
			'status' => 0,
			'date_created' => $resp['date_created'],
			'has_error_report' => $resp['has_error_report'],
			'has_new_product_report' => $resp['has_new_product_report'],
			'has_transformation_error_report' => $resp['has_transformation_error_report'],
			'has_transformed_file' => $resp['has_transformed_file'],
			'import_id' => $resp['import_id'],
			'import_status' => $resp['import_status'],
			'transform_lines_in_error' => $resp['transform_lines_in_error'],
			'transform_lines_in_success' => $resp['transform_lines_in_success'],
			'transform_lines_read' => $resp['transform_lines_read'],
			'transform_lines_with_warning' => $resp['transform_lines_with_warning'],
		);
		$insert = $this->db->insert('carrefour_cargas_import_log', $log_import);
		$sql = "UPDATE prd_to_integration SET status_int=22, date_last_int = now() WHERE status_int=21  AND int_type=13 AND int_to='".$this->getInt_to()."'";;
		$cmd = $this->db->query($sql);	
		
	}	
	
	function criarCsv($data, $table_carga, $company_id, $store_id, $file, $status_int) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		foreach ($data as $key => $row) 
	    {
	    	echo "Processando ".$row['prd_id']." \n";
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
				$pathImage = 'catalog_product_image';
			}
			else {
				$pathImage = 'product_image';
			}
			
    		$sku = $row['skubling'];
			
			$prd['description'] = trim(preg_replace('/\s+/', ' ', $prd['description']));
			//$prd['description'] = htmlspecialchars(strip_tags($prd['description']), ENT_QUOTES, "utf-8"); 
			$prd['description'] = str_replace("</p>", '<br>', $prd['description']);
			$prd['description'] = strip_tags($prd['description'],"<b><br><i>"); 
			$prd['description'] = str_replace('"', ' ', $prd['description']);
			$prd['description'] = str_replace('\'', ' ', $prd['description']);
			$prd['description'] = str_replace("&nbsp;", ' ', $prd['description']);

			// seller-atributte
			$seller_atributte = '';
			$brand_id = json_decode($prd['brand_id']);
			if ($brand_id) {
				$sql = "SELECT * FROM brands WHERE id = ?";
				$query = $this->db->query($sql, $brand_id);
				$brand = $query->row_array();
				$seller_atributte =  "Fabricante:".$brand['name'];
			}
			
			// vejo se tem campos customizados				
			$this->load->model('model_atributos_categorias_marketplaces', 'myatributoscategorias');	
			$produtos_atributos = $this->myatributoscategorias->getAllProdutosAtributos($row['id']);
			foreach ($produtos_atributos as $produto_atributo) {
				$id_atributo =  $produto_atributo['id_atributo']; 
				$valor = $produto_atributo['valor'];
				$atributo = $this->myatributoscategorias->getAtributo($id_atributo);
				if ($atributo['tipo']=='list') {
					$valores = json_decode($atributo['valor'],true );
					foreach ($valores as $valId) {					
						if ($valId['id'] == $produto_atributo['valor']) {
							$valor = $valId['name'];
						}
					}
					
				}
				$seller_atributte .= "|".$atributo['nome'].":".$valor;
			} 
			
			$lin_prd = array(
				'category_code' => "seller-category",
				'product_sku' => $sku,
				'sku' => $sku, 
				'product_title' => $prd['name'],
				'weight' => number_format($prd['peso_bruto'] * 1000,2,".",""),
				'height' => ($prd['altura'] < 2) ? 2 : $prd['altura'],
				'width' => ($prd['largura'] < 11) ? 11 : $prd['largura'],
				'depth' => ($prd['profundidade'] < 16) ? 16 : $prd['profundidade'],
				'variantImage1' => '',
				'variantImage2' => '',
				'variantImage3' => '',
				'variantImage4' => '',
				'variantImage5' => '',
				'variant_key' => '',
				'variant_code' => '',
				'variant_color' => '',
				'variant_second_color' => '',
				'variant_size' => '',
				'variant_voltage' => '',				
				'ean' => $prd['EAN'],
				'description' => $prd['description'],
				'seller_atributte' => $seller_atributte,
			);
			if ($prd['image']!="") {
				$numft = 1;
				if (strpos("..".$prd['image'],"http")>0) {
					$fotos = explode(",", $prd['image']);	
					foreach($fotos as $foto) {
						$lin_prd['variantImage'.$numft++] = $foto;
						if ($numft==5) { // limite de 5 fotos na skyhub
							break;
						} 
					}
				} else {
					$fotos = scandir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image']);	
					foreach($fotos as $foto) {
						if (($foto!=".") && ($foto!="..")) {
							if(!is_dir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image'].'/'.$foto)) {
								$lin_prd['variantImage'.$numft++] = base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $foto);
							}
						}
						if ($numft==5) { // limite de 5 fotos no carrefour 
							break;
						} 
					}
				}	
			}

			if ($prd['has_variants']=="") {
				
				$sql = "SELECT * FROM ".$table_carga." WHERE product_sku = ?";
				$cmd = $this->db->query($sql,array($lin_prd['product_sku']));
				$exist = $cmd->row_array();
				// $exist = $this->db->get_where($table_carga, array('product_sku'=>$lin_prd['product_sku']))->result();  // vejo se já inseri para evitar duplicados
				if (!$exist) {
					$insert = $this->db->insert($table_carga, $lin_prd);
				}
				// $insert = $this->db->insert($table_carga, $lin_prd);
			}
			else {
				echo "Tem Variant".$row['prd_id']."\n";
				$prd_vars = $this->model_products->getVariants($row['prd_id']);
				$tipos = explode(";",$prd['has_variants']);
				
				foreach($prd_vars as $prd_var) {
					$lin_prd['product_sku']= $sku."-".$prd_var['variant'];	// product-sku
					$valores = explode(";",$prd_var['name']);
					//var_dump($valores);
					$ind = array_search('Cor',$tipos);
					if ($ind !== false) {
						$lin_prd['variant_color']= $valores[$ind];   		// variant-color
					} else {
						$lin_prd['variant_color']= '';   					// variant-color
					}
					$ind = array_search('TAMANHO',$tipos);
					if ($ind !== false) {
						$lin_prd['variant_size']= $valores[$ind];   		// variant-size
					} else {
						$lin_prd['variant_size']= '';   					// variant-size
					}
					$ind = array_search('VOLTAGEM',$tipos);
					if ($ind !== false) {
						$lin_prd['variant_voltage']= $valores[$ind];   		// variant-voltage
					} else {
						$lin_prd['variant_voltage']= '';   					// variant-voltage
					}
					echo "processei ".$lin_prd['product_sku']."\n";
					
					$sql = "SELECT * FROM ".$table_carga." WHERE product_sku = ?";
					$cmd = $this->db->query($sql,array($lin_prd['product_sku']));
					$exist = $cmd->row_array();
					// $exist = $this->db->get_where($table_carga, array('product_sku'=>$lin_prd['product_sku']))->result();  // vejo se já inseri para evitar duplicados
					if (!$exist) {
						$insert = $this->db->insert($table_carga, $lin_prd);
					}
					//$insert = $this->db->insert($table_carga, $lin_prd);
				}
			}
			$sql = "UPDATE prd_to_integration SET status_int=".$status_int." WHERE id = ".$row['id'];
			$cmd = $this->db->query($sql);
		}	
		
		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_PRODUTOS_".$file.".csv";
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 

		$sql = "SELECT * FROM ".$table_carga;
		$query = $this->db->query($sql);
		$products = $query->result_array();
		if (count($products)) {
			$myfile = fopen($file_prod, "w") or die("Unable to open file!");
			$header = array('category-code','product-sku','sku','product-title','weight','height','width','depth',
								'variantImage1','variantImage2','variantImage3','variantImage4','variantImage5',
								'variant-key','variant-code','variant-color','variant-second-color',
		        				'variant-size','variant-voltage','ean','description','seller-atributte');
			fputcsv($myfile, $header, ";");
			foreach($products as $prdcsv) {
				fputcsv($myfile, $prdcsv, ";");
			}
			fclose($myfile);
			
			return $file_prod;
		}
		else {
			return false;
		}
	}	

	function productsChanged($products_id) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$company_id = 1; // somente da conecta-la
		$store_id = 0;
		
		$table_carga = "carrefour_carga_produtos_".$store_id."_".date('YmdHis');
		if ($this->db->table_exists($table_carga) ) {
			$this->db->query("TRUNCATE $table_carga");
		} else {
			$model_table = "carrefour_carga_produtos_model";
			$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
		}
		$file = "CHAMADOS_".$store_id."_".date('YmdHis');
		
		echo "Processando produtos que já estavam cadastrados e que precisam se alterados por chamado no ".$this->getInt_to()." \n";
		$prd_to_int = array();
		foreach($products_id as $prod) {
			$sql = "SELECT * FROM prd_to_integration WHERE int_type=13 AND int_to='".$this->getInt_to()."' AND prd_id=".$prod['prd_id'];
			$query = $this->db->query($sql);
			$data = $query->result_array();	
			foreach ($data as $rec) {
				$prd_to_int[] = $rec;
			}
		}
		
		$file_prod = $this->criarCsv($prd_to_int,$table_carga, $company_id, $store_id, $file, 1);  // status_int =1 pois são os ganhadores 
		$this->db->query("DROP TABLE $table_carga");
		if (!$file_prod) {
			return;
		}
		
		foreach($products_id as $prod) {
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$this->getInt_to()."' AND skubling='".$prod['skubling']."'";
			$query = $this->db->query($sql);
			$bling_ult_envio = $query->row_array();	
			
			$sql = "SELECT * FROM int_processing_car WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$prod['prd_id'];
			$cmd = $this->db->query($sql);
			$int_proc = $cmd->row_array();
			
			$sql = "SELECT * FROM products WHERE id = ".$prod['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();

			$cat_id = json_decode ( $prd['category_id']);
			$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
					 WHERE id =".intval($cat_id[0]).")";
			$cmd = $this->db->query($sql);
			$tipo_volume_codigo = $cmd->row_array();
			$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
			$loja  = $this->model_stores->getStoresData($prd['store_id']);
			
			$bling = array(
				'id' => $bling_ult_envio['id'],
				'int_to' => $this->getInt_to(),
				'company_id' => $int_proc['company_id'],
				'EAN'=> $bling_ult_envio['EAN'],
				'prd_id'=> $prod['prd_id'], 
				'price'=> $int_proc['price'],
				'qty'=> $int_proc['qty'],
				'sku'=> $int_proc['sku'],
				'reputacao'=> $int_proc['reputacao'],
				'NVL'=> $int_proc['NVL'],
				'mkt_store_id'=> 0,
				'data_ult_envio'=> date('Y-m-d H:i:s'),
				'skubling'=> $prod['skubling'],
				'skumkt'=> $prod['skubling'],
				'tipo_volume_codigo'=> $tipo_volume_codigo['codigo'],
				'qty_atual'=> $int_proc['qty_atual'],
				'largura'=> $prd['largura'],
				'altura'=> $prd['altura'],
				'profundidade'=>$prd['profundidade'],
				'peso_bruto'=>$prd['peso_bruto'],
				'store_id'=> $int_proc['store_id'],
				'marca_int_bling'=> null,
				'categoria_bling'=> null,
				'crossdocking' => $crossdocking, 
				'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        	'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        	'freight_seller' =>  $loja['freight_seller'],
				'freight_seller_end_point' => $loja['freight_seller_end_point'],
				'freight_seller_type' => $loja['freight_seller_type'],
			);
			$insert = $this->db->replace('bling_ult_envio', $bling);
		}

	}
		
	function syncOfertas() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$company_id = 1; // somente da conecta-la
		$store_id = 0;
		
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
		
		$sql = "SELECT * FROM prd_to_integration WHERE status_int=1 AND status=1 AND int_type=13 AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		if (count($data) > 0) {
			$table_carga = "carrefour_carga_ofertas_".$store_id;
			if ($this->db->table_exists($table_carga) ) {
				$this->db->query("TRUNCATE $table_carga");
			} else {
				$model_table = "carrefour_carga_ofertas_model";
				$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
			}
		} 
		else {
			echo "Nenhum produto novo \n";
			return true;
		}
		
		$ofertas_bling = array();
		foreach ($data as $key => $row) 
	    {
	    	$sql = "SELECT * FROM int_processing_car WHERE int_to = '".$this->getInt_to()."' AND prd_id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$int_proc = $cmd->row_array();
			
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
    		$sku = $row['skubling'];
    		// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			echo " OFERTA ".$prd['id']." sku ".$sku." price ".$int_proc['price']." qty ".$int_proc['qty_atual']."\n"; 
    		$oferta = array(
    			'sku' => $sku,
    			'product_id' => $sku,
    			'product_id_type' => "SHOP_SKU",
    			'description' => '',
    			'internal_description' => '',
    			'price' => $int_proc['price'], 
    			'quantity' => $int_proc['qty_atual'],
    			'state' => '11',
    			'update-delete' => 'update'
    		);
			
			$ofertas_bling[] = array(
				'skubling' => $sku, 
				'price' => $int_proc['price'],
				'qty' => $int_proc['qty'], 
				'qty_atual' => $int_proc['qty_atual'],
			);
			if ($prd['has_variants']=="") {
				//$exist = $this->db->get_where($table_carga, array('sku'=>$oferta['sku']))->result();  // vejo se já inseri para evitar duplicados
				$sql = "SELECT * FROM ".$table_carga." WHERE sku = ?";
				$cmd = $this->db->query($sql,array($oferta['sku']));
				$exist = $cmd->row_array();
				if (!$exist) {
					$insert = $this->db->insert($table_carga, $oferta);
				}
				// $insert = $this->db->insert($table_carga, $oferta);
			}
			else {
				echo "TEM variant \n";
				$prd_vars = $this->model_products->getVariants($row['prd_id']);
				$tipos = explode(";",$prd['has_variants']);
				//var_dump($tipos);
				foreach($prd_vars as $prd_var) {
	
					$oferta['sku'] = $sku."-".$prd_var['variant'];				//sku
					$oferta['product_id'] = $sku."-".$prd_var['variant'];			//product-id
					$qty_atual = (int) $prd_var['qty'] * $estoqueIntTo[$this->getInt_to()] / 100; 
					$oferta['quantity']= ceil($qty_atual);		//quantity
					
					//$exist = $this->db->get_where($table_carga, array('sku'=>$oferta['sku']))->result();  // vejo se já inseri para evitar duplicados
					$sql = "SELECT * FROM ".$table_carga." WHERE sku = ?";
					$cmd = $this->db->query($sql,array($oferta['sku']));
					$exist = $cmd->row_array();
					if (!$exist) {
						$insert = $this->db->insert($table_carga, $oferta);
					}
					//$insert = $this->db->insert($table_carga, $oferta);
					
					echo " Variant ".$prd['id']." sku ".$sku."-".$prd_var['variant']." price ".$int_proc['price']." qty ".ceil((int) $prd['qty'] * $estoqueIntTo[$this->getInt_to()] / 100)."\n"; 
    		
				}
			}
		}
		
		// Zero os inativos ou incompletos ou que estejam no bling com estoque zerado. 
		$sql = 'SELECT b.*, p.status, p.situacao, p.has_variants FROM bling_ult_envio b, products p 
					WHERE b.prd_id=p.id AND (p.status!=1 OR p.situacao=1) AND b.int_to="'.$this->getInt_to().'"';
		$cmd = $this->db->query($sql);
		$data = $cmd->result_array();
		foreach ($data as $row) 
	    {
	    	$sku = $row['skubling'];
			echo " Zerando ".$row['prd_id']." sku ".$sku." price ".$row['price']." qty = 0 \n"; 
    		$oferta = array(
    			'sku' => $sku,
    			'product_id' => $sku,
    			'product_id_type' => "SHOP_SKU",
    			'description' => '',
    			'internal_description' => '',
    			'price' => $row['price'], 
    			'quantity' => 0,
    			'state' => '11',
    			'update-delete' => 'update'
    		);
			$ofertas_bling[] = array(
				'skubling' => $sku, 
				'price' => $row['price'],
				'qty' => 0, 
				'qty_atual' => 0,
			);
			if ($row['has_variants']=="") {
				$insert = $this->db->insert($table_carga, $oferta);
			}
			else {
				$prd_vars = $this->model_products->getVariants($row['prd_id']);
				$tipos = explode(";",$prd['has_variants']);
				//var_dump($tipos);
				foreach($prd_vars as $prd_var) {
					$oferta['sku'] = $sku."-".$prd_var['variant'];				//sku
					$oferta['product_id'] = $sku."-".$prd_var['variant'];			//product-id
					$oferta['quantity']= 0;		//quantity			
					$insert = $this->db->insert($table_carga, $oferta);				
					echo " Variant ".$row['prd_id']." sku ".$sku."-".$prd_var['variant']." price ".$row['price']." qty =0 \n"; 
				}
			}
		}


		
		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_".$store_id.".csv";
		
		$sql = "SELECT * FROM ".$table_carga;
		$query = $this->db->query($sql);
		$products = $query->result_array();
		if (count($products)) {
			$myfile = fopen($file_prod, "w") or die("Unable to open file!");
			$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
							'state','update-delete'); 
		
			fputcsv($myfile, $header, ";");
			foreach($products as $prdcsv) {
				fputcsv($myfile, $prdcsv, ";");
			}
			fclose($myfile);
			
			$url = 'https://'.$this->getSite().'/api/offers/imports';
			echo "chamando ".$url." \n";
			echo "file: ". $file_prod."\n";
			
			$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
			if ($retorno['httpcode'] == 429) {
				sleep(120);
				$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
			}
			if ($retorno['httpcode'] != 201) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true),"E");
				
				$sql = "UPDATE prd_to_integration SET status_int=20 WHERE status_int=21 AND int_type=13 AND int_to='".$this->getInt_to()."'";
				$cmd = $this->db->query($sql);	
				return false;
			}
			//var_dump($retorno['content']);
			$resp = json_decode($retorno['content'],true);
			$import_id= $resp['import_id'];

			While(true) {
				sleep(10);
				$url = 'https://'.$this->getSite().'/api/offers/imports/'.$import_id;
				echo "chamando ".$url." \n";
				$restorno_get = $this->getCarrefour($url,$this->getApikey());
				if ($restorno_get['httpcode'] == 429) {
					sleep(120);
					$restorno_get = $this->getCarrefour($url,$this->getApikey());
				}
				if ($restorno_get['httpcode'] != 200) {
					echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
					echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
					
					$sql = "UPDATE prd_to_integration SET status_int=20 WHERE status_int=21 AND int_type=13 AND int_to='".$this->getInt_to()."'";
					$cmd = $this->db->query($sql);	
					return false;
				}
				$resp = json_decode($restorno_get['content'],true);
				//var_dump($restorno_get['content']);
				if (($resp['status'] == "SENT") || ($resp['status'] == "COMPLETE") ){
					break;
				}
			}
			$log_import = array(
				'company_id'=> $company_id,
				'store_id' => $store_id,
				'file' => $file_prod,
				'status' => 0,
				'date_created' => $resp['date_created'],				
				'has_error_report' => $resp['has_error_report'],
				'import_id' => $resp['import_id'],
				'lines_in_error' => $resp['lines_in_error'],
				'lines_in_pending' => $resp['lines_in_pending'],
				'lines_in_success' => $resp['lines_in_success'],
				'lines_read' => $resp['lines_read'],
				'mode' => $resp['mode'],
				'offer_deleted' => $resp['offer_deleted'],
				'offer_inserted' => $resp['offer_inserted'],
				'offer_updated' => $resp['offer_updated'],
				'import_status' => $resp['status'],
			);
			$insert = $this->db->insert('carrefour_ofertas_import_log', $log_import);
			$sql = "UPDATE prd_to_integration SET status_int=2 WHERE status_int=1 AND status=1 AND int_type=13 AND int_to='".$this->getInt_to()."'";;
			$cmd = $this->db->query($sql);
			
			// atualizo o Bling_ult_envio considerando que deve ter passado tudo ok. Se der erro, zero no CarProductsStatus
			foreach($ofertas_bling as $oferta_bling) {
				$sql = "UPDATE bling_ult_envio SET price = '".$oferta_bling['price']."', qty = '".$oferta_bling['qty']."', 
								qty_atual = '".$oferta_bling['qty_atual']."', data_ult_envio = NOW() 
						WHERE int_to = '".$this->getInt_to()."' AND skubling = '".$oferta_bling['skubling']."'";
				$cmd = $this->db->query($sql);
			}

		}

	}
	function apagueme() 
	{
		 return true;
		 while (true){		
			// TROUXE PRA DENTRO DO SUCESSO
			$int_date = date('Y-m-d H:i:s');
			$sql = "UPDATE prd_to_integration SET status_int=2 , date_last_int = '".$int_date."' WHERE id = ".$row['id'];
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
			
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$row['prd_id'];
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
			
        	$data = array(
        		'int_to' => $row_int_pro['int_to'],
        		'company_id' => $row_int_pro['company_id'],
        		'EAN' => $row_int_pro['EAN'],
        		'prd_id' => $row_int_pro['prd_id'],
        		'price' => $row_int_pro['price'],
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
				
        	);
			$insert = $this->db->replace('bling_ult_envio', $data);
	    }

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
		// verifico os produtos que ficaram 99, provavelmente pois não tem mais transportadoras mas tem tabém os inativos
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->getkeys(1,0);
		
		$sql = "SELECT * FROM prd_to_integration WHERE status = 0 AND status_int = 99 AND int_to=?";
		$query = $this->db->query($sql, array($this->getInt_to()));
		$prds_int = $query->result_array();
		$store_id =0;
		$company_id =1;
		if (count($prds_int) > 0) {
			$table_carga = "carrefour_carga_ofertas_status_99_".$store_id;
			if ($this->db->table_exists($table_carga) ) {
				$this->db->query("TRUNCATE $table_carga");
			} else {
				$model_table = "carrefour_carga_ofertas_model";
				$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
			}
		} 
		else {
			return true;
		}
		
		$ofertas_bling = array();
		$ofertas_int = array();
		$int_date_time = date('Y-m-d H:i:s');
		
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
						$sku = $prd_int['skubling'];
						echo " Zerando ".$prd_int['prd_id']." sku ".$sku." price ".$prd['price']." qty = 0 \n"; 
			    		$oferta = array(
			    			'sku' => $sku,
			    			'product_id' => $sku,
			    			'product_id_type' => "SHOP_SKU",
			    			'description' => '',
			    			'internal_description' => '',
			    			'price' => $prd['price'], 
			    			'quantity' => 0,
			    			'state' => '11',
			    			'update-delete' => 'update'
			    		);
						$ofertas_bling[] = array(
							'id_bling' => $bling['id'], 
							'id_int' => $prd_int['id'], 
							'status_int' => $status_int, 
						);
						if ($prd['has_variants']=="") {
							$insert = $this->db->insert($table_carga, $oferta);
						}
						else {
							$prd_vars = $this->model_products->getVariants($prd_int['prd_id']);
							$tipos = explode(";",$prd['has_variants']);
							//var_dump($tipos);
							foreach($prd_vars as $prd_var) {
								$oferta['sku'] = $sku."-".$prd_var['variant'];				//sku
								$oferta['product_id'] = $sku."-".$prd_var['variant'];			//product-id
								$oferta['quantity']= 0;		//quantity			
								$insert = $this->db->insert($table_carga, $oferta);				
								// echo " Variant ".$prd_int['prd_id']." sku ".$sku."-".$prd_var['variant']." price ".$prd['price']." qty =0 \n"; 
							}
						}
					} 
				}
			}
		}

		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_STATUS_99_".$store_id.".csv";
		
		$sql = "SELECT * FROM ".$table_carga;
		$query = $this->db->query($sql);
		$products = $query->result_array();
		if (count($products)) {
			$myfile = fopen($file_prod, "w") or die("Unable to open file!");
			$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
							'state','update-delete'); 
		
			fputcsv($myfile, $header, ";");
			foreach($products as $prdcsv) {
				fputcsv($myfile, $prdcsv, ";");
			}
			fclose($myfile);
			
			$url = 'https://'.$this->getSite().'/api/offers/imports';
			echo "chamando ".$url." \n";
			echo "file: ". $file_prod."\n";
			
			$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
			if ($retorno['httpcode'] == 429) {
				sleep(120);
				$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
			}
			if ($retorno['httpcode'] != 201) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true),"E");
				
				return false;
			}
			//var_dump($retorno['content']);
			$resp = json_decode($retorno['content'],true);
			$import_id= $resp['import_id'];

			While(true) {
				sleep(10);
				$url = 'https://'.$this->getSite().'/api/offers/imports/'.$import_id;
				echo "chamando ".$url." \n";
				$restorno_get = $this->getCarrefour($url,$this->getApikey());
				if ($restorno_get['httpcode'] == 429) {
					sleep(120);
					$restorno_get = $this->getCarrefour($url,$this->getApikey());
				}
				if ($restorno_get['httpcode'] != 200) {
					echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
					echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
					
					return false;
				}
				$resp = json_decode($restorno_get['content'],true);
				//var_dump($restorno_get['content']);
				if (($resp['status'] == "SENT") || ($resp['status'] == "COMPLETE") ){
					break;
				}
			}
			$log_import = array(
				'company_id'=> $company_id,
				'store_id' => $store_id,
				'file' => $file_prod,
				'status' => 0,
				'date_created' => $resp['date_created'],				
				'has_error_report' => $resp['has_error_report'],
				'import_id' => $resp['import_id'],
				'lines_in_error' => $resp['lines_in_error'],
				'lines_in_pending' => $resp['lines_in_pending'],
				'lines_in_success' => $resp['lines_in_success'],
				'lines_read' => $resp['lines_read'],
				'mode' => $resp['mode'],
				'offer_deleted' => $resp['offer_deleted'],
				'offer_inserted' => $resp['offer_inserted'],
				'offer_updated' => $resp['offer_updated'],
				'import_status' => $resp['status'],
			);
			$insert = $this->db->insert('carrefour_ofertas_import_log', $log_import);

			// atualizo o Bling_ult_envio  e prd_to_integration considerando que deve ter passado tudo ok. Se der erro, zero no CarProductsStatus
			foreach($ofertas_bling as $oferta_bling) {
				
				$sql = "UPDATE bling_ult_envio SET qty = 0, data_ult_envio = ? WHERE id = ?";
				$cmd = $this->db->query($sql,array($int_date_time,$oferta_bling['id_bling']));
				$sql = "UPDATE prd_to_integration SET status_int=?, date_last_int = ? WHERE id= ?";
				$cmd = $this->db->query($sql,array($oferta_bling['status_int'],$int_date_time, $oferta_bling['id_int']));

			}

		}


	}
	
	function getCarrefour($url, $api_key){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "GET",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json',
				'Authorization: '.$api_key,
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

	function postCarrefourFile($url,$api_key,$file, $import_mode = ''){
		$options = array(
		  	CURLOPT_RETURNTRANSFER => true,
		  	CURLOPT_ENCODING => "",
		  	CURLOPT_MAXREDIRS => 10,
		  	CURLOPT_TIMEOUT => 0,
		  	CURLOPT_FOLLOWLOCATION => true,
		  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  	CURLOPT_CUSTOMREQUEST => "POST",
		  	CURLOPT_POSTFIELDS => array('file'=> new CURLFILE($file)),
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json',
				'content-type: multipart/form-data', 
				'Authorization: '.$api_key,
				)
	    );
		if ($import_mode != '') {
			$options[CURLOPT_POSTFIELDS] = array('file'=> new CURLFILE($file),'import_mode' => $import_mode );
		}
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

}
?>
