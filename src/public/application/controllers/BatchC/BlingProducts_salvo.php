<?php
/*
SW Serviços de Informática 2019
 
Atualiza produtos no BLING

*/   

	function naoFezIntegracaoML($me){
		$sql = "SELECT * FROM bling_ult_envio WHERE (int_to='ML' AND (skubling = skumkt OR skumkt ='00')) OR (int_to='MAGALU' AND (skubling = skumkt OR skumkt ='00'))";
		$cmd = $me->db->query($sql);
		// return(0);
		return ($cmd->num_rows());  
	}
	
	function sendMarketplace($me,$cmd = NULL){
		
		echo "Marcando todos os produtos para envio para todos os marketplaces da Conectala\n";
		get_instance()->log_data('batch','BlingProducts/sendMarketplace',$cmd,"I");
		
		$me->load->model('model_integrations', 'myintegrations');
		$me->load->model('model_products','myproducts');
		$integrations = get_instance()->myintegrations->getIntegrationsbyType('BLING');
		foreach ($integrations as $integration) {
			$products = get_instance()->myproducts->getProductsByCompany($integration['company_id']);
			foreach ($products as $product) {
				if (($product['status'] == 1) && ($product['situacao'] != '1')) {
					// adiciono produto ativo na fila para a integração
					echo 'Adicionando para '. $integration['name'].' produto '.$product['id'].' empresa '.$product['company_id']. "\n"; 
					$prd = Array(
						'int_id' => $integration['id'],
						'prd_id' => $product['id'],
						'cpy_id' => $product['company_id'],
						'date_last_int' => '',
						'status' => 1,
						'status_int' => 0,
						'int_type' => 13,        // BLING FORÇADO
						'int_to' => $integration['name'] , 
					);
					get_instance()->myintegrations->setProductToMkt($prd);
				} 
				else {
					echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' empresa '.$product['company_id']. "\n"; 
					// removo produto inativo da fila para a integração
					$prdData = Array(
						'status' => 0,
						'user_id' => 1  // Administrador que retirou 
					);
					get_instance()->myintegrations->unsetProductToMkt( $integration['id'],$product['id'],$product['company_id'],$prdData);
				}
			}
		}
	}

    function removeBlingInative($me)
    {
		$sql = "select p.id as id,int_id,company_id,prd_id,p.int_to from prd_to_integration p, integrations i where p.int_type = 13 AND status = 0 and int_id = i.id order by prd_id";
		$query = $me->db->query($sql);
		$mktlkd = $query->result_array();
		foreach ($mktlkd as $ind => $val) {
			// Verifica se precisa sair
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to = '".$val['int_to']."' AND prd_id = '".$val['prd_id']."'";
			$cmd = $me->db->query($sql);
			if($cmd->num_rows() > 0) {    // Existe um antigo
				echo "EXISTE 1 NO BLING...\n";
				$old = $cmd->row_array();
				
				// precisa cair do bling
				echo "TEM QUE ZERAR...\n";
				$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
				$code = $old['skubling'];
				
				$sql = "SELECT name FROM products WHERE id = ".$old['prd_id'];
				$prdsql = $me->db->query($sql);
				$descricao = $prdsql->row_array();
				
				$outputType = "json";
				$url = 'https://bling.com.br/Api/v2/produto/' . rawurlencode($code) . '/' . $outputType;
				echo $url."\n";
				$retorno = executeDeleteProduct($url, $apikey,$code,$descricao['name']);					
				if ($retorno != '201') {
					// deu erro. 
					die;
				}
				echo "ZERADO DO BLING\n-------------------\n";
				// precisa ser excluido de bling_ult_envio
				echo "ZERA ESTOQUE DO ULT_ENVIO\n-------------------\n";
				$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE int_to = '".$old['int_to']."' AND EAN = '".$old['EAN']."'";
				$cmd = $me->db->query($sql);
			}
			// precisa ser excluido de prd_to_integration
			echo "DELETADO DA INTEGRACAO\n-------------------\n";
			$sql = "DELETE FROM prd_to_integration WHERE id = ".$val['id'];
			$cmd = $me->db->query($sql);
		}
	}		

    function toBlingFase1($me,$cmd = NULL)
    {
    	get_instance()->log_data('batch','BlingProducts/toBlingFase1',$cmd,"I");	
		
    	if (naoFezIntegracaoML($me)) {
    		get_instance()->log_data('batch','BlingProducts/toBlingFase1',"Não pode executar pois necessita que seja feito a integração dos produtos no BLING com o mercado livre","E");
    		echo "Tem que fazer integração ML no Bling e executar o BlingProducts getMktSku\n";
    		return;
    	}
		
		// Em análise
		$sql = "UPDATE prd_to_integration SET status_int = 99 WHERE int_type = 13";
		$query = $me->db->query($sql);
		// Em limpa tabela temporaria
		$sql = "TRUNCATE TABLE int_processing";
		$query = $me->db->query($sql);

		$parms = Array();
		// filtro de estoque minimo de cada marketplace
		$sql = "select id, value ,concat(lower(value),'_estoque_min') as name from attribute_value av where attribute_parent_id = 5";
		$query = $me->db->query($sql);
		$mkts = $query->result_array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $me->db->query($sql);
			$parm = $query->row_array();
			$parms[$val['value']] = $parm['value'];
		}
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $me->db->query($sql);
		$mkts = $query->result_array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $me->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value'].'PERC'; 
			$parms[$key_param] = $parm['value'];
		}	
		
		$sql = "select p.id,int_id,prd_id,i.int_to from prd_to_integration p, integrations i where p.int_type = 13 AND status = 1 and int_id = i.id order by prd_id";
		$query = $me->db->query($sql);
		$mktlkd = $query->result_array();
		$prd_ant = "";
		foreach ($mktlkd as $ind => $val) {
			// Check QTY
			if ($prd_ant!=$val['prd_id']) {
				$prd_ant = $val['prd_id'];
				$sql = "select * from products WHERE id = ".$val['prd_id'];
				$query = $me->db->query($sql);
				$prd = $query->row_array();
			}
			$key_param = $val['int_to'].'PERC'; 
			
			$qty_atual = (int) $prd['qty'] * $parms[$key_param] / 100; 
			$qty_atual = (int) $qty_atual; 
			//echo 'mkt= '.$val['int_to'].' qty='.$prd['qty'].' Nova quantidade = '. $qty_atual. ' perc ='.$parms[$key_param].'/n';
			if ($qty_atual<$parms[$val['int_to']]) {
				$st_int = 10;   // SEM ESTOQUE MIN
			} else {
				$st_int = 0;
				// inserir na tabela temporária
	        	$data = array(
	        		'int_to' => $val['int_to'],
	        		'prd_id' => $val['prd_id'],
	        		'EAN' => $prd['EAN'],
	        		'price' => $prd['price'],
	        		'qty' => $prd['qty'],
	        		'sku' => $prd['sku'],
	        		'reputacao' => '100',  //rick - pegar a reputação da empresa
	        		'NVL' => '0',
	        		'to_int_id' => $val['id'],
	        		'company_id' => $prd['company_id'],
	        		'store_id' => $prd['store_id'],
	        		'qty_atual' => $qty_atual,
	        	);
				$insert = $me->db->insert('int_processing', $data);
			}
			$sql = "UPDATE prd_to_integration SET status_int = ".$st_int. ", int_to ='".$val['int_to']."' WHERE id = ".$val['id'];
			$query = $me->db->query($sql);
		}

	}	

    function toBlingFase2($me,$cmd = NULL)
    {
    	get_instance()->log_data('batch','BlingProducts/toBlingFase2',$cmd,"I");
		if (naoFezIntegracaoML($me)) {
			get_instance()->log_data('batch','BlingProducts/toBlingFase2',"Não pode executar pois necessita que seja feito a integração dos produtos no BLING com o mercado livre","E");
			
    		echo "Tem que fazer integração ML no Bling e executar o BlingProducts getMktSku\n";
    		return;
    	}
		$apikeys = getBlingKeys($me);
		$parms = Array();
		$sql = "select name,value from settings where name like 'peso_%'";
		$query = $me->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$parms[$val['name']] = $val['value'];
		}	
		var_dump($parms);
		$sql = "select * from int_processing";
		$query = $me->db->query($sql);
		$mkt = $query->result_array();
		foreach ($mkt as $ind => $val) {
			$preco = $parms['peso_preco'] / $val['price'];
			$estoque = $val['qty'] / $parms['peso_estoque'];
			$rep = $val['reputacao'] / $parms['peso_reputacao'];
			$nvl = ($preco + $estoque ) * $rep;
			$sql = "UPDATE int_processing SET NVL = ".$nvl. " WHERE id = ".$val['id'];
			$query = $me->db->query($sql);
		}
		// Seleciona melhores produtos
		$sql = "select * from int_processing order by int_to ASC, EAN ASC, NVL DESC, company_id ASC";
		$query = $me->db->query($sql);
		$mkt = $query->result_array();
		$int_ant = "";
		$ean_ant = "";
		$price = 0;
		$qty = 0;
		foreach ($mkt as $ind => $val) {
			//echo $val['sku']."\n";
			if (($int_ant != $val['int_to']) OR ($ean_ant != $val['EAN'])) {
				echo "SELECIONADO...\n";
				$status_int = 1;
				$int_ant = $val['int_to'];
				$ean_ant = $val['EAN'];
				$price = $val['price'];
				$qty = $val['qty'];
			} else {
				echo "PERDEU...\n";
				if ($ean_ant == $val['EAN']) {
					if ($val['price'] > $price) {
						$status_int = 11;  // PREÇO ALTO
					} elseif ($val['price'] > $price) {
						$status_int = 12;  // ESTOQUE MENOR  
					} else {
						$status_int = 13;  // REPUTACAO
					}							
				} 
			}
			// Verifica se precisa sair
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to = '".$val['int_to']."' AND EAN = '".$val['EAN']."'";
			$cmd = $me->db->query($sql);
			if($cmd->num_rows() > 0) {    // Existe um antigo
				echo "EXISTE 1 NO BLING...\n";
				$old = $cmd->row_array();
				// Se mesma empresa e mesmo valor , não precisa reenviar 
				if (($old['company_id']==$val['company_id']) && ($old['price']==$val['price']) && ($old['qty']==$val['qty'])){
					echo "EH O MESMO... \n";
					$status_int = 2;
				} 
				if ((($old['company_id']==$val['company_id']) && ($status_int!=2)) OR (($old['company_id']!=$val['company_id'])  && ($status_int==1))){
					// precisa cair do bling
					echo "TEM QUE CAIR...\n";
					
					$sql = "SELECT name FROM products WHERE id = ".$old['prd_id'];
					$prdsql = $me->db->query($sql);
					$descricao = $prdsql->row_array();
					
					// SWV2 - Tem que pegar a apikey do bling correto
					$apikey = $apikeys[$old['int_to']];
					$code = $old['skubling'];
				    $outputType = "json";
					$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
					//echo $url."\n";
					$retorno = executeDeleteProduct($url, $apikey,$code,$descricao['name']);			// Chama Bling		
					if ($retorno != '201') {
					// deu erro. 
						die;;
					}
					// precisa ser excluido de bling_ult_envio
					$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE int_to = '".$val['int_to']."' AND EAN = '".$val['EAN']."'";
					$cmd = $me->db->query($sql);
					echo "DERRUBADO DO BLING\n-------------------\n";
					
				}
				$skubling = $old['skubling'];
			} else {
				$skubling = strtoupper(substr(md5(uniqid(mt_rand(9999999,9999999999), true)), 0, 13));
				// rick testar se ja exite no bling_ult_envio e gerar um novo se for preciso
			}
					
			// Atualiza status_int
			$sql = "UPDATE prd_to_integration SET status_int = ".$status_int. ", skubling = '".$skubling."' WHERE id = ".$val['to_int_id'];
			$query = $me->db->query($sql);
		
		}
	}	


    /**
     * Get All Linked Categories in BLING and Save them for Products Integration.
     *
     * @return Response
    */
	function getLinkedCats($me)
	{
		
		$sql = "select * from stores_mkts_linked WHERE id_integration = 13";
		$query = $me->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			$more = 'categoriasLoja/' . $row['id_loja'] . '/';
			$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
			$outputType = "json";
			$url = 'https://bling.com.br/Api/v2/'. $more . $outputType;
			$retorno = executeGetCategories($url, $apikey);
			$linkedcats = json_decode($retorno,true);
			$linkedcats = $linkedcats['retorno'];
	 		if (isset($linkedcats['categoriasLoja'])) {
				$linkedcats = $linkedcats['categoriasLoja'];
				foreach($linkedcats as $ind => $lnk) {
					$cat = $lnk['categoriaLoja'];
					$sql = "REPLACE INTO categories_mkts_linked VALUES(13,".$row['id_mkt'].",'".$cat['idCategoria']."','".$cat['idVinculoLoja']."','".$cat['descricaoVinculo']."')";
					$query = $me->db->query($sql);
				}
			}
		}
        return;
	}

    /**
     * Sync Categories with BLING.
     *
     * @return Response
    */
    function syncCategories($me)
    {
		/*
		From BLING documentation	
		<?xml version="1.0" encoding="UTF-8"?>SchumacherFull/Conectala100
		<categorias>
		  <categoria>
		    <descricao>Casa, Mesa e Banho</descricao>
		    <idcategoriapai>0</idcategoriapai>
		  </categoria>
		</categorias>
		*/		    
		// Primeiro Sync Categorias Linkadas com BLING
		getLinkedCats($me);
		// Agora Fase 2
		$more = "";
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$url = 'https://bling.com.br/Api/v2/produto/json/';

		$sql = "TRUNCATE TABLE cat_bling";
		$query = $me->db->query($sql);

		$tem = true;
		$pagina = 0;		
		$mktcats = Array();
		while ($tem) {
			$pagina++;
			$more = "categorias/page=".$pagina."/";
			$outputType = "json";
			$url2 = 'https://bling.com.br/Api/v2/'. $more . $outputType;
			echo $url2 . "\n";
			$mktcat = executeGetCategories($url2, $apikey);
			$mktcat = json_decode($mktcat,true);
			// var_dump($mktcat);
	 		if (isset($mktcat['retorno']['categorias'])) {
				$mktcat = $mktcat['retorno']['categorias'];
				foreach ($mktcat as $key => $cat) {
					$sql = "INSERT INTO cat_bling VALUES ('".$cat['categoria']['id']."','".trim($cat['categoria']['descricao'])."','".$cat['categoria']['idCategoriaPai']."')";
					$query = $me->db->query($sql);
				}
				$sql = "select a.cat as cat , a.id as id from cat_bling a where a.id_pai = 0 union select concat(b.cat,' > ',a.cat) , a.id from cat_bling a, cat_bling b where a.id_pai <> '0' and a.id_pai = b.id";
				$query = $me->db->query($sql);
				$mktcat = $query->result_array();
				$mktcats = Array();
				foreach ($mktcat as $ind => $val) {
					$mktcats[$val['cat']] = $val['id'];
				}
			} else {
				$tem = false;
			}	
		}
		$sql = "select * from categories where data_alteracao > date_sub(NOW(), interval 1 HOUR) ORDER BY id ";       // CHANGED IN THE LAST HOUR
		$query = $me->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
		    $cat = $row['name'];
			if (isset($mktcats[$cat])) {
				
			} else {
			    $fcats = explode('>', $cat);
			    $fullcat = "";
			    $idant = 0;
			    $ccats = count($fcats) - 1;
			    for ($j = 0; $j <= $ccats; $j++) {
				    $fullcat .= trim($fcats[$j]);
					if (isset($mktcats[$fullcat])) {
						$idant = $mktcats[$fullcat];
					} else {
						// insere o item
						if ($j == $ccats) {
							$idant = insereCatBling ($me,$row['id'],$apikey,$fcats[$j],$idant);    // Pra só inserir na tabela o último
						} else {
							$idant = insereCatBling ($me,0,$apikey,$fcats[$j],$idant);   
						}
						$mktcats[$fullcat] = $idant;
						// pega id do novo pai			
					}	
					$fullcat .= " > ";
				}	
			} // Já existe
	    }
        return "CATEGORIES SYNCED WITH BLING";
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
		

    function syncProducts($me, $cmd = null)
    {
    	get_instance()->log_data('batch','BlingProducts/syncProducts',$cmd,"I");
		if (naoFezIntegracaoML($me)) {
			get_instance()->log_data('batch','BlingProducts/syncProducts',"Não pode executar pois necessita que seja feito a integração dos produtos no BLING com o mercado livre","E");		
    		echo "Tem que fazer integração ML no Bling e executar o BlingProducts getMktSku\n";
    		return;
    	}
		$more = "";
		$url = 'https://bling.com.br/Api/v2/produto/json/';
		$apikeys = getBlingKeys($me);

		get_instance()->load->model('model_products');

		$sql = "select c.id,a.id_loja from stores_mkts_linked a, attribute_value b, integrations c where id_integration = 13 and id_mkt = b.id and b.value = c.int_to";
		$query = $me->db->query($sql);
		$mktlkd = $query->result_array();
		$mktVS = Array();
		foreach ($mktlkd as $ind => $val) {
			$mktVS[$val['id']] = $val['id_loja'];
		}

		$sql = "SELECT * FROM categories_mkts WHERE id_integration = 13";
		$query = $me->db->query($sql);
		$mktcat = $query->result_array();
		$mktcats = Array();
		foreach ($mktcat as $ind => $val) {
			$mktcats[$val['id_cat']] = $val['id_mkt'];
		}
		
		// pensar em como derrubar os produtos que já estavam no bling apontando pra alguma loja;
		// feito na fase 2		
		
        $sql = "select * from prd_to_integration where date_update > date_last_int AND status_int=1 AND status=1 AND int_type = 13 ORDER BY int_to";
		$query = $me->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $me->db->query($sql);
			$prd = $cmd->row_array();
		    $catP = json_decode($prd['category_id']);
		    $cat = $mktcats[$catP[0]];
			$sql = "SELECT * FROM prefixes WHERE company_id = ?";
			$query = $me->db->query($sql, array($prd['company_id']));
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
			$cmd = $me->db->query($sql);
			$row_int_pro = $cmd->row_array();
			
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$prd['qty'] = $row_int_pro['qty_atual'];
			$retorno = inserePrdBling ($me,$prd,$apikey,$cat,$url,$sku,$row['int_to']);    // chama bling
			
			if (array_key_exists('erros',$retorno)) {
				echo "==================> ERRO BLING <=====================\n";
				var_dump($retorno);
				get_instance()->log_data("batch", "BlingProducts syncProducts", "ERRO Retorno do Bling: ".print_r($retorno,true),"E");
				return "Erro no bling"; 
			} else { 
				$nprds = count($retorno['produtos']);
				if ($nprds<2) {  // VOLTAR PRA 2 ***********************
					echo $prd['sku']."\n"; 
					echo "UM SÓ VAI LINKAR VS".$mktcats[$catP[0]]."/".$cat."/".$prd['category_id']."\n";
					echo 'mktvs='.print_r($mktVS,true);
					echo '$row='.$row['int_id']; 
					$lnk = linkPrdVS ($me,$apikey,$mktVS[$row['int_id']],$sku,$cat,$prd['price']);			// INSERE OU ATUALIZA O PRODUTO NA LOJA VIRTUAL
					if (($row['int_to']=="ML") || ($row['int_to']=="MAGALU")) {
					  $lnk = zeraPrdVS ($me,$apikey,$mktVS[$row['int_id']],$sku,$row['skumkt'],$cat,$prd['price'],$row['int_to']); // ATUALIZA O PRODUTO SE LOJA VIRTUAL = ML
					}
				
				} else {
					foreach ($retorno['produtos'] as $produto) {			
						$prd_ret = $produto[0]['produto'];
						var_dump($prd_ret);
						$SKU = $prd_ret['codigo'];
						$PRICE = $prd_ret['preco'];
						echo "=====>".$SKU.":".$PRICE."\n"; 
						if ($SKU!=$prd['sku']) {    // PULA O PAI DAS VARIANTES
							echo "VAI LINKAR VS\n";		
							$lnk = linkPrdVS ($me,$apikey,$mktVS[$row['int_id']],$SKU,$cat,$PRICE);			// INSERE OU ATUALIZA O PRODUTO NA LOJA VIRTUAL
							// atualiza a situação do produto se ele for novo 				
							
							
							// PRECISA REVER ESSA PARTE
							if (($row['int_to']=="ML") || ($row['int_to']=="MAGALU")) {
								if ($SKU == $sku) {
								  $lnk = zeraPrdVS ($me,$apikey,$mktVS[$row['int_id']],$SKU,$row['skumkt'],$cat,$PRICE,$row['int_to']);			// ATUALIZA O PRODUTO SE LOJA VIRTUAL = ML
								} else {
								  $sql = "SELECT * FROM ML_sku WHERE skubling = '".$SKU."'";
								  $cmd = $me->db->query($sql);
								  if($cmd->num_rows() > 0) {    // Existe um antigo
									  $skuml = $cmd->row_array();
									  $skuml = $skuml['skumkt'];
								  } else {
									  $skuml = "";
								  }				  
								  $lnk = zeraPrdVS ($me,$apikey,$mktVS[$row['int_id']],$SKU,$skuml,$cat,$PRICE);			// ATUALIZA O PRODUTO SE LOJA VIRTUAL = ML
								  $sql = "REPLACE into ML_sku VALUES ('".$SKU."','".$skuml."')";
								  $cmd = $me->db->query($sql);
								}								  		
							}
						}
					}
				}
				// TROUXE PRA DENTRO DO SUCESSO
				$sql = "UPDATE prd_to_integration SET status_int=2 , date_last_int = NOW() WHERE id = ".$row['id'];
				$cmd = $me->db->query($sql);
				
				if (($row['int_to']=="ML") || ($row['int_to']=="MAGALU")) {
					$xsku = $row['skumkt'];  // ERA BRANCO
				} else {
					$xsku = $sku;
				}	
				
				// rick Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
				$sql = "SELECT category_id FROM products WHERE id = ".$row['prd_id'];
				$cmd = $me->db->query($sql);
				$category_id_array = $cmd->row_array();  //Category_id esta como caracter no products
				$cat_id = json_decode ( $category_id_array['category_id']);
				$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
						 WHERE id =".intval($cat_id[0]).")";
				$cmd = $me->db->query($sql);
				$lido = $cmd->row_array();
				$tipo_volume_codigo= $lido['codigo'];
				echo 'SQL = '. $sql."\n";
				echo 'lido ='. print_r($lido,true)."\n";
				
				// rick Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
				$sql = "SELECT category_id FROM products WHERE id = ".$row_int_pro['prd_id'];
				
	        	$data = array(
	        		'int_to' => $row_int_pro['int_to'],
	        		'prd_id' => $row_int_pro['prd_id'],
	        		'EAN' => $row_int_pro['EAN'],
	        		'price' => $row_int_pro['price'],
	        		'qty' => $row_int_pro['qty'],
	        		'sku' => $row_int_pro['sku'],
	        		'mkt_store_id' => $mktVS[$row['int_id']],
	        		'NVL' => $row_int_pro['NVL'],
	        		'reputacao' => $row_int_pro['reputacao'],
	        		'company_id' => $row_int_pro['company_id'],                
	        		'data_ult_envio' => date('Y-m-d H:i:s'),
	        		'skubling' => $sku,
	        		'skumkt' => $sku,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $row_int_pro['qty_atual'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id']
					
	        	);
				$insert = $me->db->replace('bling_ult_envio', $data);
			}
			
	    }
        return "PRODUCTS Synced with BLING";
    } 

	function inserePrdBling ($me,$row,$apikey,$cat,$url,$skumkt,$int_to) {	
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
		$xml = new SimpleXMLElement($pruebaXml);
		$sku = $skumkt;
		$xml->codigo[0] = $sku;
		if ($int_to=="ML") {
			$xml->descricao[0] = htmlspecialchars(substr($row['name'],0,60), ENT_QUOTES, "utf-8");  // SO 60 chars por causa do ML
		} else {
			$xml->descricao[0] = htmlspecialchars($row['name'], ENT_QUOTES, "utf-8");
		}
		$xml->descricaoCurta[0] = htmlspecialchars($row['description'], ENT_QUOTES, "utf-8");

		$xml->vlr_unit[0] = $row['price'];
		$xml->peso_bruto[0] = $row['peso_bruto'];
		$xml->peso_liq[0] = $row['peso_liquido'];
		$brand_id = json_decode($row['brand_id']);
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $me->db->query($sql, $brand_id);
		$brand = $query->row_array();
		$xml->marca[0] = $brand['name'];
		$xml->estoque[0] = $row['qty'];
		$xml->gtin[0] = $row['EAN'];
		$xml->altura[0] = $row['altura'];
		$xml->largura[0] = $row['largura'];
		$xml->profundidade[0] = $row['profundidade'];
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
				$fotos = scandir(FCPATH . 'assets/images/product_image/' . $row['image']);	
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
            $prd_vars = $me->model_products->getProductVariants($row['id'],$row['has_variants']);
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
				$xml->variacoes->variacao[$i]->codigo =  $sku."-".$SKU;
				$xml->variacoes->variacao[$i]->vlr_unit =  $value['price']; 
				if ($int_to=="ML") {
					$xml->variacoes->variacao[$i]->clonarDadosPai = "N";
				} else {
					$xml->variacoes->variacao[$i]->clonarDadosPai = "S";
				}	
				$xml->variacoes->variacao[$i]->deposito->id = "3075788878";
				$xml->variacoes->variacao[$i]->deposito->estoque = $value['qty'];
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
		$retorno = executeInsertProduct($url, $posts);
		$retorno = json_decode($retorno,true);
		$retorno = $retorno['retorno'];
		if (array_key_exists('erros',$retorno)) {
			return $retorno;
		}
		
		
		//vejo se o produto já teve a categoria diferente da padrão associada
		$sql = "SELECT * FROM bling_ult_envio WHERE skumkt = '".$sku."'. AND categoria_bling is not null AND categoria_bling != '1122016'";
		$query = $me->db->query($sql);
		$rows = $query->result_array();
		if (count($rows) == 0 ) {
			// se não tem, não adianta mandar os campos customizados 
			return $retorno;
		}
        // Adiciona campos das categorias do ML 
		// leio os valores dos campos por produto 
		
		$me->load->model('model_atributos_categorias_marketplaces', 'myatributoscategorias');	
		$produtos_atributos = get_instance()->myatributoscategorias->getAllProdutosAtributos($row['id']);
		// vejo se tem campos customizados
		if (empty($produtos_atributos)) {
			return $retorno;
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
		$xml->descricaoCurta[0] = htmlspecialchars($row['description'], ENT_QUOTES, "utf-8");
			
		// $produtos_atributos =array();
		foreach ($produtos_atributos as $produto_atributo) {
			if ($criaChildXml) {
				$xml->addChild("camposCustomizados");
				// $xml->camposCustomizados->addChild('marca',$brand['name']);
				$criaChildXml=false; 
			}
			$id_atributo =  $produto_atributo['id_atributo']; 
			$valor = $produto_atributo['valor'];
			$atributo = get_instance()->myatributoscategorias->getAtributo($id_atributo);
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
		$retorno = executeUpdateProduct($url, $posts);
		$retorno = json_decode($retorno,true);
		$retorno = $retorno['retorno'];
		if (array_key_exists('erros',$retorno)) {
			return $retorno;
		}
		return $retorno;
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

	function insereCatBling ($me,$myid,$apikey,$cat,$idpai) {	
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<categorias></categorias>
XML;
		$xml = new SimpleXMLElement($pruebaXml);
		$n1 = simplexml_load_string('<categoria></categoria>');
		$tag = "descricao";	
		$n1->addchild($tag, htmlspecialchars(trim($cat), ENT_QUOTES, "utf-8"));
		$tag = "idcategoriapai";	
		$n1->addchild($tag,$idpai);
		sxml_append($xml, $n1);
		$dados = $xml->asXML();
		$url = 'https://bling.com.br/Api/v2/categoria/json/';
		$posts = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);
		$retorno = executeInsertCategory($url, $posts);
		$retorno = json_decode($retorno,true);
		$retorno = $retorno['retorno'];
		if (isset($retorno['erros'])) {
			$id = 0;
		    // tratar erro	
		} else {
			$id = $retorno['categorias'][0][0]['categoria']['id'];
			if ($myid > 0) {   // nao insere o pai
			$sql = "REPLACE INTO categories_mkts VALUES (".$myid.",13,'".$id."')";
			$query = $me->db->query($sql);
			} 
		}	
		return $id;
	}	

	
	function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from) {
	    $toDom = dom_import_simplexml($to);
	    $fromDom = dom_import_simplexml($from);
	    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
	}
     
    
	function executeGetCategories($url, $apikey){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey);
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

	function linkPrdVS ($me,$apikey,$store,$prdsku,$cat,$price) {	
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
		$retorno = executeInsertCategory($url, $posts);
		var_dump($retorno);
		return $retorno;
	}

	function getBlingKeys($me) {
		$apikeys = Array();
		$sql = "select * from stores_mkts_linked";
		$query = $me->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}	
		var_dump($apikeys);
		
		return $apikeys;
	}

	function zeraPrdVS ($me,$apikey,$store,$prdsku,$skumkt,$cat,$price,$int_to) {	
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
			get_instance()->log_data('batch','BlingProducts/zeraPrdVS', 'ERRO site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA BLING: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
		}

		if (array_key_exists('erros',json_decode($retorno,true))) {
			echo " URL: ". $url. "\n"; 
			echo " RESPOSTA BLING: ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($data,true)." \n"; 
			get_instance()->log_data('batch','BlingProducts/zeraPrdVS', 'ERRO site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA BLING: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
		}
		
	    curl_close($curl_handle); 
		return $retorno;
	}
	
	function executeGetProduct($url, $apikey,$loja){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey . "&loja=" . $loja);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}		
	
	function getMktSku($me) {
		
		// Pego primeiro os do ML e os MAgalu que são os críticos 
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$outputType = "json";
		//$sql = "SELECT * FROM bling_ult_envio WHERE int_to ='ML' OR int_to = 'MAGALU' ";
		$sql = "SELECT * FROM bling_ult_envio WHERE (int_to='ML' AND (skubling = skumkt OR skumkt ='00' OR skumkt is null)) OR (int_to='MAGALU' AND (skubling = skumkt OR skumkt ='00' OR skumkt is null))";
		// rick - alterar só para fazer para o ML e possíveis marketplace que tem o mesmo problema
		$query = $me->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling']; 
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			// echo "-------------------------------------\n";
			$retorno = executeGetProduct($url, $apikey,$row['mkt_store_id']);
			$linkedmkt = json_decode($retorno,true);
			
			if (isset($linkedmkt['retorno']['erros'])) {
				echo 'produto '.$code.' não encontrado no bling'."\n";	
				get_instance()->log_data('batch','BlingProducts/getMktSku','produto '.$code.' nao encontrado no bling',"E");
				continue;
			} else {
				echo 'trazendo produto '.$code."\n";
			}
			
			$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
			//var_dump($linkedmkt);
	 		if (isset($linkedmkt['produtoLoja'])) {
		 		$linkedmkt = $linkedmkt['produtoLoja'];
		 		$skumkt = $linkedmkt['idProdutoLoja'];
			} else {
				$skumkt = "";
			}
			// echo $code . " : " . $skumkt . "\n";
			$sql = "UPDATE bling_ult_envio set skumkt = '".$skumkt."' WHERE id = ".$row['id'];  
			$query = $me->db->query($sql);
			//echo $sql . "\n" ;
			$sql = "UPDATE prd_to_integration set skumkt = '".$skumkt."' WHERE int_to = '".$row['int_to']. "' AND prd_id = ".$row['prd_id'];  
			$query = $me->db->query($sql);
			//echo $sql . "\n" ;
			
			// TEM VARIANTS
			$code = $row['skubling'];  
			$sql = "SELECT * FROM ML_sku WHERE skubling like '".$code."%'";
			$query = $me->db->query($sql);
			$rowsml = $query->result_array();
			foreach ($rowsml as $ind => $rowml) {
				$url = 'https://bling.com.br/Api/v2/produto/' . $rowml['skubling'] . '/' . $outputType;
				// echo "-------------------------------------\n";
				$retorno = executeGetProduct($url, $apikey,$row['mkt_store_id']);
				$linkedmkt = json_decode($retorno,true);
				$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
				if (isset($linkedmkt['retorno']['erros'])) {
					echo 'produto '.$code.' não encontrado no bling'."\n";	
					get_instance()->log_data('batch','BlingProducts/getMktSku','produto '.$code.' nao encontrado no bling',"E");
					continue;
				} else {
					echo 'trazendo produto '.$code."\n";
				}
				// var_dump($linkedmkt);
		 		if (isset($linkedmkt['produtoLoja'])) {
			 		$linkedmkt = $linkedmkt['produtoLoja'];
			 		$skumkt = $linkedmkt['idProdutoLoja'];
				} else {
					$skumkt = "";
				}
				$sql = "UPDATE ML_sku set skumkt = '".$skumkt."' WHERE skubling = '".$rowml['skubling']."'";  
				$query = $me->db->query($sql);
			}	
		}	
	
		// Agora pego os que ainda não foram acertados no prd_to_integration
		$outputType = "json";
		$sql = "SELECT b.* FROM bling_ult_envio b left join prd_to_integration p on p.skubling = b.skubling where p.skumkt is null";
		$query = $me->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling'];  
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			// echo "-------------------------------------\n";
			$retorno = executeGetProduct($url, $apikey,$row['mkt_store_id']);
			$linkedmkt = json_decode($retorno,true);
			$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
			if (isset($linkedmkt['retorno']['erros'])) {
				echo 'produto '.$code.' não encontrado no bling'."\n";	
				get_instance()->log_data('batch','BlingProducts/getMktSku','produto '.$code.' nao encontrado no bling',"E");
				continue;
			} else {
				echo 'trazendo produto '.$code."\n";
			}
			// var_dump($linkedmkt);
	 		if (isset($linkedmkt['produtoLoja'])) {
		 		$linkedmkt = $linkedmkt['produtoLoja'];
		 		$skumkt = $linkedmkt['idProdutoLoja'];
			} else {
				$skumkt = null;
			}
			// echo $code . " : " . $skumkt . "\n";
			$sql = "UPDATE bling_ult_envio set skumkt = '".$skumkt."' WHERE id = ".$row['id'];  
			$query = $me->db->query($sql);
			$sql = "UPDATE prd_to_integration set skumkt = '".$skumkt."' WHERE int_to = '".$row['int_to']. "' AND prd_id = ".$row['prd_id'];  
			$query = $me->db->query($sql);
			
		}	
	}

	function getMktSkuTodos($me) {
		// Não é usada no momento. Irá sincronizar todos os produtos
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$outputType = "json";
		$sql = "SELECT * FROM bling_ult_envio";
		$query = $me->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling'];  
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			// echo "-------------------------------------\n";
			$retorno = executeGetProduct($url, $apikey,$row['mkt_store_id']);
			$linkedmkt = json_decode($retorno,true);
			$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
			// var_dump($linkedmkt);
	 		if (isset($linkedmkt['produtoLoja'])) {
		 		$linkedmkt = $linkedmkt['produtoLoja'];
		 		$skumkt = $linkedmkt['idProdutoLoja'];
			} else {
				$skumkt = "NONE";
			}
			// echo $code . " : " . $skumkt . "\n";
			$sql = "UPDATE bling_ult_envio set skumkt = '".$skumkt."' WHERE id = ".$row['id'];  
			$query = $me->db->query($sql);
			$sql = "UPDATE prd_to_integration set skumkt = '".$skumkt."' WHERE int_to = '".$row['int_to']. "' AND prd_id = ".$row['prd_id'];  
			$query = $me->db->query($sql);
		}	
	}


	function getCategoriaProdutos($me) {
		// Pega somente a categoria do produtos - Rodar a cada 15 minutos 
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$outputType = "json";
		$sql = "SELECT * FROM bling_ult_envio WHERE categoria_bling is null OR categoria_bling = '1122016'";
		$query = $me->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling'];  
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			// echo "-------------------------------------\n";
			$retorno = executeGetProduct($url, $apikey,$row['mkt_store_id']);
			$linkedmkt = json_decode($retorno,true);
			$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
			// var_dump($linkedmkt);
	 		if (!(isset($linkedmkt['categoria']))) {
	 			echo $code." sem categoria\n";
	 			continue;
	 		}
	 		$cat_id = $linkedmkt['categoria']['id'];
	 		$cat_desc = $linkedmkt['categoria']['descricao'];
			
			if ($cat_id == $row['categoria_bling']) {
				// Nada a alterar pois ninguem linkou o produto no Bling 
				continue;
			}
			// pode não ser mais nulo, então altero o bling
	 		echo $code . " : " . $cat_id . '-'. $cat_desc. "\n";
			$sql = "UPDATE bling_ult_envio set categoria_bling = '".$cat_id."' WHERE id = ".$row['id'];  
			$query = $me->db->query($sql);
			
			if ($cat_id =='1122016') {
				// categoria padrão
				continue;
			}
			// Alterou a categoria. Vejo se tem campos customizados aguardando para serem enviados
		    // Adiciona campos das categorias do ML 
		    // leio os valores dos campos por produto 
		
			$me->load->model('model_atributos_categorias_marketplaces', 'myatributoscategorias');
			$me->load->model('model_products', 'myproducts');		
			$produtos_atributos = get_instance()->myatributoscategorias->getAllProdutosAtributos($row['prd_id']);
			// vejo se tem campos customizados
			if (empty($produtos_atributos)) {
				// Sem nenhum campo 	
				continue;
			}
			$criaChildXml = true;
			
			$produto = get_instance()->myproducts->getProductData(0,$row['prd_id']); 
		
			$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
</produto>
XML;
			$xml = new SimpleXMLElement($pruebaXml);
			$xml->codigo[0] = $row['skubling'];
			if ($row['int_to']=="ML") {
				$xml->descricao[0] = htmlspecialchars(substr($produto['name'],0,60), ENT_QUOTES, "utf-8");  // SO 60 chars por causa do ML
			} else {
				$xml->descricao[0] = htmlspecialchars($produto['name'], ENT_QUOTES, "utf-8");
			}
			$xml->descricaoCurta[0] = htmlspecialchars($produto['description'], ENT_QUOTES, "utf-8");
				
			// $produtos_atributos =array();
			foreach ($produtos_atributos as $produto_atributo) {
				if ($criaChildXml) {
					$xml->addChild("camposCustomizados");
					// $xml->camposCustomizados->addChild('marca',$brand['name']);
					$criaChildXml=false; 
				}
				$id_atributo =  $produto_atributo['id_atributo']; 
				$valor = $produto_atributo['valor'];
				$atributo = get_instance()->myatributoscategorias->getAtributo($id_atributo);
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
			$url = 'https://bling.com.br/Api/v2/produto/'.$row['skubling'].'/json/';
			$posts = array (
			    "apikey" => $apikey,
			    "xml" => rawurlencode($dados)
			);
			echo "**************** VAI MANDAR BLING ***************\n";
			$retorno = executeUpdateProduct($url, $posts);
			$retorno = json_decode($retorno,true);
			$retorno = $retorno['retorno'];
			if (array_key_exists('erros',$retorno)) {
				echo "Erro na respota do bling\n";
				echo " URL: ". $url. "\n"; 
				echo " RESPOSTA BLING: ".print_r($retorno,true)." \n"; 
				echo " Dados enviados: ".print_r($posts,true)." \n"; 
				get_instance()->log_data('batch','BlingProducts/getCategoriaProdutos', 'ERRO [site:'.$url.' RESPOSTA BLING: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($posts,true),"E");
				
				// Volto a tras a categoria padrão para que tente enviar de novo
				$sql = "UPDATE bling_ult_envio set categoria_bling = '1122016' WHERE id = ".$row['id'];  
				$query = $me->db->query($sql);
			}

		}	
	}

	/* ********************************************
		Não pode deletar do BLING pq ele não sincroniza 
		a exclusão, zerando o estoque o produto fica 
		inativo no bling e nos marketplaces
	********************************************  */
	
	function executeDeleteProduct($url,$apikey,$code, $descricao) {
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
			get_instance()->log_data('batch','BlingProducts/executeDeleteProduct', 'ERRO [cria site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA BLING: '.print_r($response,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
		}
		curl_close($curl_handle);
	    return $httpcode;
	}


?>