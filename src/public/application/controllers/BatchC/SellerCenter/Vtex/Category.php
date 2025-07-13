<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

abstract class Category extends Main
{
    const FILENAME = 'categoriesMapping.xlsx';
    var $categories_mapping = array();
    var $categories_active = array();

	abstract function run($id = null, $params = null);
	
    public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
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
    
    function sync() {
        $main_integrations = $this->model_integrations->getIntegrationsbyStoreId(0);

        foreach ($main_integrations as $integrationName) {
            echo 'Sync: '. $integrationName['int_to']."\n";
            $this->syncIntTo($integrationName['id'], $integrationName['int_to'], $integrationName['auth_data']);
            echo PHP_EOL;
        }

        echo PHP_EOL . "FIM SYNC CATEGORIAS" . PHP_EOL;
    }

    function syncIntTo($id_integration, $int_to, $auth_data) {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $this->categories_active = array();
        $endPoint = 'api/catalog_system/pvt/category/tree/100';
        $categories_json = $this->getCategories($int_to, $auth_data, $endPoint);
		if (($this->responseCode == 429) || ($this->responseCode == 504)) {
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
            // var_dump($category);
            $tree_category_id = '['. $category->id . '] '. $category->name;
            $this->findAndSaveLeaf($auth_data, $id_integration, $int_to, $category, $category->name, $tree_category_id);
        }

        $this->inactivateCategories($this->categories_active,$int_to);
        $this->createXls($this->categories_mapping, $int_to);
    }

    private function findAndSaveLeaf($auth_data, $id_integration, $int_to, $category, $breadcrumb = '', $tree_category_ids = '') {

        if ($category->hasChildren) {
            if (count($category->children) > 0) {
                $childrens = $category->children;
                foreach($childrens as $children) {
                    $category_name = $breadcrumb .'/'. $children->name;
                    $tree_category_id = $tree_category_ids . ' | ['. $children->id . '] '. $children->name;
                    $this->save($auth_data, $id_integration, $category->id, $breadcrumb, $tree_category_id, $int_to);
                    $this->findAndSaveLeaf($auth_data, $id_integration, $int_to, $children, $category_name, $tree_category_id);
                }
            }
        }
        else {
            $this->save($auth_data, $id_integration, $category->id, $breadcrumb, $tree_category_ids, $int_to);
        }
    }

    private function save($auth_data, $id_integration, $category_mkt_id, $breadcrumb, $category_tree_ids, $int_to) {
        echo 'Category: '. $category_mkt_id . PHP_EOL;
        echo "nome = ".$breadcrumb."\n";
        
        $this->categories_active[$category_mkt_id] = $breadcrumb; 

        $this->addMapping(array(
            'mapping'       => $category_tree_ids,
            'breadcrumb'    =>$breadcrumb
        ));

        $sql = "SELECT * FROM categories WHERE name = ?";
        $query = $this->db->query($sql, array($breadcrumb));
        if (count($query->result_array()) == 0) {
            $category = array(
                'name'          => $breadcrumb,
                'active'        => 1,
                'qty_products'  => 0
            );

            $this->db->insert('categories', $category);
            $categories_id = $this->db->insert_id();
        }
        else {
            $categories = $query->row_array();
            $categories_id = $categories['id'];
        }    
        
        $categoria_todos_marketplaces = array(
            'id_integration'    => $id_integration,
            'id'                => $category_mkt_id,
            'nome'              => $breadcrumb,
            'int_to'            => $int_to
        );

        $this->db->replace('categorias_todos_marketplaces', $categoria_todos_marketplaces);

        // $sql = "SELECT * FROM categorias_marketplaces WHERE int_to = ? and category_id =  ? and category_marketplace_id = ?";
        // $query = $this->db->query($sql, array($int_to, $categories_id, $category_mkt_id));
		$sql = "SELECT * FROM categorias_marketplaces WHERE int_to = ? and category_id =  ? ";
        $query = $this->db->query($sql, array($int_to, $categories_id));
        if (count($query->result_array()) == 0) {
            $categorias_marketplaces = array(
                'int_to'                    => $int_to,
                'category_id'               => $categories_id,
                'category_marketplace_id'   => $category_mkt_id
            );

            $this->db->replace('categorias_marketplaces', $categorias_marketplaces);
        }

        $this->findAndSaveSpecifications($int_to, $auth_data, $id_integration, $categories_id, $category_mkt_id);
    }

