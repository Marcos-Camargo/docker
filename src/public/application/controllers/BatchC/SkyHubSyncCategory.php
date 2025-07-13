<?php
/*
 
 Envia as categorias para o SkyHub

*/   
class SkyHubSyncCategory extends BatchBackground_Controller {
	
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
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		
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
		$this->criaCategorias();
		
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
	
	function criaCategorias()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		
		$url = 'https://api.skyhub.com.br/categories';
		$retorno = $this->getSkyHub($url,$this->getApikey(),$this->getEmail());
		if ($retorno['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA B2W: ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA B2W: '.print_r($retorno,true),"E");
			return;
		}
		
		$categorias_skyhub= json_decode($retorno['content'],true);
		if (array_key_exists('message', $categorias_skyhub)) {
		    echo " URL: ". $url. "\n"; 
			echo " RESPOSTA B2W: ".print_r($retorno,true)." \n"; 
			// echo " Dados enviados: ".print_r($data,true)." \n"; 
			//$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA B2W: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA B2W: '.print_r($retorno,true),"E");
			return;
		} 
		
		$categorias = $this->model_category->getCategoryData();
		//var_dump($categorias_skyhub);
		$cat_sky= array();
		foreach ($categorias_skyhub as $categoria_sky){
			$cat_sky[$categoria_sky['code']] = $categoria_sky['name'];
		}
		//var_dump($cat_sky);
		foreach ($categorias as $categoria) {
			if ($categoria['active'] == 2 ) {   //categoria inativa 
				if (isset($cat_sky[$categoria['id']])) {  // vejo se já existe
					// remover 
					sleep(1);
					echo "Removendo a categoria ".$categoria['id']." ".$categoria['name']."\n";
					$url = 'https://api.skyhub.com.br/categories/'.$categoria['id'];
					$retorno = $this->deleteSkyHub($url,$this->getApikey(),$this->getEmail());
					if ($retorno['httpcode'] != 204) {
						echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
						echo " RESPOSTA B2W: ".print_r($retorno,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no delete categoria site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA B2W: '.print_r($retorno,true),"E");
						return;
					}
				}
			}else {  // Categoria ativa
				if (isset($cat_sky[$categoria['id']])) {  // vejo se já existe
					if ( $cat_sky[$categoria['id']] == $categoria['name']) { // está igual
						// já existe pego a próxima  
						continue; 
					} else {
						// nome diferente, altero 
						$cat_data = Array (
						'category' => array(
								    "name" => $categoria['name']
								) 
							);
						sleep(1);
						echo "Alterando a categoria ".$categoria['id']." ".$categoria['name']."\n";
						$json_data = json_encode($cat_data);
						$url = 'https://api.skyhub.com.br/categories/'.$categoria['id'];
						$retorno = $this->putSkyHub($url, $json_data, $this->getApikey(),$this->getEmail());
						// var_dump($retorno);
						if ($retorno['httpcode'] != 204) {
							echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
							echo " RESPOSTA B2W: ".print_r($retorno,true)." \n"; 
							echo " Dados enviados: ".print_r($cat_data,true)." \n"; 
							$this->log_data('batch',$log_name, 'ERRO no put categoria site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA B2W: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($cat_data,true),"E");
							return;
						}
					}
				}
			   	else {
			   		// Novo, crio
					$cat_data = Array (
							'category' => array(
							    "code" => $categoria['id'],
							    "name" => $categoria['name']
							) 
						);
					sleep(1);
					echo "Incluindo a categoria ".$categoria['id']." ".$categoria['name']."\n";
					$json_data = json_encode($cat_data);
					$url = 'https://api.skyhub.com.br/categories';
					$retorno = $this->postSkyHub($url, $json_data, $this->getApikey(),$this->getEmail());
					// var_dump($retorno);
					if ($retorno['httpcode'] != 201) {
						echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
						echo " RESPOSTA B2W: ".print_r($retorno,true)." \n"; 
						echo " Dados enviados: ".print_r($cat_data,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no post categoria site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA B2W: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($cat_data,true),"E");
						return;
					}	
			   }
			}
		}
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
	
}

?>