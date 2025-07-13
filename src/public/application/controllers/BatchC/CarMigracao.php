<?php
/*
 
Migra as ofertas para o modelo novo alterando a prd_to_integration e bling_ult_envio para ter variação. 

*/   
class CarMigracao extends BatchBackground_Controller {
	
	var $int_to='CAR';
	var $apikey='';
	var $site='';
	
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
		$this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_promotions');
		$this->load->model('model_products');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_errors_transformation'); 	
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_car_ult_envio');
		
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
		
		/* faz o que o job precisa fazer */
		// usado uma vez para limpar as ofertas perdidas do BLING 
		// faça um backup do bling_ult_envio antes pois irá remover os produtos errados dela. 
		// poderá ser usado novamente para sincronizar com as ofertas validas do carrefour 
		// para isso deve ser baixado o arquivo de ofertas atuais e colocado no diretorio de importação 
		// com o nome offers.csv e descomentar a linha abaixo 
		$this->migra();
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function migra()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		
		$myfile = fopen("/var/www/html/app/importacao/migra.csv", "r") or die("Unable to open file!");
		
		$matarbling = array();
		$matarprdto = array();
		$matar = array();
		$fila = array();
		$cnt = 0;
		//$lines= $this->breakLines($lido);
		//foreach($lines as $line) {
		$header = fgets($myfile);
		while (($line = fgets($myfile)) !== false) {
			$cnt++;
			//if ($cnt >50) { break;}
			$linha = explode(';',$line) ; 
			$sku = $linha[0];
			echo $cnt.': ';
			if (strrpos($sku, '-') !=0) { // produto com variação
				echo $sku." com variação ".$linha[18]."\n";
				$skumkt = substr($linha[0], 0, strrpos($linha[0], '-'));
				$variant = substr($linha[0], strrpos($linha[0], '-')+1);
				
				// procuro na bling para ver se já está ok
				$row_ult = $this->model_blingultenvio->getDataBySkyblingAndIntto($sku,$this->int_to);
				if ($row_ult) {
					echo "Já migrado\n";
					continue; 	
				}
				
				// procuro o sku do pai na bling_ult_envio
				$row_ult = $this->model_blingultenvio->getDataBySkyblingAndIntto($skumkt,$this->int_to);
				if (!$row_ult) {
					echo "Removida base \n";
					$matar[] = $sku;
					continue;	
				}
	 
				if (!is_null($row_ult['variant'])) {
					echo "algo errado neste produto pois tem variação cadastrada na bling_ult_envio\n";
					$matar[] = $sku;
					$limpa = true;
				}
				$prd = $this->model_products->getProductData(0,$row_ult['prd_id']);
				$variants = $this->model_products->getVariants($prd['id']);
				$prd = $this->preparaProduto($prd);
				
				if ($prd['has_variants'] == '') {
					echo "algo errado neste produto pois não tem variação cadastrada no produto ".$row_ult['prd_id']."\n";
					$matar[] = $sku;
					$limpa = true;
				}
				
				if (!in_array($row_ult['id'],$matarbling)) { // adiciono este registo para ser removido que é o registro sem variação
					$matarbling[]= $row_ult['id'];
				}
				
				// agora acerto bling_ult_envio 
				if (!$limpa) {
					if (key_exists($variant, $variants)) {
						$this->updateBling($prd,$sku,$skumkt,$variants[$variant]);
						$this->updateCARUltEnvio($prd,$sku,$skumkt,$variants[$variant]);
					}
					else{
						echo "Não existe a variacao ".$variant." no peoduto ".$prd['id']."\n";
						$matar[] = $sku;
						continue;
					}
					
				}
				
				// verifico a prd_to_integration 
				$prds_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndInttoMulti($sku, $this->getInt_to());
				if (count($prds_to)>0) {
					echo "Já migrado\n";
					continue; 	
				}
				
				// pego todos com este sku sem variação
				$prds_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndInttoMulti($skumkt, $this->getInt_to());
				foreach($prds_to as $prd_to) {
					if ($prd_to['prd_id'] == $row_ult['prd_id']) {
						if (!in_array($prd_to['id'],$matarprdto)) { // adiciono este registo para ser removido que é o registro sem variação
							$matarprdto[]= $prd_to['id']; 
						}
						if ($limpa) { // produto sem variacao marcardo como se tivesse variação
							$sku  = null;
							$skumkt = null;
							$variant = null; 
							$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
						}
						echo "Criando o registro novo com variação\n";
						$prd_upd = $prd_to;
						$prd_upd['id'] = 0;
						$prd_upd['skubling'] = $sku;
						$prd_upd['skumkt'] = $skumkt;
						$prd_upd['variant'] = $variant;
						$prd_upd['ad_link'] = 'https://www.carrefour.com.br/p/'.$linha[1];
						$this->model_integrations->createPrdToIntegration($prd_upd);

						if (!in_array($prd['id'],$fila)) {
							$fila[] = $prd['id'];
						}
						
					}
					else {
						echo " Alterando prd_to_integration ".$prd_to['id'].' do produto '.$prd_to['prd_id'].' para remover o SKU '.$sku.' pois era perdedor do leilão'."\n"; 
						$prd_upd = array (
							'skubling' 		=> null,
							'skumkt' 		=> null,
							'ad_link' 		=> null,
							);
						$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);
						if (!in_array($prd_to['prd_id'],$fila)) {
							$fila[] = $prd_to['prd_id'];
						}
						$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
					}
				}
			}
			else { // produto sem variação
				echo $sku." sem variação ".$linha[18]."\n";
				$row_ult = $this->model_blingultenvio->getDataBySkyblingAndIntto($sku,$this->int_to);
				if (!$row_ult) {
					echo "Removida base \n";
					$matar[] = $sku;
					continue;	
				}
				$limpa = false;
				if (!is_null($row_ult['variant'])) {
					echo "algo errado neste produto pois tem variação cadastrada na bling_ult_envio\n";
					$matar[] = $sku;
					if (!in_array($row_ult['id'],$matarbling)) { // adiciono este registo para remover pois está inconsistente com o gravado no marketplace
						$matarbling[]= $row_ult['id'];
					}
					$limpa = true;
				}
				$prd = $this->model_products->getProductData(0,$row_ult['prd_id']);
				$prd = $this->preparaProduto($prd);
				if ($prd['has_variants'] !='') {
					echo "algo errado neste produto pois tem variação cadastrada no produto ".$row_ult['prd_id']."\n";
					$matar[] = $sku;
					if (!in_array($row_ult['id'],$matarbling)) { // adiciono este registo para remover pois está inconsistente com o gravado no marketplace
						$matarbling[]= $row_ult['id'];
					}
					$limpa = true;
				}
				$prds_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndInttoMulti($sku, $this->getInt_to());
				foreach($prds_to as $prd_to) {
					if ($prd_to['prd_id'] == $row_ult['prd_id']) {
						// este é o registro que continuará 
						if ($limpa) {
							echo " Alterando prd_to_integration ".$prd_to['id'].' do produto '.$prd_to['prd_id'].' para remover o SKU '.$sku.' pois está inconsistente com o produto'."\n"; 
							$prd_upd = array (
								'skubling' 		=> null,
								'skumkt' 		=> null,
								'ad_link' 		=> null,
								);
							$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);
							if (!in_array($prd_to['prd_id'],$fila)) {
								$fila[] = $prd_to['prd_id'];
							}
							// Limpar erros de transformação antigos
							$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
							
						}
						else {
							$this->updateCARUltEnvio($prd,$sku,$sku,null);
							echo 'Tudo Ok '."\n";
						} 
					}
					else {
						echo " Alterando prd_to_integration ".$prd_to['id'].' do produto '.$prd_to['prd_id'].' para remover o SKU '.$sku.' pois era perdedor do leilão'."\n"; 
						$prd_upd = array (
							'skubling' 		=> null,
							'skumkt' 		=> null,
							);
						$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);
						if (!in_array($prd_to['prd_id'],$fila)) {
							$fila[] = $prd_to['prd_id'];
						}
						$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
					}
				}
				
			}	
		}
		fclose($myfile);
		
		echo 'Processou :'.$cnt." ofertas\n";
		echo "Removendo da prd_to_integration: ".count($matarprdto)."\n";
		foreach($matarprdto as $id) {
			$this->model_integrations->removePrdToIntegration($id);
		}
		echo "Removendo da bling_ult_envio: ".count($matarbling)."\n";
		foreach($matarbling as $id) {
			$this->model_blingultenvio->remove($id);
		}
		
		echo 'Esses deveria remover e mandar de novo'."\n";
		echo "Removendo da Carrefour: ".count($matar)."\n";
		$this->killOffers($matar);
		
		$data = array (
			'id'     => 0, 
			'status' => 0,
			'prd_id' => 0,
			'int_to' => $this->getInt_to()
		);
		
		echo "Re-enviando para o Carrefour: ".count($fila)."\n";
		foreach($fila as $prd_id) {
			$data['prd_id'] = $prd_id;
			$this->model_queue_products_marketplace->create($data);	
		}
		
		
	}

	function killOffers($matar) 
	{
		$store_id = 0; 
		$table_carga = "carrefour_carga_ofertas_migracao_".$store_id;
		if ($this->db->table_exists($table_carga) ) {
			$this->db->query("TRUNCATE $table_carga");
		} else {
			$model_table = "carrefour_carga_ofertas_model";
			$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
		}
		 
		foreach($matar as $sku) {
			echo " Incluindo para remover ". $sku. "\n";
			$oferta = array(
    			'sku' => $sku,
    			'product_id' => $sku,
    			'product_id_type' => "SHOP_SKU",
    			'description' => '',
    			'internal_description' => '',
    			'price' => 0, 
    			'quantity' => 0,
    			'state' => '11',
    			'update-delete' => 'delete'
    		);
			$insert = $this->db->insert($table_carga, $oferta);
		}
		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_MIGRACAO_ERROS_".$store_id.".csv";
		
		$sql = "SELECT * FROM ".$table_carga;
		$query = $this->db->query($sql);
		$products = $query->result_array();
		if (count($products) ==0 ) {
			return;
		}
		$myfile = fopen($file_prod, "w") or die("Unable to open file!");
		$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
						'state','update-delete'); 
	
		fputcsv($myfile, $header, ";");
		foreach($products as $prdcsv) {
			fputcsv($myfile, $prdcsv, ";");
		}
		fclose($myfile);
		
		echo "Arquivo: ".$file_prod."\n";
		
		$url = 'https://'.$this->getSite().'/api/offers/imports';
		echo "chamando ".$url." \n";
		echo "file: ". $file_prod."\n";
		
		$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
		if ($retorno['httpcode'] != 201) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true),"E");
			return false;
		}
		//var_dump($retorno['content']);
		$resp = json_decode($retorno['content'],true);
		$import_id= $resp['import_id'];

		
	}

	function preparaProduto($prd) {
		
		$cat_id = json_decode($prd['category_id']);
		$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
				 WHERE id =".intval($cat_id[0]).")";
		$cmd = $this->db->query($sql);
		$tipo_volume_codigo = $cmd->row_array();
		$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
		$loja  = $this->model_stores->getStoresData($prd['store_id']);
	
		$prd['preco'] = $this->getPrice($prd,null);
		$prd['loja'] = $loja;
		$prd['crossdocking'] = $crossdocking;
		$prd['tipo_volume_codigo'] = $tipo_volume_codigo['codigo'];
		return $prd; 
	}

	function updateBling($prd,$skubling,$skumkt,$variant = null) {
		
		if ($prd['is_kit'] == 1) {
			$ean ='IS_KIT'.$prd['id'];
		}
		else {
			$ean ='NO_EAN'.$prd['id']; 
		}
		$sku_prd = $prd['sku'];
		if (!is_null($variant)) {
			$ean = $ean.'V'.$variant['variant'];
			$sku_prd = $variant['sku'];
		}
		
		$bling = array(
			'int_to' => $this->int_to,
			'company_id' => $prd['company_id'],
			'EAN'=> $ean,
			'prd_id'=> $prd['id'], 
			'price'=> $prd['preco'],
			'qty'=> 0,
			'sku'=> $sku_prd,  
			'reputacao'=> 100,
			'NVL'=> 0,
			'mkt_store_id'=> 0,
			'data_ult_envio'=> '',
			'skubling'=> $skubling,
			'skumkt'=> $skumkt,
			'tipo_volume_codigo'=> $prd['tipo_volume_codigo'],
			'qty_atual'=> 0,
			'largura'=> $prd['largura'],
			'altura'=> $prd['altura'],
			'profundidade'=>$prd['profundidade'],
			'peso_bruto'=>$prd['peso_bruto'],
			'store_id'=> $prd['store_id'],
			'marca_int_bling'=> null,
			'categoria_bling'=> null,
			'crossdocking' => $prd['crossdocking'], 
			'CNPJ' => preg_replace('/\D/', '', $prd['loja']['CNPJ']),
        	'zipcode' => preg_replace('/\D/', '', $prd['loja']['zipcode']),
        	'freight_seller' =>  $prd['loja']['freight_seller'],
			'freight_seller_end_point' => $prd['loja']['freight_seller_end_point'],
			'freight_seller_type' => $prd['loja']['freight_seller_type'],
			'variant' => is_null($variant) ? null : $variant['variant'],
		);
		
		// insiro no bling_ult_envio para que o produto deixe de ser novo começar a receber a carga de ofertas. 
		$savedUltEnvio= $this->model_blingultenvio->createIfNotExist($ean, $this->int_to, $bling); 
	} 

	function updateCARUltEnvio($prd, $skubling, $skumkt, $variant = null) 
	{
		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];
		$sku_prd = $prd['sku'];
		
		if ($prd['is_kit'] == 1) {
			$ean ='IS_KIT'.$prd['id'];
		}
		else {
			$ean ='NO_EAN'.$prd['id']; 
		}
		if (!is_null($variant_num)) {
			$ean = $ean.'V'.$variant_num;
			$sku_prd = $variant['sku'];
		}

    	$data = array(
    		'int_to' => $this->int_to,
    		'prd_id' => $prd['id'],
    		'variant' => $variant_num,
    		'company_id' => $prd['company_id'],
    		'store_id' => $prd['store_id'], 
    		'EAN' => $ean,
    		'price' => $prd['preco'],
    		'qty' => 0,
    		'qty_total' => 0,
    		'sku' => $sku_prd,
    		'skulocal' => $skubling,
    		'skumkt' => $skumkt,     
    		'date_last_sent' => date('Y-m-d H:i:s'),
    		'tipo_volume_codigo' => $prd['tipo_volume_codigo'], 
    		'width' => $prd['largura'],
    		'height' => $prd['altura'],
    		'length' => $prd['profundidade'],
    		'gross_weight' => $prd['peso_bruto'],
    		'crossdocking' => $prd['crossdocking'], 
    		'zipcode' => preg_replace('/\D/', '', $prd['loja']['zipcode']), 
    		'CNPJ' => preg_replace('/\D/', '', $prd['loja']['CNPJ']),
    		'freight_seller' => $prd['loja']['freight_seller'],
			'freight_seller_end_point' => $prd['loja']['freight_seller_end_point'],
			'freight_seller_type' => $prd['loja']['freight_seller_type'],
    	);
		
		$savedUltEnvio =$this->model_car_ult_envio->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela car_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 
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

	public function getPrice($prd, $variant = null) 
	{
		$new_price = round($prd['price'],2);
		// pego o preço da variant 
		if (!is_null($variant)) {
			if ((float)trim($variant['price']) > 0) {
				$new_price = round($variant['price'],2);
			}
		}
		// altero o preço para acertar o DE POR do marketplace. Tem precedencia em relação ao preço por variação 
		$new_price  =  $this->model_products_marketplace->getPriceProduct($prd['id'],$new_price,$this->getInt_to(), $prd['has_variants']);

		// Pego o preço a ser praticado se tem promotion. Tem precedencia em relação ao preço por variação

		if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
			$price = $this->model_promotions->getPriceProduct($prd['id'],$new_price,$this->getInt_to(), $variant);
		}
		else
		{
			$price = $this->model_promotions->getPriceProduct($prd['id'],$new_price,$this->getInt_to());
		}


		return round($price,2);
	}
}
?>
