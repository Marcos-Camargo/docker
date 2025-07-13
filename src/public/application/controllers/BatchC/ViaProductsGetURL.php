<?php
/*
 
Sincroniza os produtos que foram alterados e que são ganhadores de leilão

*/   
require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

 class ViaProductsGetURL extends BatchBackground_Controller {
	
	var $int_to='VIA';
	var $apikey='';
	var $email='';

	private $oAuth2 = null;
	private $integration = null;

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
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_stores');

		$this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();
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
		
		$int_to = $this->getInt_to();
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		/* faz o que o job precisa fazer */
		$retorno = $this->getURL($authorization);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
    function getURL($authorization)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	

		$sql = "SELECT * FROM prd_to_integration WHERE skumkt IS NOT NULL and status = 1 and status_int = 2 and ad_link is null AND int_to='". $this->getInt_to()."' ";
      	$query = $this->db->query($sql);
		$pis = $query->result_array();
		foreach ($pis as $pi) 
	    {
			if (($pi['status'] == 1) && ($pi['status_int'] == 2) && ($pi['skumkt'] != '')) {
	    		echo "Sku ".$pi['skumkt']."\n";
				$resp = $this->integration->getProduct($authorization, $pi['skumkt']);
				if ($resp['httpcode']=="429")  {  
					echo "Estourei o limite \n";
					sleep(60);
					$resp = $this->integration->getProduct($authorization, $pi['skumkt']);
				}
				if ($resp['httpcode']=="404")   {  // não existe link 
					echo "Não achou URL para o sku ".$pi['skumkt']."\n";
					$ad_link = null;
				}
				elseif ($resp['httpcode']!="200")   {  
					echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO no get URL no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true),"E");
					continue;
				} 
				else {
					$resp_via = json_decode($resp['content'],true);
					if (array_key_exists('error', $resp_via)) {
						echo "Não achou URL para o sku ".$pi['skumkt']."\n";
						$ad_link = null;
					}
					else {
						$urls = $resp_via['urls'];

						$ad_link = array();
						foreach($urls as $url) {
							$site = '';

							switch ($url['site']) {
								case 'CB':
									$site = 'Casas Bahia';
									break;
								case 'PF':
									$site = 'Ponto Frio';
									break;
								case 'EX':
									$site = 'Extra';
									break;
								default:
									# code...
									break;
							}

							$url_link = array(
								'name' => $site,
								'href' => $url['href']
							);
							array_push($ad_link, $url_link);
						}

						$ad_link = json_encode($ad_link);

						echo $ad_link."\n";
					}	
				} 
	    	}
			else {
				$ad_link = null; 
			}
			if ($ad_link != $pi['ad_link']) {
				$this->db->where('id', $pi['id']);
				$data = array('ad_link' =>  $ad_link); 
	            $update = $this->db->update('prd_to_integration', $data);
			}
		}
    } 
}
?>
