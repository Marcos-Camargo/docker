<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   
class CarLimpaOfertas extends BatchBackground_Controller {
	
	var $int_to='CAR';
	var $apikey='';
	var $site='';
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_integrations');
		$this->load->model('model_frete_ocorrencias');
		$this->load->model('model_promotions');
		
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
		$this->getkeys(1,0);
		// usado uma vez para limpar as ofertas perdidas do BLING 
		// faça um backup do bling_ult_envio antes pois irá remover os produtos errados dela. 
		// poderá ser usado novamente para sincronizar com as ofertas validas do carrefour 
		// para isso deve ser baixado o arquivo de ofertas atuais e colocado no diretorio de importação 
		// com o nome offers.csv e descomentar a linha abaixo 
		$this->achaFaltosos();
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
	
	function breakLines($texto)
	{
		$result = array();
		$linha_header = substr($texto,0,strpos($texto,PHP_EOL));
		//echo "linha hdr  = ", $linha_header."\n";
		$header = str_getcsv($linha_header,";");
		$texto = substr($texto,strpos($texto,PHP_EOL)+1);
		$i=0;
		while ($texto!="") {
			if (strpos($texto,PHP_EOL) === false) {
				$line = $texto;
				$texto = '';
			}
			else {
				$line= substr($texto,0,strpos($texto,PHP_EOL));
				$texto = substr($texto,strpos($texto,PHP_EOL)+1);
			}
			$result[]= array_combine($header,str_getcsv($line,";"));
			//echo "linha ".$i." = ", $result[$i++]."\n";
			
		}
		return ($result);
	}
	
	function achaFaltosos()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		
		$myfile = fopen("/var/www/html/app/importacao/offers.csv", "r") or die("Unable to open file!");
		$lido = fread($myfile,filesize("/var/www/html/app/importacao/offers.csv"));
		fclose($myfile);
		
		$matar = array();
		$zerar = array();
		$lines= $this->breakLines($lido);
		foreach($lines as $line) {
			$sku = $line['SKU da oferta'];
			//echo $sku."\n";
			$sql = 'SELECT * FROM bling_ult_envio WHERE int_to="CAR" AND skubling = ?';
			$query = $this->db->query($sql, array($sku));
			$row_ult = $query->row_array();
			if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
				$variant = null;
			}
			else
			{
				$variant = '';
			}

