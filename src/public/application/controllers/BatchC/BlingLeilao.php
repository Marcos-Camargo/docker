<?php
/*
 
Realiza o Leilão de Produtos e atualiza o Bling 

*/   
 class BlingLeilao extends BatchBackground_Controller {
		
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
		$this->load->model('model_blingultenvio');
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
		
		/* faz o que o job precisa fazer */
		$retorno = $this->promotions();
		$retorno = $this->campaigns();
		/* não estamos mais executando o Leilao
		$retorno = $this->toBlingFase1();
		$retorno = $this->toBlingFase2();
		$retorno = $this->syncProducts();
		*/
		  
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
	
	function naoFezIntegracaoML(){
		$sql = "SELECT * FROM bling_ult_envio WHERE (int_to='ML' AND (skubling = skumkt OR skumkt ='00')) OR (int_to='MAGALU' AND (skubling = skumkt OR skumkt ='00'))";
		$cmd = $this->db->query($sql);
		 return(0);
		return ($cmd->num_rows());  
	}
	
    function toBlingFase1()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
    	if ($this->naoFezIntegracaoML()) {
    		$this->log_data('batch',$log_name,"Não pode executar pois necessita que seja feito a integração dos produtos no BLING com o mercado livre","E");
    		echo "Tem que fazer integração ML no Bling e executar o BlingMktSku run\n";
    		return;
    	}
		
		echo 'Iniciando fase 1'."\n";
		// Em análise
		$not_int_to_p = " AND (int_to = 'VIA' OR int_to = 'ML')  ";  // Não trago mais a B2W e CAR 
		$not_int_to_p = " AND (int_to = 'VIA')  ";  // Agora Só VIA
		$sql = "UPDATE prd_to_integration SET status_int = 99 WHERE int_type = 13".$not_int_to_p;
		//$query = $this->db->query($sql);
		// Em limpa tabela temporaria
		$sql = "TRUNCATE TABLE int_processing";
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
		
		$not_int_to_p = " AND (p.int_to = 'ML' OR p.int_to = 'VIA')  ";  // Não trago mais a B2W e CAR 
		$not_int_to_p = " AND ( p.int_to = 'VIA')  ";  // AGORA Só VIA
		// $not_int_to_p = "";
		
		$sql = "select p.id,int_id,prd_id,i.int_to from prd_to_integration p, integrations i where p.int_type = 13 AND status = 1 and int_id = i.id ".$not_int_to_p." order by prd_id";
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
			}
			$key_param = $val['int_to'].'PERC'; 
			
			$qty_atual = (int) $prd['qty'] * $parms[$key_param] / 100; 
			$qty_atual = ceil($qty_atual); // arrendoda para cima  
			if ((int) $prd['qty'] < 5) {
				if ($val['int_to'] == 'B2W') {
					$qty_atual = (int) $prd['qty'];
				}
				else {
					$qty_atual = 0;
				}
			}
			//echo 'mkt= '.$val['int_to'].' qty='.$prd['qty'].' Nova quantidade = '. $qty_atual. ' perc ='.$parms[$key_param].'/n';
		//	if ($qty_atual<$parms[$val['int_to']]) { // Não tem mais estoque mínimo por marketplace por enquanto
			if ($qty_atual==0) { 
			//if (($qty_atual==0) || ((int) $prd['qty'] < 5)) {  
				$st_int = 10;   // SEM ESTOQUE MIN
			} else {
				$st_int = 0;
				
				$ean = $prd['EAN'];
				if ($prd['is_kit'] == 1) {
					$ean ='IS_KIT'.$prd['id'];
				}
				if ($ean=='') {
					$ean ='NO_EAN'.$prd['id'];
				}
				// inserir na tabela temporária
	        	$data = array(
	        		'int_to' => $val['int_to'],
	        		'prd_id' => $val['prd_id'],
	        		'EAN' => $ean,
	        	    'price' => $this->model_promotions->getPriceProduct($val['prd_id'],$prd['price'],$val['int_to']),
	        		'qty' => $prd['qty'],
	        		'sku' => $prd['sku'],
	        		'reputacao' => '100',  //rick - pegar a reputação da empresa
	        		'NVL' => '0',
	        		'to_int_id' => $val['id'],
	        		'company_id' => $prd['company_id'],
	        		'store_id' => $prd['store_id'],
	        		'qty_atual' => $qty_atual,
	        	);
				$insert = $this->db->insert('int_processing', $data);
			}
			$sql = "UPDATE prd_to_integration SET status_int = ".$st_int. ", int_to ='".$val['int_to']."' WHERE id = ".$val['id'];
			$query = $this->db->query($sql);
		}

	}	

    function toBlingFase2()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		if ($this->naoFezIntegracaoML()) {
    		$this->log_data('batch',$log_name,"Não pode executar pois necessita que seja feito a integração dos produtos no BLING com o mercado livre","E");
    		echo "Tem que fazer integração ML no Bling e executar o BlingMktSku run\n";
    		return;
    	}
    	echo 'Iniciando fase 2'."\n";
		$apikeys = $this->getBlingKeys();
		$parms = Array();
		$sql = "select name,value from settings where name like 'peso_%'";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$parms[$val['name']] = $val['value'];
		}	
		//var_dump($parms);
		$sql = "select * from int_processing";
		$query = $this->db->query($sql);
		$mkt = $query->result_array();
		foreach ($mkt as $ind => $val) {
			$preco = $parms['peso_preco'] / $val['price'];
			$estoque = $val['qty'] / $parms['peso_estoque'];
			$rep = $val['reputacao'] / $parms['peso_reputacao'];
			$nvl = ($preco + $estoque ) * $rep;
			$sql = "UPDATE int_processing SET NVL = ".$nvl. " WHERE id = ".$val['id'];
			$query = $this->db->query($sql);
		}
		echo 'Selecionando produtps'."\n";
		// Seleciona melhores produtos
		$sql = "select * from int_processing order by int_to ASC, EAN ASC, NVL DESC, company_id ASC";
		$query = $this->db->query($sql);
		$mkt = $query->result_array();
		$int_ant = "";
		$ean_ant = "";
		$price = 0;
		$qty = 0;
		foreach ($mkt as $ind => $val) {
			//echo $val['sku']."\n";
			if (($int_ant != $val['int_to']) OR ($ean_ant != $val['EAN']) OR (substr($val['EAN'],0,6)=='IS_KIT') OR (substr($val['EAN'],0,6)=='NO_EAN') ) {
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
					} elseif ($val['qty'] < $qty) {
						$status_int = 12;  // ESTOQUE MENOR  
					} else {
						$status_int = 13;  // REPUTACAO
					}							
				} 
			}
			// Verifica se precisa sair
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to = '".$val['int_to']."' AND EAN = '".$val['EAN']."'";
			$cmd = $this->db->query($sql);
			if($cmd->num_rows() > 0) {    // Existe um antigo
				echo "EXISTE 1 NO BLING...\n";
				$old = $cmd->row_array();
				// Se mesma empresa e mesmo valor , não precisa reenviar 
				if (($old['company_id']==$val['company_id']) && ($old['price']==$val['price']) && ($old['qty']==$val['qty'])){
					echo "EH O MESMO...".$old['prd_id']." \n";
					$status_int = 1; // era 2 mas quero enviar todos novamente. 
				} 
				if ($old['prd_id'] != $ganhador)  {
				//if ((($old['company_id']==$val['company_id']) && ($status_int!=2)) OR (($old['company_id']!=$val['company_id'])  && ($status_int==1))){
					// precisa cair do bling
					echo "TEM QUE CAIR...".$old['prd_id']." ".$old['skubling']." ".$val['int_to']."\n";
					
					$sql = "SELECT name FROM products WHERE id = ".$old['prd_id'];
					$prdsql = $this->db->query($sql);
					$descricao = $prdsql->row_array();
					
					// SWV2 - Tem que pegar a apikey do bling correto
					$apikey = $apikeys[$old['int_to']];
					$code = $old['skubling'];
				    $outputType = "json";
					$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
					//echo $url."\n";
					$retorno = $this->executeDeleteProduct($url, $apikey,$code,$descricao['name']);			// Chama Bling		
					if ($retorno == '201') {
					
					// precisa ser excluido de bling_ult_envio
					$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE int_to = '".$val['int_to']."' AND EAN = '".$val['EAN']."'";
					$cmd = $this->db->query($sql);
					//echo "DERRUBADO DO BLING\n-------------------\n";
					// deu erro. 
						//return;;
					}
				}
				$skubling = $old['skubling'];
			} else {
				// Produto novo 
				//$skubling = strtoupper(substr(md5(uniqid(mt_rand(9999999,9999999999), true)), 0, 13));
				if ((substr($val['EAN'],0,6)=='IS_KIT') OR (substr($val['EAN'],0,6)=='NO_EAN')) {
					$skubling = "P".$val['prd_id']."S".$val['store_id'].$val['int_to'];
				}
				else {
					$skubling = $val['EAN'].$val['int_to'];  // passo a usar o EAN concatenado com o Marketplace para gerar o SKU
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
			$sql = "UPDATE prd_to_integration SET status_int = ".$status_int. ", skubling = '".$skubling."' WHERE id = ".$val['to_int_id'];
			$query = $this->db->query($sql);
		
		}

		$not_int_to = " AND (int_to = 'ML' OR int_to = 'VIA') ";  // Não trago mais a B2W e CAR
		$not_int_to = " AND (int_to = 'VIA') ";  // agora só VIA 
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
					$apikey = $apikeys[$prd_derr['int_to']];
					$outputType = "json";
					$url = 'https://bling.com.br/Api/v2/produto/' . rawurlencode($prd_derr['skubling']) . '/' . $outputType;
					echo $url."\n";
					$retorno = $this->executeDeleteProduct($url, $apikey,$prd_derr['skubling'],$prd['name']);					
					if ($retorno != '201') {
						// deu erro. 
						continue;
					}
					else {
						$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE id = ".$prd_derr['id'];
						$cmd = $this->db->query($sql);
					}
					
				}
			}
		}
		
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

	}	

	

    /**
     * Sync Products with BLING.
     *
     * @return Response
    */
		/* XML PRODUTO BLING
		<produto>
		   <codigo></codigo>
		   <descricao></descricao>
		   <situacao>Ativo</situacao>
		   <descricaoCurta></descricaoCurta>
		   <descricaoComplementar></descricaoComplementar>
		   <un>Pc</un>
		   <vlr_unit></vlr_unit>
		   <peso_bruto></peso_bruto>
		   <peso_liq></peso_liq>
		   <class_fiscal>1000.01.01</class_fiscal>
		   <marca></marca>
		   <origem>0</origem>
		   <estoque></estoque>
		   <deposito>
		      <id></id>
		      <estoque></estoque>
		   </deposito>
		   <gtin></gtin>
		   <largura></largura>
		   <altura></altura>
		   <profundidade></profundidade>
		   <estoqueMinimo>1.00</estoqueMinimo>
		   <estoqueMaximo>100.00</estoqueMaximo>
		   <cest>28.040.00</cest>
		   <condicao>Novo</condicao>
		   <freteGratis>N</freteGratis>
		   <producao>P</producao>
		   <dataValidade>20/11/2019</dataValidade>
		   <unidadeMedida>Centímetros</unidadeMedida>
		   <garantia>6</garantia>
		   <itensPorCaixa>1</itensPorCaixa>
		   <volumes>1</volumes>
		   <imagens>
		     <url></url>
		   </imagens>
		</produto>
		
		*/
		

    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		if ($this->naoFezIntegracaoML()) {
    		$this->log_data('batch',$log_name,"Não pode executar pois necessita que seja feito a integração dos produtos no BLING com o mercado livre","E");
    		echo "Tem que fazer integração ML no Bling e executar o BlingMktSku run\n";
    		return;
    	}
		
		$more = "";
		$url = 'https://bling.com.br/Api/v2/produto/json/';
		$apikeys = $this->getBlingKeys();

		$sql = "select c.id,a.id_loja from stores_mkts_linked a, attribute_value b, integrations c where id_integration = 13 and id_mkt = b.id and b.value = c.int_to";
		$query = $this->db->query($sql);
		$mktlkd = $query->result_array();
		$mktVS = Array();
		foreach ($mktlkd as $ind => $val) {
			$mktVS[$val['id']] = $val['id_loja'];
		}

		$sql = "SELECT * FROM categories_mkts WHERE id_integration = 13";
		$query = $this->db->query($sql);
		$mktcat = $query->result_array();
		$mktcats = Array();
		foreach ($mktcat as $ind => $val) {
			$mktcats[$val['id_cat']] = $val['id_mkt'];
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
		
		// pensar em como derrubar os produtos que já estavam no bling apontando pra alguma loja;
		// feito na fase 2		
		$not_int_to = " AND (int_to = 'ML' OR int_to = 'VIA') ";  // Não trago mais a B2W e CAR 
		$not_int_to = " AND (int_to = 'VIA') ";  // Agora só VIA
		
        //$sql = "select * from prd_to_integration where date_update > date_last_int AND status_int=1 AND status=1 AND int_type = 13 ".$not_int_to." ORDER BY int_to";
		$sql = "select * from prd_to_integration where status_int=1 AND status=1 AND int_type = 13 ".$not_int_to." ORDER BY int_to";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			// acerto o preço do produto com o preço da promoção se tiver 
			$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],$prd['int_to']);
		    $catP = json_decode($prd['category_id']);
		    $cat = $mktcats[$catP[0]];
			$sql = "SELECT * FROM prefixes WHERE company_id = ?";
			$query = $this->db->query($sql, array($prd['company_id']));
			$prf = $query->row_array();
		    $prefix = $prf['prefix']; 
    		$sku = $row['skubling'];
			$apikey = $apikeys[$row['int_to']];
			
	//		echo 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
