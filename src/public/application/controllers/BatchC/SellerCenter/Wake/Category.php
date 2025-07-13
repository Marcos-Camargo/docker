<?php
require APPPATH . "controllers/BatchC/SellerCenter/Wake/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
//ini_set("memory_limit", "4096M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

class Category extends Main
{
    const FILENAME = 'categoriesMapping.xlsx';
    var $categories_mapping = array();
    var $categories_active = array();

	public function __construct()
	{
		parent::__construct();

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
        $this->load->model('model_integrations');
        $this->load->model('model_catalogs');
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_categorias_marketplaces');
        $this->load->model('model_settings');
        $this->load->model('model_category');

        $this->load->library('excel');

    }

    // php index.php BatchC/SellerCenter/Wake/Category run null farmadelivery
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
		if(is_null($params)  || ($params == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
			if($integration){
				echo 'Sync: '. $integration['int_to']."\n";
                $this->syncIntTo($integration['id'], $integration['int_to'], $integration['auth_data']);
                $this->checkMissingCategories($integration['int_to']); 
            }
			else {
				echo PHP_EOL .$params." não tem integração definida". PHP_EOL;
			}
		}    

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
    }
    
    function syncIntTo($id_integration, $int_to, $auth_data) {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $this->categories_active = array();
        $endPoint = 'categorias';
        $categories_json = $this->getCategories($int_to, $auth_data, $endPoint);
		if (($this->responseCode == 429) || ($this->responseCode == 504) || ($this->responseCode == 502)) {
			echo " Wake respondeu ".$this->responseCode.". Aguardando 60 segundos\n";
            sleep(60);
			$categories_json = $this->getCategories($int_to, $auth_data, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true); 
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
        $categories = json_decode($categories_json);
        
        foreach ($categories as $category) {
            $tree_category_id = '['. $category->id . '] '. $category->nome;
            $this->findAndSaveLeaf($auth_data, $id_integration, $int_to, $category, $category->caminhoHierarquia, $tree_category_id);
        }

        $this->inactivateCategories($this->categories_active,$int_to);
        $this->createXls($this->categories_mapping, $int_to);
    }

    private function findAndSaveLeaf($auth_data, $id_integration, $int_to, $category, $breadcrumb = '', $tree_category_ids = '') {
        $this->save($auth_data, $id_integration, $category->id, $breadcrumb, $tree_category_ids, $int_to);
    }

    private function save($auth_data, $id_integration, $category_mkt_id, $breadcrumb, $category_tree_ids, $int_to) {

        if (array_key_exists($category_mkt_id, $this->categories_active)) {
            // echo " Já categoria ".$category_mkt_id." já processada \n";
            return ;
        }

        echo 'Category: '. $category_mkt_id . PHP_EOL;
        echo "nome = ".$breadcrumb."\n";
        
        $this->categories_active[$category_mkt_id] = $breadcrumb; 

        $this->addMapping(array(
            'mapping'       => $category_tree_ids,
            'breadcrumb'    => $breadcrumb
        ));

        $category_market = $this->model_categorias_marketplaces->getAllCategoriesByMarketplaceAndCategoryId($int_to, $category_mkt_id);
        if (count($category_market)>1) {
            echo "Categoria do marketplace ".$category_mkt_id." repetida em categorias_marketplaces, removendo\n"; 
            foreach($category_market as $cat_remove) {                 
                $this->model_categorias_marketplaces->removeByAllFields($int_to, $cat_remove['category_id'] ,$cat_remove['category_marketplace_id']);              
            }
            $category_market = false;
        }

        If ($category_market) { // Categoria existe, vamos ver se mudou o nome             
            $category_market= $category_market[0];
            $categories = $this->model_category->getCategoryData($category_market['category_id']);
            $categories_id = $categories['id'];
            if ($categories['name'] != $breadcrumb) {
                // verifico se sou único para este Marketplace                 
                $all_categories =  $this->model_categorias_marketplaces->verifyExistCategoryAssociateDiferentByIntTo($categories_id, $int_to);
                if (!count($all_categories)) {
                    echo "O nome mudou na Wake de '".$breadcrumb."' para '".$categories['name']."'. Acertando a categoria\n";
                    $this->model_category->update(array('name'=> $breadcrumb, 'active'=>1), $categories_id);
                    $categories['active'] = 1;
                }
                else { // Nossa Categpria está associada com outras de outras marketplace                     
                    echo "O nome mudou na Wake de '".$breadcrumb."' para '".$categories['name']."' Como tem mais de uma categoria associada, o nome da categoria não mudará.\n";                                            
                }                                
            }
            if ($categories['active'] != 1) { // pode não ter mudado o nome mas está ativa.
                echo "Ativando a categoria ".$categories_id."\n";
                $this->model_category->update(array('active'=>1),$categories_id);
            }
        } else { // Não tem associação com as categorias então teria que criar ou associar 
            echo "Associação de categoria não encontrada\n";
            $categories = $this->model_category->getDataCategoryByName($breadcrumb);
            if (!$categories) {  // cria a categoria 
                $category = array(
                    'name'          => $breadcrumb,
                    'active'        => 1,
                    'qty_products'  => 0
                );
                $categories_id =  $this->model_category->create($category);
                echo "Criando a categoria ". $breadcrumb. " com id ".$categories_id."\n";
            }
            else { // Ja tinha, então ativa se precisar 
                $categories_id = $categories['id'];            
                if ($categories['active'] != 1) {
                    echo "Ativando a categoria ".$categories_id."\n";
                    $this->model_category->update(array('active'=>1),$categories_id);
                }

                $verifyIfExist = $this->model_categorias_marketplaces->getDataByCategoryId($categories_id);
                if ($verifyIfExist) {
                    echo "Já existia uma associação entre a categoria ".$categories_id." com o categoria_marketplace ".$verifyIfExist[0]['category_marketplace_id'].". Removendo\n";
                    $this->model_categorias_marketplaces->removeByIntToCategoryId($int_to, $categories_id);
                }
            }  
            // Agora crio 
            $categorias_marketplaces = array(
                'int_to'                    => $int_to,
                'category_id'               => $categories_id,
                'category_marketplace_id'   => $category_mkt_id
            );
            
            $category_market=$this->model_categorias_marketplaces->create( $categorias_marketplaces);
            echo "Associação de categoria realizanda entre category_id ".$categories_id." e cat_mkt ".$category_mkt_id."\n";
        }

        $categoria_todos_marketplaces = array(
            'id_integration'    => $id_integration,
            'id'                => $category_mkt_id,
            'nome'              => $breadcrumb,
            'int_to'            => $int_to
        );
        $catTodos = $this->model_categorias_marketplaces->getAllCategoryByMarketplace($int_to, $category_mkt_id);
        if (count($catTodos) == 0) {
            $this->model_categorias_marketplaces->createTodosMarketplace($categoria_todos_marketplaces);            
        }
        else {
            $categorieMkt = $catTodos[0];
            if ($breadcrumb != $categorieMkt['nome']) { // nome mudou, acerto 
                $this->model_categorias_marketplaces->replaceTodosMarketplace($categoria_todos_marketplaces);
            }
        }

        $this->findAndSaveSpecifications($int_to, $auth_data, $id_integration, $categories_id, $category_mkt_id);
    }

    private function getCategories($int_to, $auth_data, $end_point)
    {
        $this->processNew(json_decode($auth_data),$end_point);
        if (($this->responseCode == 429) || ($this->responseCode == 504)) {
            echo "Timeout ".$this->responseCode."\n";
			sleep(60);
			return $this->getCategories($int_to, $auth_data, $end_point);
		}

        return $this->result;
    }

    private function findAndSaveSpecifications($int_to, $auth_data, $id_integration, $category_id, $category_mkt_id) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $endPoint = 'atributos';
        $this->processNew(json_decode($auth_data), $endPoint);
        $specifications_json = $this->result;
		if (($this->responseCode == 429) || ($this->responseCode == 504) || ($this->responseCode == 500) || ($this->responseCode == 503)|| ($this->responseCode == 502)) {
            echo "Wake com erro ".$this->responseCode."\n";
			sleep(60);
			$specifications_json = $this->processNew(json_decode($auth_data), $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");	
            die; 
			return;
        }

		$this->model_atributos_categorias_marketplaces->removeByCategoryAndIntTo($category_mkt_id, $int_to); 

        $specifications = json_decode($specifications_json, true);
        foreach ($specifications as $specification) {

            //if ($specification['IsActive'] === false) continue ;
            $field_type = 'string';

            $values = array();
            // if ($field_type == 'list') {
            //     $values = $this->getSpecificationValues($int_to, $auth_data, $specification);
            // }
			echo "    ".$specification['nome']." \n";
            $specification_data = array(
                'id_integration'    => $id_integration,
                'id_categoria'      => $category_mkt_id,
                'id_atributo'       => $specification['id'],
                'obrigatorio'       => 2, //atualmente nada é obrigatório
                'int_to'            => $int_to,
                'variacao'          => $specification['tipo'] == "Selecao" ? 1 : 0,
                'nome'              => $specification['nome'],
                'tipo'              => $field_type,
                'multi_valor'       =>  0 , // pelo entendimento não tem valores pre cadastrados
                'valor'             => json_encode($values),
                'atributo_variacao' => 0
            );
            if (!$this->exists($id_integration, $category_mkt_id, $specification['id'])) {
                $this->db->insert('atributos_categorias_marketplaces', $specification_data);
            }
            else {
                $this->db->replace('atributos_categorias_marketplaces', $specification_data);
            }
        }   
    }

    private function exists($id_integration, $id_categoria, $id_atributo) {
        $records = $this->model_atributos_categorias_marketplaces->getData($id_integration, $id_atributo, $id_categoria);
        if (!is_null($records))
            return count($records) > 0;
        else 
            return false;
    }

    private function addMapping($mapping) {
        $canAdd = true;
        foreach($this->categories_mapping as $item) {
            if ($mapping['breadcrumb'] == $item['breadcrumb']) {
                $canAdd = false;
            }
        }
        if ($canAdd === true) {
            array_push($this->categories_mapping, $mapping);
        }
    }

    private function createXls($mapping, $int_to) {
        $objPHPExcel = new Excel();
        $objPHPExcel->setActiveSheetIndex(0);

        $line = 1;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, 'MarketPlace categories');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $line, 'Categories sended by seller');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $line, 'Unmapped categories sended by seller');
        
        foreach($mapping as $item) {
            $line++;

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, $item['mapping']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $line, $item['breadcrumb']);
        }
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save(FCPATH . 'assets/images/'.SELF::FILENAME);
    }

    private function inactivateCategories($categories_active, $int_to) 
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $all_categories = $this->model_categorias_marketplaces->getAllCategoriesByMarketplace($int_to);
        echo "Verificando categorias para remover ou inativar \n";
        $inactivate_category = $this->model_settings->getValueIfAtiveByName('inactivate_sellercenter_category_if_associated_marketplace_category_is_inactive');
       
        foreach($all_categories as $category) {
            if (!in_array($category['nome'], $categories_active)) {
                echo "Não achei a categoria ".$category['id']."  ".$category['nome']." removendo \n";

                if ($inactivate_category) {
                    $categories_to_inactivate = $this->model_categorias_marketplaces->getAllCategoriesByMarketplaceAndCategoryId($int_to, $category['id']);
                    foreach ($categories_to_inactivate as $cat_inactivate) {
                        echo "Inativando categoria ". $cat_inactivate['category_id']."\n"; 
                        $this->model_category->update(array('active' =>2),$cat_inactivate['category_id'] );
                    }
                }

                // deletar de atributos_categorias_marketplaces id_categoria = $category['id'] and int_to=$int_to
                if ($this->model_categorias_marketplaces->removeAtttributesByCategoryIdIntTo($int_to, $category['id'])) {
                    if ($this->model_categorias_marketplaces->removeMarketplaceByCategoryIdIntTo($int_to, $category['id'])) {
                        if ($this->model_categorias_marketplaces->removeCategoryByCategoryIdIntTo($int_to, $category['id'])) {
                            echo "Removido com sucesso\n";
                        } else {
                            $erro = 'Erro ao remover de categorias_marketplaces int_to='.$int_to.' categoria do marketplace='.$category['id'];
                            echo $erro."\n";
                            $this->log_data('batch',$log_name, $erro ,"E");
                            die;
                        }
                    } else {
                        $erro = 'Erro ao remover de categorias_todos_marketplaces int_to='.$int_to.' categoria do marketplace='.$category['id'];
                        echo $erro."\n";
                        $this->log_data('batch',$log_name, $erro ,"E");
                        die;
                    }
                } else {
                    $erro = 'Erro ao remover de atributos_categorias_marketplaces int_to='.$int_to.' categoria do marketplace='.$category['id'];
                    echo $erro."\n";
                    $this->log_data('batch',$log_name, $erro ,"E");
                    die;
                }

            }
        }
    }

    private function checkMissingCategories($int_to) 
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $inactivate_category = $this->model_settings->getValueIfAtiveByName('inactivate_sellercenter_category_if_associated_marketplace_category_is_inactive');
       
        echo "Verificando categorias que não existem mais na Wake \n";
        if (!$inactivate_category) {
            echo "Função inativada \n";
            return ;
        }
        
        $categories = $this->model_category->getActiveCategroy(); 
        $categories_mkt = $this->model_categorias_marketplaces->getAllCategoriesByMarketplace($int_to);        
        foreach($categories as $category) {
            $found = false; 
            foreach ($categories_mkt as $cat_mkt) {
                if ( $cat_mkt['nome'] ==  $category['name']) {
                    $found = true; 
                    break;
                }
            }
            if (!$found) {
                echo  "Desativando ".$category['name']."\n"; 
                $this->model_category->update(array('active' =>2),$category['id'] );
            }
        }
    }

}
