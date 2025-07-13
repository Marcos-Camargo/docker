<?php
/*
 
 Sincroniza os SKUs do Bling (ML e Magalu)

*/   
class BlingSyncSku extends BatchBackground_Controller {
		
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
		$this->load->model('model_products');
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
		$retorno = $this->getMktSku();

		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getBlingKeys() {
		$apikeys = Array();
		$sql = "select * from stores_mkts_linked where id_integration = 13";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}
		return $apikeys;
	}
	
	function executeGetProduct($url, $apikey,$loja){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey . "&loja=" . $loja);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}		
	
	function getMktSku() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$apikeys = $this->getBlingKeys();
		$not_int_to = " int_to!='B2W' AND int_to!='CAR' AND";
		$not_int_to = " int_to='VIA' AND"; // agora só VIA 
		// Pego primeiro os do ML e os MAgalu que são os críticos 
		// $apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$outputType = "json";
		//$sql = "SELECT * FROM bling_ult_envio WHERE int_to ='ML' OR int_to = 'MAGALU' ";
		$sql = "SELECT * FROM bling_ult_envio WHERE ".$not_int_to."(int_to='ML' AND (skubling = skumkt OR skumkt ='00' OR skumkt is null OR skumkt ='')) OR (int_to='MAGALU' AND (skubling = skumkt OR skumkt ='00' OR skumkt is null ))";
		// rick - alterar só para fazer para o ML e possíveis marketplace que tem o mesmo problema
		$query = $this->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling']; 
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			// echo "-------------------------------------\n";
			
			$apikey = $apikeys[$row['int_to']];

			$retorno = $this->executeGetProduct($url, $apikey,$row['mkt_store_id']);
			$linkedmkt = json_decode($retorno,true);	
			if (isset($linkedmkt['retorno']['erros'])) {
				echo 'produto '.$code.' id='.$row['prd_id'].' não encontrado no bling '.$row['int_to']."\n";	
				$this->log_data('batch',$log_name,'produto '.$code.' id='.$row['prd_id'].' nao encontrado no bling '.$row['int_to'],"E");
				continue;
			} else {
				// echo 'trazendo produto '.$code."\n";
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
			$query = $this->db->query($sql);
			//echo $sql . "\n" ;
			$sql = "UPDATE prd_to_integration set skumkt = '".$skumkt."' WHERE int_to = '".$row['int_to']. "' AND prd_id = ".$row['prd_id'];  
			$query = $this->db->query($sql);
			//echo $sql . "\n" ;
			
			// TEM VARIANTS
			$code = $row['skubling'];  
			$sql = "SELECT * FROM ML_sku WHERE skubling like '".$code."%'";
			$query = $this->db->query($sql);
			$rowsml = $query->result_array();
			foreach ($rowsml as $ind => $rowml) {
				$url = 'https://bling.com.br/Api/v2/produto/' . $rowml['skubling'] . '/' . $outputType;
				// echo "-------------------------------------\n";
				$retorno = $this->executeGetProduct($url, $apikey,$row['mkt_store_id']);
				$linkedmkt = json_decode($retorno,true);
				if (isset($linkedmkt['retorno']['erros'])) {
					echo 'produto variante '.$code.' id='.$row['prd_id'].' não encontrado no bling '.$row['int_to']."\n";	
					$this->log_data('batch',$log_name,'produto '.$code.' id='.$row['prd_id'].' nao encontrado no bling '.$row['int_to'],"E");
					continue;
				} else {
					// echo 'trazendo produto '.$code."\n";
				}
				$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
				// var_dump($linkedmkt);
		 		if (isset($linkedmkt['produtoLoja'])) {
			 		$linkedmkt = $linkedmkt['produtoLoja'];
			 		$skumkt = $linkedmkt['idProdutoLoja'];
				} else {
					$skumkt = "";
				}
				$sql = "UPDATE ML_sku set skumkt = '".$skumkt."' WHERE skubling = '".$rowml['skubling']."'";  
				$query = $this->db->query($sql);
			}	
		}	
		
		$not_int_to = " p.int_to!='B2W' AND p.int_to!='CAR' AND ";
		$not_int_to = " p.int_to='VIA' AND "; // agora só VIA 
		// Agora pego os que ainda não foram acertados no prd_to_integration
		$outputType = "json";
		$sql = "SELECT b.* FROM bling_ult_envio b left join prd_to_integration p on p.skubling = b.skubling where ".$not_int_to." p.skumkt is null or b.skumkt = '' or p.skumkt = ''";
		$query = $this->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling'];  
			echo 'trazendo produto '.$code."\n";
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			
			$apikey = $apikeys[$row['int_to']];
			// echo "-------------------------------------\n";
			$retorno = $this->executeGetProduct($url, $apikey,$row['mkt_store_id']);
			$linkedmkt = json_decode($retorno,true);
			if (isset($linkedmkt['retorno']['erros'])) {
				echo 'produto '.$code.' id='.$row['prd_id'].' não encontrado no bling '.$row['int_to']."\n";	
				$this->log_data('batch',$log_name,'produto '.$code.' id='.$row['prd_id'].' nao encontrado no bling '.$row['int_to'],"E");
				continue;
			} else {
				// echo 'trazendo produto '.$code."\n";
			}
			$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
			// var_dump($linkedmkt);
	 		if (isset($linkedmkt['produtoLoja'])) {
		 		$linkedmkt = $linkedmkt['produtoLoja'];
		 		$skumkt = $linkedmkt['idProdutoLoja'];
			} else {
				$skumkt = null;
			}
			// echo $code . " : " . $skumkt . "\n";
			$sql = "UPDATE bling_ult_envio set skumkt = '".$skumkt."' WHERE id = ".$row['id'];  
			$query = $this->db->query($sql);
			$sql = "UPDATE prd_to_integration set skumkt = '".$skumkt."' WHERE int_to = '".$row['int_to']. "' AND prd_id = ".$row['prd_id'];  
			$query = $this->db->query($sql);
			
		}	
	}

	function getMktSkuTodos() {
		// Não é usada no momento. Irá sincronizar todos os produtos
		//$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$apikeys = $this->getBlingKeys();
		
		$not_int_to = " WHERE int_to != 'B2W' AND int_to != 'CAR' ";  // Não trago mais a B2W e CAR
		$not_int_to = " WHERE int_to = 'VIA' ";  // Agora só Via
		$outputType = "json";
		$sql = "SELECT * FROM bling_ult_envio ".$not_int_to;
		$query = $this->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling'];  
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			// echo "-------------------------------------\n";
			$apikey = $apikeys[$row['int_to']];
			$retorno = $this->executeGetProduct($url, $apikey,$row['mkt_store_id']);
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
			$query = $this->db->query($sql);
			$sql = "UPDATE prd_to_integration set skumkt = '".$skumkt."' WHERE int_to = '".$row['int_to']. "' AND prd_id = ".$row['prd_id'];  
			$query = $this->db->query($sql);
		}	
	}

}


?>