//			echo 'apikey='.$apikey;
//			echo 'cat='.$cat;
//			echo 'url='.$url;
//			echo 'sku='.$sku;
//			echo 'row[int_to]='.$row['int_to'];
			
			// leio o int_processing 
			$sql = "SELECT * FROM int_processing WHERE to_int_id = ".$row['id'];
			$cmd = $this->db->query($sql);
			$row_int_pro = $cmd->row_array();
			
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$prd['qty'] = $row_int_pro['qty_atual'];
			$retorno = $this->inserePrdBling ($prd,$apikey,$cat,$url,$sku,$row['int_to'],$estoqueIntTo);    // chama bling
			
			if (array_key_exists('erros',$retorno)) {
				if ($retorno['erros'][0]['cod'] ==18) { //estourou o limite de requisicoes no minuto
					sleep(60);
					$retorno = $this->inserePrdBling ($prd,$apikey,$cat,$url,$sku,$row['int_to'],$estoqueIntTo);    // chama bling
				}
				if (array_key_exists('erros',$retorno)) { 
					echo "==================> ERRO BLING <=====================\n";
					var_dump($retorno);
					$this->log_data("batch", $log_name, "ERRO Retorno do Bling: ".print_r($retorno,true),"E");
					return "Erro no bling"; 
				}
			} 
			$nprds = count($retorno['produtos']);
			if ($nprds<2) {  // VOLTAR PRA 2 ***********************
				echo $prd['sku']."\n"; 
				echo "UM SÓ VAI LINKAR VS".$mktcats[$catP[0]]."/".$cat."/".$prd['category_id']."\n";
				// echo 'mktvs='.print_r($mktVS,true);
				//echo '$row='.$row['int_id']."\n"; 
				
				$preco = $this->model_campaigns->getPriceProduct($prd['id'],$prd['price'],$row['int_to']);
				echo 'preco ='.$preco."\n";
				$lnk = $this->linkPrdVS ($apikey,$mktVS[$row['int_id']],$sku,$cat,$prd['price']);			// INSERE OU ATUALIZA O PRODUTO NA LOJA VIRTUAL
				if (($row['int_to']=="ML") || ($row['int_to']=="MAGALU")) {
				  $lnk = $this->zeraPrdVS ($apikey,$mktVS[$row['int_id']],$sku,$row['skumkt'],$cat,$prd['price'],$row['int_to']); // ATUALIZA O PRODUTO SE LOJA VIRTUAL = ML
				}
			
			} else {
				foreach ($retorno['produtos'] as $produto) {			
					$prd_ret = $produto[0]['produto'];
					// var_dump($prd_ret);
					$SKU = $prd_ret['codigo'];
					$PRICE = $prd_ret['preco'];
					echo "=====>".$SKU.":".$PRICE."\n"; 
					if ($SKU!=$prd['sku']) {    // PULA O PAI DAS VARIANTES
						echo "VAI LINKAR VS\n";		
						$lnk = $this->linkPrdVS ($apikey,$mktVS[$row['int_id']],$SKU,$cat,$PRICE);			// INSERE OU ATUALIZA O PRODUTO NA LOJA VIRTUAL
						// atualiza a situação do produto se ele for novo 				
												
						// PRECISA REVER ESSA PARTE
						if (($row['int_to']=="ML") || ($row['int_to']=="MAGALU")) {
							if ($SKU == $sku) {
							  $lnk = $this->zeraPrdVS ($apikey,$mktVS[$row['int_id']],$SKU,$row['skumkt'],$cat,$PRICE,$row['int_to']);			// ATUALIZA O PRODUTO SE LOJA VIRTUAL = ML
							} else {
							  $sql = "SELECT * FROM ML_sku WHERE skubling = '".$SKU."'";
							  $cmd = $this->db->query($sql);
							  if($cmd->num_rows() > 0) {    // Existe um antigo
								  $skuml = $cmd->row_array();
								  $skuml = $skuml['skumkt'];
							  } else {
								  $skuml = "";
							  }				  
							  $lnk = $this->zeraPrdVS ($apikey,$mktVS[$row['int_id']],$SKU,$skuml,$cat,$PRICE,$row['int_to']);			// ATUALIZA O PRODUTO SE LOJA VIRTUAL = ML
							  $sql = "REPLACE into ML_sku VALUES ('".$SKU."','".$skuml."')";
							  $cmd = $this->db->query($sql);
							}								  		
						}
					}
				}
			} 

			// TROUXE PRA DENTRO DO SUCESSO
			$int_date_time = date('Y-m-d H:i:s');
			$sql = "UPDATE prd_to_integration SET status_int=2 , date_last_int = ? WHERE id = ".$row['id'];
			$cmd = $this->db->query($sql,array($int_date_time));
			
			if (($row['int_to']=="ML") || ($row['int_to']=="MAGALU")) {
				$xsku = $row['skumkt'];  // ERA BRANCO
			} else {
				$xsku = $sku;
			}	
			
			// rick Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
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
			
			
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$row['int_to']."' AND prd_id = ".$row_int_pro['prd_id']." AND EAN = '".$row_int_pro['EAN']."'";
			$cmd = $this->db->query($sql);
			$bling_ult_envio = $cmd->row_array();
			$marca_int_bling= null;
			$categoria_bling = null;
			if ($bling_ult_envio) {
				$marca_int_bling = $bling_ult_envio['marca_int_bling'];
				$categoria_bling = $bling_ult_envio['categoria_bling'];
				$integrar_price = ($bling_ult_envio['integrar_price'] || ($row_int_pro['price'] != $bling_ult_envio['price']));
				$integrar_qty = ($bling_ult_envio['integrar_qty'] || ($row_int_pro['qty_atual'] != $bling_ult_envio['qty_atual']));
			}
			else{
				$integrar_price = true;
				$integrar_qty = true;
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
        		'mkt_store_id' => $mktVS[$row['int_id']],  
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
        		'integrar_price' => $integrar_price,
	        	'integrar_qty' => $integrar_qty,
				
        	);
			if ($bling_ult_envio) {
				$insert = $this->model_blingultenvio->update($data, $bling_ult_envio['id']);
			}else {
				//$insert = $this->model_blingultenvio->create($data);
				$insert = $this->db->replace('bling_ult_envio', $data);
			}
			
	    }
		echo " ------- Processo de envio de produtos terminou\n";
        return "PRODUCTS Synced with BLING";
    } 

	function inserePrdBling ($row,$apikey,$cat,$url,$skumkt,$int_to, $estoqueIntTo) {	
	if ($row['has_variants']=="") {
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
   <situacao>Ativo</situacao>
   <descricaoCurta></descricaoCurta>
   <descricaoComplementar></descricaoComplementar>
   <un>Un</un>
   <vlr_unit></vlr_unit>
   <peso_bruto></peso_bruto>
   <peso_liq></peso_liq>
   <class_fiscal>1000.01.01</class_fiscal>
   <marca></marca>
   <origem>0</origem>
   <estoque></estoque>
   <deposito>
      <id>3075788878</id>
      <estoque></estoque>
   </deposito>
   <gtin></gtin>
   <largura></largura>
   <altura></altura>
   <profundidade></profundidade>
   <estoqueMinimo>1.00</estoqueMinimo>
   <estoqueMaximo>100.00</estoqueMaximo>
   <idGrupoProduto></idGrupoProduto>
   <categoria>
   		<id></id>
   		<descricao></descricao>
   </categoria>
   <cest>28.040.00</cest>
   <condicao>Novo</condicao>
   <freteGratis>N</freteGratis>
   <producao>T</producao>
   <dataValidade>31/12/2022</dataValidade>
   <unidadeMedida>Centímetros</unidadeMedida>
   <garantia>6</garantia>
   <itensPorCaixa>1</itensPorCaixa>
   <volumes>1</volumes>
   <imagens>
     <url></url>
   </imagens>
</produto>
XML;
	} else {
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
   <situacao>Ativo</situacao>
   <descricaoCurta></descricaoCurta>
   <descricaoComplementar></descricaoComplementar>
   <un>Un</un>
   <peso_bruto></peso_bruto>
   <peso_liq></peso_liq>
   <class_fiscal>1000.01.01</class_fiscal>
   <marca></marca>
   <origem>0</origem>
   <estoque></estoque>
   <deposito>
      <id>3075788878</id>
      <estoque></estoque>
   </deposito>
   <gtin></gtin>
   <largura></largura>
   <altura></altura>
   <profundidade></profundidade>
   <estoqueMinimo>1.00</estoqueMinimo>
   <estoqueMaximo>100.00</estoqueMaximo>
   <categoria>
   		<id></id>
   		<descricao></descricao>
   </categoria>
   <cest>28.040.00</cest>
   <condicao>Novo</condicao>
   <freteGratis>N</freteGratis>
   <producao>T</producao>
   <crossdocking>5</crossdocking>
   <dataValidade>31/12/2022</dataValidade>
   <unidadeMedida>Centímetros</unidadeMedida>
   <garantia>6</garantia>
   <itensPorCaixa>1</itensPorCaixa>
   <volumes>1</volumes>
   <variacoes>
   </variacoes>
   <imagens>
     <url></url>
   </imagens>
</produto>
XML;
	}
		echo " Enviando sku ".$skumkt." id ".$row['id']." para ".$int_to."\n";
		$xml = new SimpleXMLElement($pruebaXml);
		$sku = $skumkt;
		$xml->codigo[0] = $sku;
		if ($int_to=="ML") {
			//echo "nome antes = ".$row['name']."\n";
			$nome = htmlspecialchars($row['name'], ENT_QUOTES, "utf-8");
			$xml->descricao[0] = substr($nome,0,60);  // SO 60 chars por causa do ML
		} else {
			$xml->descricao[0] = htmlspecialchars($row['name'], ENT_QUOTES, "utf-8");
		}
		//$xml->descricaoCurta[0] = htmlspecialchars($row['description'], ENT_QUOTES, "utf-8");
		$xml->descricaoCurta[0] = htmlspecialchars(strip_tags(str_replace("<br>"," \n",$row['description'])), ENT_QUOTES, "utf-8");
	 
		$xml->vlr_unit[0] = $this->model_campaigns->getPriceProduct($row['id'],$row['price'],$int_to);
		
		// $xml->vlr_unit[0] = $row['price'];
		
		$xml->peso_bruto[0] = $row['peso_bruto'];
		$xml->peso_liq[0] = $row['peso_liquido'];
		$brand_id = json_decode($row['brand_id']);
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $this->db->query($sql, $brand_id);
		$brand = $query->row_array();
		$xml->marca[0] = $brand['name'];
		$xml->estoque[0] = $row['qty'];
		$xml->gtin[0] = $row['EAN'];
		$xml->altura[0] = $row['altura'];
		$xml->largura[0] = $row['largura'];
		$xml->profundidade[0] = $row['profundidade'];
		$crossdocking = (is_null($row['prazo_operacional_extra'])) ? 0 : $row['prazo_operacional_extra'];
		$xml->crossdocking[0] = $crossdocking + 5;
		
		// para pegar o depósito https://bling.com.br/Api/v2/depositos/json?apikey=3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157 
		if (($int_to=="B2W") || ($int_to=="ML")) {  // tem um bling só dele
			$xml->deposito->id[0]="7832346198";
		}else {   // o bling default
			$xml->deposito->id[0]="3075788878";
		}
		$xml->deposito->estoque[0] = $row['qty'];
		$xml->categoria->id[0] = $cat;
		//rick dataValidade 
		echo "SKU:". $sku . " CATEGORIA:".$cat. " ORIG:".$row['sku']."\n";
		echo "IMAGENS:".$row['image']."\n";
		if ($row['image']!="") {
			$numft = 0;
			if (strpos("..".$row['image'],"http")>0) {
				$fotos = explode(",", $row['image']);	
				foreach($fotos as $foto) {
					$xml->imagens->url[$numft++] = $foto;
				}
			} else {
				$fotos = scandir(FCPATH . 'assets/images/product_image/' . $row['image'],1); // Bling tem que enviar com fotos invertidas	
				foreach($fotos as $foto) {
					if (($foto!=".") && ($foto!="..")) {
						// $xml->imagens->url[$numft] = base_url('assets/images/product_image/' . $row['image'].'/'. $foto);
						// rick trocar com o site correto e colocar no mycrontoller para nao ter que pegar o site correto 
						// $xml->imagens->url[$numft++] = 'http://conectala.com.br/fase1/assets/images/product_image/' . $row['image'].'/'. $foto;
						$xml->imagens->url[$numft++] = base_url('assets/images/product_image/' . $row['image'].'/'. $foto);
					}
				}
			}	
		}
		// TRATAR VARIANTS		
		if ($row['has_variants']!="") {
            $prd_vars = $this->model_products->getProductVariants($row['id'],$row['has_variants']);
            // var_dump($prd_vars);
            $tipos = explode(";",$row['has_variants']);
            // var_dump($tipos);
            $i = -1;
			foreach($prd_vars as $value) {
				// var_dump($value);
			  if (isset($value['sku'])) { 	
				$apelido = "";
				$SKU = "";
				foreach ($tipos as $z => $campo) {
					if ($apelido!="") {
						$apelido .= ";";
						$SKU .= "-";
					}
					$apelido .= $campo.":".$value[$campo];
					$SKU .= $value[$campo];
				}
				// var_dump($i);
				//var_dump($apelido);
				$i++;
				$xml->variacoes->variacao[$i]->nome =  $apelido; 
				$xml->variacoes->variacao[$i]->codigo =  $sku."-".$value['variant'];
				$xml->variacoes->variacao[$i]->vlr_unit = $this->model_campaigns->getPriceProduct($row['id'],$row['price'],$int_to);
				if ($int_to=="ML") {
					$xml->variacoes->variacao[$i]->clonarDadosPai = "N";
				} else {
					$xml->variacoes->variacao[$i]->clonarDadosPai = "S";
				}	
				//$xml->variacoes->variacao[$i]->deposito->id = "3075788878";
				if (($int_to=="B2W") || ($int_to=="ML")) {  // tem um bling só dele
					$xml->variacoes->variacao[$i]->deposito->id = "7832346198";
				}else {   // o bling default
					$xml->variacoes->variacao[$i]->deposito->id = "3075788878";
				}
				
				//$xml->variacoes->variacao[$i]->deposito->estoque = $value['qty'];
				
				$qty_atual = (int) $value['qty'] * $estoqueIntTo[$int_to] / 100; 
				$xml->variacoes->variacao[$i]->deposito->estoque = ceil($qty_atual);
			  }	
			}
		}

		$dados = $xml->asXML();
		var_dump($dados);
		echo '---------------';
		 
		$posts = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);
		echo "**************** VAI MANDAR BLING ***************\n";
		$retorno = $this->executeInsertProduct($url, $posts);
		$retorno = json_decode($retorno,true);
		var_dump($retorno);
		$retorno = $retorno['retorno'];
		if (array_key_exists('erros',$retorno)) {
			return $retorno;
		}
		$retornoCriado = $retorno;
		
		//vejo se o produto já teve a categoria diferente da padrão associada
		$sql = "SELECT * FROM bling_ult_envio WHERE skumkt = '".$sku."' AND categoria_bling is not null AND categoria_bling != '1122016'";
		$query = $this->db->query($sql);
		$rows = $query->result_array();
		if (count($rows) == 0 ) {
			// se não tem, não adianta mandar os campos customizados 
			return $retornoCriado;
		}
        // Adiciona campos das categorias do ML 
		// leio os valores dos campos por produto 
		
		$this->load->model('model_atributos_categorias_marketplaces', 'myatributoscategorias');	
		$produtos_atributos = $this->myatributoscategorias->getAllProdutosAtributos($row['id']);
		// vejo se tem campos customizados
		if (empty($produtos_atributos)) {
			return $retornoCriado;
		}
		$criaChildXml = true;
		
		
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
</produto>
XML;
		$xml = new SimpleXMLElement($pruebaXml);
		$sku = $skumkt;
		$xml->codigo[0] = $sku;
		if ($int_to=="ML") {
			$xml->descricao[0] = htmlspecialchars(substr($row['name'],0,60), ENT_QUOTES, "utf-8");  // SO 60 chars por causa do ML
		} else {
			$xml->descricao[0] = htmlspecialchars($row['name'], ENT_QUOTES, "utf-8");
		}
		//$xml->descricaoCurta[0] = htmlspecialchars($row['description'], ENT_QUOTES, "utf-8");
		$xml->descricaoCurta[0] = htmlspecialchars(strip_tags(str_replace("<br>"," \n",$row['description'])), ENT_QUOTES, "utf-8");
	 
		// $produtos_atributos =array();
		foreach ($produtos_atributos as $produto_atributo) {
			if ($criaChildXml) {
				$xml->addChild("camposCustomizados");
				// $xml->camposCustomizados->addChild('marca',$brand['name']);
				$criaChildXml=false; 
			}
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
			$xml->camposCustomizados->addChild(strtolower($id_atributo),$valor);
		} 
		$dados = $xml->asXML();
		var_dump($dados);
		echo '---------------';
		$url = 'https://bling.com.br/Api/v2/produto/'.$sku.'/json/';
		$posts = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);
		echo "**************** VAI MANDAR BLING ***************\n";
		$retorno = $this->executeUpdateProduct($url, $posts);
		$retorno = json_decode($retorno,true);
		$retorno = $retorno['retorno'];
		if (array_key_exists('erros',$retorno)) {
			return $retornoCriado;
		}
		return $retornoCriado;
	}	
	
	function executeInsertProduct($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}
	
	function executeUpdateProduct($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}

	
	function executeInsertCategory($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}    	

	function linkPrdVS ($apikey,$store,$prdsku,$cat,$price) {	
		$url = 'https://bling.com.br/Api/v2/produtoLoja/'.$store.'/'.$prdsku.'/json/';
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<produtosLoja>
   <produtoLoja>
      <idLojaVirtual></idLojaVirtual>
      <preco>
         <preco></preco>
         <precoPromocional></precoPromocional>
      </preco>
      <idFornecedor></idFornecedor>
      <idMarca></idMarca>
      <categoriasLoja>
         <categoriaLoja>
            <idCategoria></idCategoria>
         </categoriaLoja>
      </categoriasLoja>
   </produtoLoja>
</produtosLoja>
XML;

		$xml = new SimpleXMLElement($pruebaXml);
		$xml->produtoLoja->idLojaVirtual[0] = $prdsku;
		$xml->produtoLoja->preco->preco[0] = $price;
		$xml->produtoLoja->preco->precoPromocional[0] = $price;
		$xml->produtoLoja->categoriasLoja->categoriaLoja->idCategoria[0] = $cat;
		$dados = $xml->asXML();
		var_dump($dados);
		$posts = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);
		$retorno = $this->executeInsertCategory($url, $posts);
		var_dump($retorno);
		return $retorno;
	}

	function getBlingKeys() {
		$apikeys = Array();
		$sql = "select * from stores_mkts_linked where id_integration = 13";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}	
		// var_dump($apikeys);
		
		return $apikeys;
	}

	function zeraPrdVS ($apikey,$store,$prdsku,$skumkt,$cat,$price,$int_to) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$url = 'https://bling.com.br/Api/v2/produtoLoja/'.$store.'/'.$prdsku.'/json/';
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<produtosLoja>
   <produtoLoja>
      <idLojaVirtual>00</idLojaVirtual>
      <preco>
         <preco></preco>
         <precoPromocional></precoPromocional>
      </preco>
      <idFornecedor></idFornecedor>
      <idMarca></idMarca>
      <categoriasLoja>
         <categoriaLoja>
            <idCategoria></idCategoria>
         </categoriaLoja>
      </categoriasLoja>
   </produtoLoja>