			if (is_null($row_ult)) {
				//pode ser produto com variação
				if (strrpos($sku, '-') !=0) {
					$sku = substr($line['SKU da oferta'], 0, strrpos($line['SKU da oferta'], '-'));
					$variant = substr($line['SKU da oferta'], strrpos($line['SKU da oferta'], '-')+1);
					$sql = 'SELECT * FROM (bling_ult_envio use index (int_skubling)) WHERE int_to ="CAR" and skubling = ?';
					$query = $this->db->query($sql, array($sku));
					$row_ult = $query->row_array();
				}
				if (is_null($row_ult)) {
					$sql = 'SELECT * FROM prd_to_integration WHERE int_to="CAR" AND skubling = ?';
					$query = $this->db->query($sql, array($sku));
					$row_ult = $query->row_array();
					if (is_null($row_ult)) {
						if (strrpos($sku, '-') !=0) {
							$sku = substr($line['SKU da oferta'], 0, strrpos($line['SKU da oferta'], '-'));
							$variant = substr($line['SKU da oferta'], strrpos($line['SKU da oferta'], '-')+1);
							$sql = 'SELECT * FROM (prd_to_integration WHERE int_to ="CAR" and skubling = ?';
							$query = $this->db->query($sql, array($sku));
							$row_ult = $query->row_array();
						}
						if (is_null($row_ult)) {
							//produto não existe
							$matar[] = $line['SKU da oferta'];					
							continue;	
						}
					}
					echo "Achei no prd_to_integration $sku\n";
					//var_dump($row_ult);

					$prd_to = $row_ult;
					$sql = "SELECT * FROM products WHERE id = ".$prd_to['prd_id'];
					$cmd = $this->db->query($sql);
					$prd = $cmd->row_array();

					if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
						$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"CAR", $variant);
					}
					else
					{
						$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"CAR");
					}

					//$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
					$ean = $prd['EAN']; 
					if ($ean == '') {
						if ($prd['is_kit'] == 0) {
							$ean = 'NO_EAN'.$prd['id'];
						}else {
							$ean = 'IS_KIT'.$prd['id'];
						}
					}
					$zerar[] = array (
								'sku' => $line['SKU da oferta'],
								'price' =>$prd['price'],
								);	
					
					$cat_id = json_decode ( $prd['category_id']);
					$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
							 WHERE id =".intval($cat_id[0]).")";
					$cmd = $this->db->query($sql);
					$tipo_volume_codigo = $cmd->row_array();
					$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
					
					$bling = array(
						'int_to' => 'CAR',
						'company_id' => $prd['company_id'],
						'EAN'=> $ean,
						'prd_id'=> $prd_to['prd_id'], 
						'price'=> $prd['price'],
						'qty'=> 0,
						'sku'=> $prd['sku'],
						'reputacao'=> 100,
						'NVL'=> 0,
						'mkt_store_id'=> 0,
						'data_ult_envio'=> '',
						'skubling'=> $sku,
						'skumkt'=> $sku,
						'tipo_volume_codigo'=> $tipo_volume_codigo['codigo'],
						'qty_atual'=> 0,
						'largura'=> $prd['largura'],
						'altura'=> $prd['altura'],
						'profundidade'=>$prd['profundidade'],
						'peso_bruto'=>$prd['peso_bruto'],
						'store_id'=> $prd['store_id'],
						'marca_int_bling'=> null,
						'categoria_bling'=> "NOVA",
						'crossdocking' => $crossdocking
					);
					if ($prd_to['status'] == 0) {
						$status_int=90;
					}else {
						$status_int=2;
					}
					$sql = "UPDATE prd_to_integration SET status_int=".$status_int.", skumkt = '".$sku."'  WHERE skubling = '".$sku."' AND int_to='CAR'";
					$cmd = $this->db->query($sql);
					// insiro no bling_ult_envio para que o produto deixe de ser novo começar a receber a carga de ofertas. 
					$insert = $this->db->replace('bling_ult_envio', $bling);
				}
				else {
					$sql = 'UPDATE bling_ult_envio SET categoria_bling="NOVA" WHERE id=?' ;
					$query = $this->db->query($sql, array($row_ult['id']));
				}
			}
			else {
				$sql = 'UPDATE bling_ult_envio SET categoria_bling="NOVA" WHERE id=?' ;
				$query = $this->db->query($sql, array($row_ult['id']));
			}
 		}
		
		$sql = 'DELETE FROM bling_ult_envio WHERE categoria_bling!="NOVA" AND int_to ="CAR"' ;
		// $query = $this->db->query($sql);
		
		if ((count($matar)==0) && (count($zerar)==0)) {
			return ;
		}
		$store_id = 0; 
		$table_carga = "carrefour_carga_ofertas_".$store_id;
		if ($this->db->table_exists($table_carga) ) {
			$this->db->query("TRUNCATE $table_carga");
		} else {
			$model_table = "carrefour_carga_ofertas_model";
			$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
		}
		foreach($zerar as $sku) {
			$oferta = array(
    			'sku' => $sku['sku'],
    			'product_id' => $sku['sku'],
    			'product_id_type' => "SHOP_SKU",
    			'description' => '',
    			'internal_description' => '',
    			'price' => $sku['price'], 
    			'quantity' => 0,
    			'state' => '11',
    			'update-delete' => 'update'
    		);
			$insert = $this->db->insert($table_carga, $oferta);
		}
		foreach($matar as $sku) {
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
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_APAGA_".$store_id.".csv";
		
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
				sleep(60);
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
}
?>
