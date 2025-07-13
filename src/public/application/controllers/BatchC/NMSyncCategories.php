<?php
/*
 
Realiza a atualização de preço e estoque dos Seller Centers

*/   

require 'NovoMundo/NMIntegration.php';


 class NMSyncCategories extends BatchBackground_Controller 
 {
		
	private $integration = null;
	private $integration_data = null;
    private $int_to = 'NM';
	private $int_to_SC = 'NovoMundo';
	private $api_keys = null;
    private $total_categories = 0;
    private $total_attributes = 0;


	public function __construct()
	{
		parent::__construct();
		
		$logged_in_sess = array(
			'id' => 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' => 1,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		
		$this->load->model('model_integrations');
        $this->load->model('model_atributos_categorias_marketplaces'); 

        $this->integration = new NMIntegration();
    }


	function run($id=null, $params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) 
		{
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType(1, 'NM', "CONECTALA", "DIRECT", 0);
		$this->api_keys = json_decode($this->integration_data['auth_data'], true);
		
		$retorno = $this->syncCategories($this->api_keys);

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
		 

    function syncCategories($api_keys)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$response = $this->integration->getCategories($api_keys);

		$categories = json_decode($response['content'], true)['result'];
	
		foreach ($categories as $category) 
		{
			$this->saveUpdateCategories($category);
		}

		return ;
	} 
	
	
	private function saveUpdateCategories($category) 
	{
		if (!is_array($category) || empty($category))
			return false;

		if ($category['status'] === 'enabled')
		{
			$sql = "INSERT INTO categorias_todos_marketplaces (id_integration, id, nome, int_to) 
					VALUES (".intVal($this->integration_data['id']).", ".$category['code'].", '".addslashes(trim($category['name']))."', '".$this->integration_data['int_to']."')
					ON DUPLICATE KEY UPDATE nome = '".addslashes(trim($category['name']))."', int_to = '".$this->int_to."'";

            if ($this->db->query($sql))
            {
                echo "\r\n-- Gravando Categoria ".$category['name']." (".++$this->total_categories.") \r\n";

                $attributes = $this->integration->getAttributesNM($this->api_keys, $category['code'], $this->int_to_sc);

                if (!empty($attributes))
                {
                    if (!empty($attributes['result'][0]) && $attributes['success'] == true)
                    {
                        foreach ($attributes['result'] as $k => $attr)
                        {       
                            $mandatory = '';     

                            $data = [
                                'id_integration' => intVal($this->integration_data['id']),
                                'id_categoria' => $category['code'],
                                'id_atributo' => $attr['attribute_id'],
                                'obrigatorio' => ($attr['required'] === false) ? 2 : 1,
                                'int_to' => $this->integration_data['int_to'],
                                'variacao' => ($attr['variation'] === false) ? 0 : 1,
                                'nome' => $attr['name'],
                                'tipo' => $attr['type'],
                                'valor' => json_encode($attr['values'])
                            ];

                             if($attr['required'] === true)
                                $mandatory .= '(*) ';

                            if($attr['variation'] === true)
                                $mandatory .= '## ';

                            echo "   +-- ".$mandatory."Atributo  ".$attr['name']." (".++$this->total_attributes.") \r\n";                            

                            $this->model_atributos_categorias_marketplaces->replace($data);
                        }
                    }
                }
            }
		}
	}



	private function saveIfNotExists($category, $breadcrumb) {
		if (!$this->find($category)) {
			$this->save($category, $breadcrumb);
		}
	}

	private function find($category) {
		$sql = "SELECT * FROM categorias_todos_marketplaces WHERE int_to = 'SC' and id = ". $category['id'] ;
		$cmd = $this->db->query($sql);
		$record = $cmd->row_array();
		return !is_null($record);
	}

	private function save($category, $breadcrumb) {
		$sql = "INSERT INTO categorias_todos_marketplaces (id_integration, id, nome, int_to) ".
			"VALUES(50, '".$category['id']."', '". $breadcrumb ."', 'NM');";
		$this->db->query($sql);
	}
}
?>
