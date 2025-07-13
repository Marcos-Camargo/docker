<?php
/*
 
Realiza o Leilão de Produtos e atualiza o B2W 

*/   
 class SkyHubProductsStatus extends BatchBackground_Controller {
	
	var $int_to='B2W';
	var $apikey='';
	var $email='';
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id'        => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp'  => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_integrations');
		$this->load->model('model_errors_transformation');
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
		
		/* faz o que o job precisa fazer */
		$this->getkeys(1,0);
		$retorno = $this->clearErrors();	
		$retorno = $this->syncProductsStatus();		
			
		
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
 	
    function syncProductsStatus()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo "Verificando erros de integração\n";
		$offset = 0; 
		while (true) {
			
			$url = 'https://api.skyhub.com.br/sync_errors/products?offset='.$offset;
			$retorno = $this->getSkyHub($url,$this->getApikey(), $this->getEmail());
			if (!($retorno['httpcode']=="200") )  {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['content'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['content'],true),"E");
				return;
			}
			$resposta = json_decode($retorno['content'],true);
			foreach ($resposta['data'] as $product) {
				$reset_arr = array();
				if (array_key_exists('entity_id', $product)) {
					$reset_arr['entity_id'] = $product['entity_id'];				
				}else if (array_key_exists('sku', $product)) {
					$reset_arr['entity_id'] = $product['sku'];
				}
				echo 'produto '.$reset_arr['entity_id']."\n";
				foreach ($product['categories'] as $errocat ) {
					$reset_arr['error_category_code'] =$errocat['error_category_code'];
					//echo $errocat['error_category_code']."\n";
					foreach ($errocat['errors'] as $errormsg ) {
						$reset_arr['error_code'] = $errormsg['error_code'];
						$reset_json = json_encode($reset_arr);
						$this->addError($reset_arr['entity_id'],$errormsg['last_occurrence'],$errormsg['message'], $reset_json);
					
					}
				}
			}
			if ($resposta['errors_qty'] < 50 ) {
				break;
			}
			$offset += 50;			
		}
		return ;

    } 

	function addError($sku,$data,$msg,$reset_jason) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		if (is_null($msg)) {
			$msg ='';
		}
		
		$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$prd_to = $query->row_array();
		if (empty($prd_to)) {
			if (strrpos($sku, "-") !=0) { // vejo se é uma variante de produto 
				$sku_variant = substr($sku, 0, strrpos($sku, "-"));
				$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku_variant."' AND int_to='".$this->getInt_to()."'";
				$query = $this->db->query($sql);
				$prd_to = $query->row_array();
			}
			if (empty($prd_to)){
				// deve ter mudado de EAN 
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
				$query = $this->db->query($sql);
				$prd_to = $query->row_array();
				if (empty($prd_to)){
					$sku_variant = substr($sku, 0, strrpos($sku, "-"));
					$sql = "SELECT * FROM bling_ult_envio WHERE skubling = '".$sku_variant."' AND int_to='".$this->getInt_to()."'";
					$query = $this->db->query($sql);
					$prd_to = $query->row_array();	
					if (empty($prd_to)){
						echo " Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration \n"; 
						$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration","E");
						return false;
					}
				}
			}
		}
		$error = $this->model_errors_transformation->getErrorByFields($sku,$data,$this->getInt_to());  //vejo se já cadastrei este erro
		if (!$error) {
			// marco os erros antigos e não enviados deste produto como resolvido, já que apareceu um novo erro 
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->getInt_to());
			$trans_err = array(
				'prd_id' => $prd_to['prd_id'],
				'skumkt' => $sku,
				'int_to' => $this->getInt_to(),
				'step' => "Transformação SkyHub/B2W",
				'message' => $msg,
				'status' => 0,
				'date_create' => $data, 
				'reset_jason' => $reset_jason
			);
			echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." ERRO: ".$msg."\n"; 
			$insert = $this->model_errors_transformation->create($trans_err);
			
			// $sql = "UPDATE prd_to_integration SET status_int=29 WHERE id=".$prd_to['id'];
			// $cmd = $this->db->query($sql);
		}
		return true;	
	}

	function clearErrors()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo "Limpando erros de integração\n";
		
		$errors_clean = $this->model_errors_transformation->getErrorsByStatus(1,$this->getInt_to());
		
		$tot_errors = count($errors_clean);
		$next_error = 0;
		while ($next_error <= $tot_errors) {
			$ids = array();
			$errors = array();
			$cnt = 0; 
			echo "Enviando ". $next_error. " de ".$tot_errors."\n";
			while (($cnt <100) && ($next_error <= $tot_errors)) {
				$error = $errors_clean[$next_error];		
				$ids[] = $error['id'];
				$errors[] = json_decode($error['reset_jason'],true);
				$cnt++;
				$next_error++;
			}
			if ($cnt >0) {
				$json_resp = json_encode(array('errors' => $errors),true);
				// echo print_r($json_resp,true)."\n";

				$url = 'https://api.skyhub.com.br/sync_errors/products';
				$retorno = $this->patchSkyHub($url, $json_resp, $this->getApikey(), $this->getEmail());
				while ($retorno['httpcode']=="429")   {  
					echo "Estourei o limite \n";
					sleep(60);
					$retorno = $this->patchSkyHub($url, $json_resp, $this->getApikey(), $this->getEmail());
				}
				if (!($retorno['httpcode']=="204") && !($retorno['httpcode']=="200") )  {  
					echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpcode']."\n";
					echo " http: ".$url."\n";
					echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno['content'],true)." \n"; 
					echo " Enviado: ".print_r($json_resp,true)." \n";
					$this->log_data('batch',$log_name, 'ERRO no ao limpar erros na '.$this->getInt_to().' - httpcode: '.$retorno['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['content'],true)." ENVIADO: ".print_r($json_resp,true) ,"E");
					return;
				}
				
				foreach ($ids as $id){
					$this->model_errors_transformation->setStatus($id,2);
				}
			}
			
		}
		
		return ;

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

	function patchSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_CUSTOMREQUEST  => 'PATCH',
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

}
?>