    private function getCategories($int_to, $auth_data, $end_point)
    {
        $this->process($int_to, $end_point);

        return $this->result;
    }

    private function findAndSaveSpecifications($int_to, $auth_data, $id_integration, $category_id, $category_mkt_id) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
       // $endPoint = 'api/catalog_system/pub/specification/field/listByCategoryId/'. $category_mkt_id;
        $endPoint = 'api/catalog_system/pub/specification/field/listTreeByCategoryId/'. $category_mkt_id;
        $specifications_json = $this->getSpecifications($int_to, $auth_data, $endPoint);
		if (($this->responseCode == 429) || ($this->responseCode == 504)) {
			sleep(60);
			$specifications_json = $this->getSpecifications($int_to, $auth_data, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
			return;
        }
        //TODO - EXCLUIR AQUI
        //$this->db->where('id_categoria', (string)$category_mkt_id);
        //$this->db->delete('atributos_categorias_marketplaces');
		$this->model_atributos_categorias_marketplaces->removeByCategory($category_mkt_id); 

        $specifications = json_decode($specifications_json, true);
        foreach ($specifications as $specification) {
            $field = $this->getSpecificationField($int_to, $auth_data, $specification);

            if ($specification['IsActive'] === false) continue ;
			// if (is_null($specification['CategoryId'])) continue ;
            $field_type = $this->getFieldType($field);

            $values = array();
            if ($field_type == 'list') {
                $values = $this->getSpecificationValues($int_to, $auth_data, $specification);
            }
			/*
			if ($specification['Name'] == 'Voltagem') {
				echo " categoria ".$category_mkt_id."\n";
				var_dump($field);
			}*/
			echo "    ".$specification['Name']." \n";
            $specification_data = array(
                'id_integration'    => $id_integration,
                'id_categoria'      => $category_mkt_id,
                'id_atributo'       => $specification['FieldId'],
                'obrigatorio'       => $field['IsRequired'] === true ? 1 : 2,
                'int_to'            => $int_to,
                'variacao'          => $specification['IsStockKeepingUnit'] === true ? 1 : 0,
                'nome'              => $specification['Name'],
                'tipo'              => $field_type,
                'multi_valor'       => ($field['FieldTypeName'] == 'CheckBox') ? 1 : 0 ,
                'valor'             => json_encode($values),
                'atributo_variacao' => 0
            );
            if (!$this->exists($id_integration, $category_mkt_id, $specification['FieldId'])) {
                $this->db->insert('atributos_categorias_marketplaces', $specification_data);
            }
            else {
                $this->db->replace('atributos_categorias_marketplaces', $specification_data);
            }
        }   
    }

    private function getSpecificationField($int_to, $auth_data, $specification) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $endPoint = 'api/catalog_system/pub/specification/fieldGet/'. $specification['FieldId'];
        $specifications_json = $this->getSpecifications($int_to, $auth_data, $endPoint);
		if (($this->responseCode == 429) || ($this->responseCode == 504)) {
			sleep(60);
			$specifications_json = $this->getSpecifications($int_to, $auth_data, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
        $field = json_decode($specifications_json, true);
        return $field;
    }

    private function getFieldType($field) {
        $type = 'string';
        if (($field['FieldTypeName'] != 'Texto') && ($field['FieldTypeName'] != 'Texto Grande') && ($field['FieldTypeName'] != 'Texto Grande Indexado')){
            if (($field['FieldTypeName'] == 'Radio') || ($field['FieldTypeName'] == 'Combo') || ($field['FieldTypeName'] == 'CheckBox')){
                $type = 'list';
            }
            else {
                echo $field['FieldId']. ' - '. $field['Name'] . ' ' . $field['FieldTypeName'] . PHP_EOL;
            }

        }
        
        return $type;
    }

    private function getSpecificationValues($int_to, $auth_data, $specification) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $endPoint = 'api/catalog_system/pub/specification/fieldValue/'. $specification['FieldId'];
        $specifications_json = $this->getSpecifications($int_to, $auth_data, $endPoint);
		if (($this->responseCode == 429) || ($this->responseCode == 504)) {
			sleep(60);
			$specifications_json = $this->getSpecifications($int_to, $auth_data, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
        $field = json_decode($specifications_json, true);
        return $field;
    }

    private function getSpecifications($int_to, $auth_data, $end_point) {
        $this->process($int_to, $end_point);

        return $this->result;
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

}
