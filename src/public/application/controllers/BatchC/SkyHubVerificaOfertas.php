<?php
  
class SkyHubVerificaOfertas extends BatchBackground_Controller {
	
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
		
		$this->getkeys(1,0);
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
		$this->setEmail($api_keys['email']);
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
		
		$myfile = fopen("/var/www/html/app/importacao/PRODUTOS B2W.csv", "r") or die("Unable to open file!");
		$lido = fread($myfile,filesize("/var/www/html/app/importacao/offers.csv"));
		fclose($myfile);
		
		$matar = array();
		$zerar = array();
		$lines= $this->breakLines($lido);
		echo "SKU B2W;SITUACAO B2W;ID;SITUACAO CONECTALA;SITUACAO SKYHUB;PROBLEMA\n";
		foreach($lines as $line) {
			$sku = $line['SKU Parceiro'];
			$line['Situação'] = str_replace(";",",",$line['Situação']);
			//echo $sku."\n";
			$sql = 'SELECT * FROM bling_ult_envio WHERE int_to="B2W" AND skubling = ?';
			$query = $this->db->query($sql, array($sku));
			$row_ult = $query->row_array();
			$variant = '';
			if (is_null($row_ult)) {
				//pode ser produto com variação
				if (strrpos($sku, '-') !=0) {
					$sku = substr($line['SKU Parceiro'], 0, strrpos($line['SKU Parceiro'], '-'));
					$variant = substr($line['SKU Parceiro'], strrpos($line['SKU Parceiro'], '-')+1);
					$sql = 'SELECT * FROM (bling_ult_envio use index (int_skubling)) WHERE int_to ="B2W" and skubling = ?';
					$query = $this->db->query($sql, array($sku));
					$row_ult = $query->row_array();
				}
				if (is_null($row_ult)) {
					//echo "Não achei: ".$line['Situação']." ".$line['SKU Parceiro']."\n"; 
					
					$resp = $this->getSkyHubProduct($line['SKU Parceiro']);
					
					if ($resp) {
						$sky_status = $resp['status'];
						$sky_price = $resp['price'];
						$sky_qty = $resp['qty'];
					}
					else {
						$sky_status = 'NÂO EXISTE';
					}
					
					echo $line['SKU Parceiro'].";".$line['Situação'].";;;".$sky_status.";NÂO EXISTE NO SISTEMA\n";
					continue;
				}
			}
			$sql = 'SELECT * FROM products WHERE id= ?';
			$query = $this->db->query($sql, array($row_ult['prd_id']));
			$prd = $query->row_array();
			
			if (($prd['status'] == 1) && ($prd['situacao'] == 2)) {
				$situacao = "ATIVO";
			}
			else {
				$situacao = "INATIVO";
			}
			
			//echo $sku;
			$resp = $this->getSkyHubProduct($sku);
			//echo print_r($resp,true);	
			if ($resp) {
				$sky_status = $resp['status'];
				$sky_price = $resp['price'];
				$sky_qty = $resp['qty'];
			}
			else {
				$sky_status = 'NÂO EXISTE';
				$sky_price = "-";
				$sky_qty = "-";
			}
			
			
			$clean =str_replace("R$ ","",$line['Preço por']);
			$clean =str_replace(".","",$clean);
			$clean =str_replace(",",".",$clean);			
			
			if ((float)$clean != (float)$row_ult['price'] ) {
				//echo $line['Situação']." Preço diferente : ".$line['SKU Parceiro']." preço aqui:".$row_ult['price']." na B2W:".$line['Preço por']."\n"; 
				echo $line['SKU Parceiro'].";".$line['Situação'].";".$row_ult['prd_id'].";".$situacao.";".$sky_status.";PRECO DIFERENTE B2W: ".$clean." CONECTALA:".$row_ult['price']." SKYHUB:".$sky_price."\n";
			}
			elseif ($variant=='') {
				if ((int)$line['Qtd. Estoque']!= (int)$row_ult['qty_atual'] ) { 
					echo $line['SKU Parceiro'].";".$line['Situação'].";".$row_ult['prd_id'].";".$situacao.";".$sky_status.";QUANTIDADE DIFERENTE B2W: ".$line['Qtd. Estoque']." CONECTALA:".$row_ult['qty_atual']." SKYHUB:".$sky_qty."\n";
				}
			}		
 			else{
				// echo "SKU Ok: ".$line['SKU Parceiro']."\n";
			}
 		}
		
	}


	function getSkyHubProduct($sku) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://api.skyhub.com.br/products/'.$sku;
		$retorno = $this->getSkyHub($url,$this->getApikey(), $this->getEmail());
		if (($retorno['httpcode']=="429") )  {
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
}
?>
