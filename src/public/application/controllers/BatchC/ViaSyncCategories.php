<?php
/*
 
Realiza a atualização de preço e estoque da VIA Varejo

*/   

require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

 class ViaSyncCategories extends BatchBackground_Controller {
		
	private $oAuth2 = null;
	private $integration = null;
	private $count = 0;

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
		$this->load->model('model_integrations');

		$this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();
    }

	function getInt_to() {
		return ViaUtils::getInt_to();
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
		$retorno = $this->syncCategories($authorization, 0, 1);

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
		 
    function syncCategories($authorization, $offset, $limit)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$int_to = $this->getInt_to();

		$response = $this->integration->getCategories($authorization, $offset, $offset + 1);

		$categories = json_decode($response['content'], true)['categories'];

		foreach ($categories as $category) {
			$this->searchLeaf($category, $category['name']);
		}

		if (count($categories) == $limit) {
			$offset = $offset + 1;
			$this->syncCategories($authorization, $offset, $offset + 1);
		}

		return ;
	} 
	
	private function searchLeaf($category, $breadcrumb) {
		if (count($category['categories']) == 0) {
			$this->saveIfNotExists($category, $breadcrumb);
		}
		else {
			foreach ($category['categories'] as $cat) {
				$this->searchLeaf($cat, $breadcrumb . ' > '. $cat['name']);
			}
		}
	}

	private function saveIfNotExists($category, $breadcrumb) {
		if (!$this->find($category)) {
			$this->save($category, $breadcrumb);
		}
	}

	private function find($category) {
		$sql = "SELECT * FROM categorias_todos_marketplaces WHERE int_to = 'VIA' and id = ". $category['id'] ;
		$cmd = $this->db->query($sql);
		$record = $cmd->row_array();
		return !is_null($record);
	}

	private function save($category, $breadcrumb) {
		$sql = "INSERT INTO categorias_todos_marketplaces (id_integration, id, nome, int_to) ".
			"VALUES(50, '".$category['id']."', '". $breadcrumb ."', 'VIA');";
		$this->db->query($sql);
	}
}
?>