</produtosLoja>
XML;

		$xml = new SimpleXMLElement($pruebaXml);
		if (trim($skumkt)!="") {
			$xml->produtoLoja->idLojaVirtual[0] = $skumkt;
		} elseif ($int_to=='MAGALU') {
			$xml->produtoLoja->idLojaVirtual[0] = '00';  // deveria ser 0 mas o bling não está aceitando
		} elseif ($int_to=="ML") {
			$xml->produtoLoja->idLojaVirtual[0] = '00';
		}
		$xml->produtoLoja->preco->preco[0] = $price;
		$xml->produtoLoja->preco->precoPromocional[0] = $price;
		$xml->produtoLoja->categoriasLoja->categoriaLoja->idCategoria[0] = $cat;
		$dados = $xml->asXML();
		var_dump($dados);
		$data = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);
		
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $retorno = curl_exec($curl_handle);
		
		$httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		if (!($httpcode=="200") ) {
			echo "Erro na respota do bling. httpcode=".$httpcode."\n";
			echo " URL: ". $url. "\n"; 
			echo " RESPOSTA BLING: ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA BLING: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
		}

		if (array_key_exists('erros',json_decode($retorno,true))) {
			echo " URL: ". $url. "\n"; 
			echo " RESPOSTA BLING: ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA BLING: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
		}
		
	    curl_close($curl_handle); 
		return $retorno;
	}
	
	/* ********************************************
		Não pode deletar do BLING pq ele não sincroniza 
		a exclusão, zerando o estoque o produto fica 
		inativo no bling e nos marketplaces
	********************************************  */
	
	function executeDeleteProduct($url,$apikey,$code, $descricao) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
   <estoque>0</estoque>
</produto>
XML;

		$xml = new SimpleXMLElement($pruebaXml);
		$xml->codigo[0] = $code;
		$xml->descricao[0] = $descricao;
		$dados = $xml->asXML();
		//var_dump($dados);
		$data = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);

	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);	
		$httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		if (!($httpcode=="201") ) {
			echo "Erro na respota do bling. httpcode=".$httpcode."\n";
			echo " URL: ". $url. "\n"; 
			echo " RESPOSTA BLING: ".print_r($response,true)." \n"; 
			echo " Dados enviados: ".print_r($data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO [cria site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA BLING: '.print_r($response,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
		}
		curl_close($curl_handle);
	    return $httpcode;
	}

}
?>
