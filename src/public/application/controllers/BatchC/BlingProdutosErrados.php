<?php

 class BlingProdutosErrados extends BatchBackground_Controller {
		
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
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
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
		$retorno = $this->syncProducts();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		$more = "";
		$url = 'https://bling.com.br/Api/v2/produto/json/';
		// var_dump($apikeys);
		
		$sql = 'select * from stores_mkts_linked'; 
		$query = $this->db->query($sql);
		$markets = $query->result_array();
		foreach ($markets as $market) {
			$loja[$market['apelido']] = $market['id_loja'];
			$apikey[$market['apelido']] =$market['apikey'];
		}
		//var_dump($loja);
		//var_dump($apikey);
		
		$myfile = fopen("errados.csv", "w");
		fwrite($myfile,"MARKETPLACE ERRADO;MARKETPLACE CERTO;SKU;MENSAGEM\n");
		$sql = 'SELECT * FROM bling_ult_envio WHERE int_to != "ML" ORDER BY int_to';
		$query = $this->db->query($sql);
		$prds_bling = $query->result_array();
		$errado = array();
		foreach ($prds_bling as $bling) {
			if (strpos($bling['skubling'],$bling['int_to']) == 0) {
				if ($bling['int_to'] == 'CAR') { $int_to ='VIA';}
				if ($bling['int_to'] == 'VIA') { $int_to ='CAR';}
				if ($bling['int_to'] == 'B2W') { $int_to ='ML';}
				
				echo ' produrando produto '.$bling['skubling'].' da '.$bling['int_to'].' na '.$int_to.' na loja '.$loja[$int_to]."\n";
				
				$url = 'https://bling.com.br/Api/v2/produto/'.$bling['skubling'].'/json&apikey='.$apikey[$int_to].'&loja='.$loja[$int_to];
				$retorno = $this->getProduct($url);
				if (array_key_exists('produtoLoja', $retorno['retorno']['produtos'][0]['produto'])) {
					echo ' ERRADO '."\n";
					fwrite($myfile, $int_to.';'.$bling['int_to'].';'.$bling['skubling'].";Enviado Errado para ".$int_to." produto da ".$bling['int_to']."\n");
				}
				
			} 
			
		}
		fclose($myfile);
		die; 
		
    } 

	function getProduct($url){
	    $curl_handle = curl_init();

	   
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		if ($httpcode != '200') {
			 echo "http = ".$url."\n";
			 echo  ' erro httpcode='.$httpcode."\n";
			 die;
		}
	    curl_close($curl_handle);
	    return json_decode($response,true);
	}
}
?>
