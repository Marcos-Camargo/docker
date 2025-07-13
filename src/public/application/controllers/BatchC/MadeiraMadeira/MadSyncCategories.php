<?php
/*
Baixa as categorias do Madeira Madeira 
*/  
require APPPATH . "controllers/BatchC/MadeiraMadeira/Main.php";

 class MadSyncCategories extends Main {
	
	var $geraCSVOminilogic = false;
	
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
		$this->load->model('model_categorias_marketplaces');
	
    }

	// php index.php BatchC/MadeiraMadeira/MadSyncCategories run 
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
	    $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		
		// seta os valores iniciais e pega os dados da integração. 
		$this->store_id=0;
		$this->int_to = 'MAD';
		$this->getIntegration();
		
		// Busca as categorias
		$this->getCategories();
		if ($this->geraCSVOminilogic) 
			$this->CSVOminilogic();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getCategories()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$offset = 0;
		$limit = 500; 
		$exist_categories = true;
		while ($exist_categories) {
			$url = "/v1/categoria/limit={$limit}&offset={$offset}";
			$this->processURL($url,'GET', null); 
			
			if ($this->responseCode == 404) {
				echo "Acabou 404\n";
				$exist_categories = false;
				continue;
			}
			if ($this->responseCode != 200) {
				$error = "Erro {$this->responseCode} ao acessar {$this->site}{$url} na função GET";
				echo $error."\n";
				$this->log_data('batch',$log_name,$error,"E");
				die;
			}
			$response = json_decode($this->result,true);
			if (empty($response)) {
				echo "Acabou empty\n";
				$exist_categories = false;
				continue;
			}
			$offset += $limit; 
			$categories = $response['data']; 
			foreach($categories as $category) {
				
				echo $category['id_categoria'].'-'.$category['nome_completo_fc'];
				
				$cat = $this->model_categorias_marketplaces->getAllCategoriesById($this->int_to,$category['id_categoria']); 
				if ($cat) { // a categoria já está cadastrada 
					if ($category['nome_completo_fc'] != $cat['nome']) { // o nome sofreu alteração
						echo " - Atualizando id ".$cat['id'].' com nome '.$cat['nome'].' para '.$category['nome_completo_fc']."\n";
						$cat = $this->model_categorias_marketplaces->updateCatTodosMkt(
							array('nome' => $category['nome_completo_fc']),
							$cat['id'],$this->int_to);
					}
					else {
						echo " - Já existe\n";
					}
				}
				else { // não existe então crio 
					$cat_todos = array(
						'id_integration' 	=> $this->integration_main['id'],
						'id' 			 	=> $category['id_categoria'],
						'nome'				=> $category['nome_completo_fc'], 
						'int_to'			=> $this->int_to, 
					); 
					$insert = $this->model_categorias_marketplaces->createCatTodosMkt($cat_todos);
					if ($insert) {
						echo " - Incluido com sucesso\n";
					}
					else {
						echo "erro \n";
						$error = "Erro ao tentar incluir {$category['id_categoria']}: {$category['nome_completo_fc']} na categorias_todos_marketplaces";
						echo $error."\n";
						$this->log_data('batch',$log_name,$error,"E");
						die;
					}
				} 
			}
		}
	}
	
	public function CSVOminilogic() 
	{

		$allCat = $this->model_categorias_marketplaces->getAllCategoriesByMarketplace($this->int_to);
		if (!$allCat) {
			return false;
		}
		$fp = fopen("mad_categorias_omnilogic.csv", "w");
		
		$header  = array("canal","category_id","category_name","parent_category_id");
		fputcsv($fp, $header);
		foreach($allCat as $line) {
			$csv = array (
				'conectala_'.$this->int_to, 
				$line['id'], 
				utf8_encode($line['nome']),
				''
			);
			fputcsv($fp, $csv);
		}
		fclose($fp);
		
		/*
		"canal","category_id","category_name","parent_category_id"
conectala_ML,MLB100028,"Carros, Motos e Outros > Carros e Caminhonetes > Renault > Duster",
conectala_ML,MLB100074,Informática > Componentes para PC > Receptores para TV,
conectala_ML,MLB100075,Informática > Componentes para PC > Gabinetes de PC e Suportes > Suportes de CPU,
		
		*/
	}
}
