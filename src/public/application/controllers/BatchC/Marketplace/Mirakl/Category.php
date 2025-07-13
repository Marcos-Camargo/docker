<?php

require APPPATH . "controllers/BatchC/Marketplace/Vtex/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;
// php index.php BatchC/Marketplace/Mirakl/Category sync null GPA
 class Category extends BatchBackground_Controller
{
    const FILENAME = 'categoriesMapping.xlsx';
    var $categories_mapping = array();
	var $ignore_list;
	
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
        $this->load->model('model_integrations');
        $this->load->model('model_catalogs');
        $this->load->model('model_atributos_categorias_marketplaces');

        $this->load->library('excel');

    }
    
    function sync($id = null, $params=null) {

        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        if(is_null($params)){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
            echo PHP_EOL . "FIM SYNC CATEGORIAS" . PHP_EOL;
            $this->log_data('batch',$log_name,'finish',"I");
            $this->gravaFimJob();
            die;
        }

        $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
        if($integration){
            echo 'Sync: '. $integration['int_to']."\n";
            $this->syncIntTo($integration['id'], $integration['int_to'], $integration['auth_data']);
        }


        echo PHP_EOL . "FIM SYNC CATEGORIAS" . PHP_EOL;
        $this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
    }

    function syncIntTo($id_integration, $int_to, $auth_data) {

    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->populateIgnoreList($int_to);
		
        $auth_data_json = json_decode($auth_data);
        $endPoint = 'https://'.$auth_data_json->site.'/api/hierarchies';
        $cnt = 1;
		$max = 10;
		while ($cnt++ <= $max) {
			$categories_json = $this->getRequest($endPoint, $auth_data_json->apikey);
			if (($this->responseCode == 429) || ($this->responseCode == 504)) {
				echo " Dormindo por 1 minuto pois deu ".$this->responseCode ."\n";
				sleep(60);
			}	
			else {
				$cnt = $max+1;
			}
		}

		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true); 
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
        $categories = json_decode($categories_json);
        $this->categories = $categories->hierarchies;

        foreach ($this->categories as $category) {
            $this->findAndSaveLeaf($auth_data, $id_integration, $int_to, $category);
        }
    }

    private function findAndSaveLeaf($auth_data, $id_integration, $int_to, $category) {
        if(isset($category->label)){
            if ($category->level > 1) {
                $breadcrumb = $category->label;
                $level = $category->level;
                $categoryId = $category->parent_code;
                $categoriesTree = array("",$category->code);

                while ($level > 1) {
    
                    $categoryFather = $this->findFather($categoryId);
                    $categoryFather = $categoryFather[0];
                    $level = $categoryFather->level;
                    $categoryId = $categoryFather->parent_code;
                    array_push($categoriesTree,$categoryFather->code);
                    $breadcrumb = $categoryFather->label.'/'.$breadcrumb;
                    
                  }
                 // echo $breadcrumb. "\n";
                  $this->save($auth_data, $id_integration, $category->code, $breadcrumb, $int_to, $categoriesTree);
            }
            else {
                $categoriesTree = array("",$category->code);
                $breadcrumb = $category->label;
                echo $breadcrumb;
                $this->save($auth_data, $id_integration, $category->code, $breadcrumb, $int_to, $categoriesTree);

            }
        }
    }

    private function findFather($categoryId){       
        $categoryFather = array_filter($this->categories, function ($category) use($categoryId){
            return $category->code == $categoryId;
        });
        return array_values($categoryFather);
    }

    private function findSpecificationsByIds($categories){       
        $specifications = array_filter($this->specifications, function ($specification) use($categories){
            return in_array($specification['hierarchy_code'], $categories);
        });
        return array_values($specifications);
    }

    private function save($auth_data, $id_integration, $category_mkt_id, $breadcrumb, $int_to, $categoriesTree) {
        echo 'Category: '. $category_mkt_id . PHP_EOL;
        echo "nome = ".$breadcrumb."\n";
               
        $categoria_todos_marketplaces = array(
            'id_integration' => $id_integration,
            'id' => $category_mkt_id,
            'nome' => $breadcrumb,
            'int_to' => $int_to
        );

        $this->db->replace('categorias_todos_marketplaces', $categoria_todos_marketplaces);

        $this->findAndSaveSpecifications($int_to, $auth_data, $id_integration, $category_mkt_id, $category_mkt_id, $categoriesTree);
    }

    private function getFieldType($tipo) {
        $type = 'string';
		if (($tipo == 'TEXT') || ($tipo == 'LONG_TEXT')|| ($tipo == 'MEDIA') || ($tipo == 'DATE')) {
			$type = 'string';
		}
		elseif (($tipo=='INTEGER') || ($tipo=='DECIMAL')) {
			$type = 'number';
		}
		elseif ($tipo=='LIST') {
			$type = 'list';
		}
        else {    	
            echo "Não achei este tipo ".$tipo. PHP_EOL;
			die;
    	}
    	return $type;
    }
	
	private function populateIgnoreList($int_to) {
		
		$ingore['GPA'] = array (
			"categoria",
			"sku",
	//		"marca",
			"name", 
			"weight",
			"height", 
			"width", 
			"length", 
			"imagem1",
			"imagem2",
			"imagem3",
			"imagem4",
			"imagem5",
			"imagem6",
			"ean1", 
			"description", 
			"iskit"
		); 
		
		$this->ignore_list = array();
		if (array_key_exists($int_to, $ingore)) {
			$this->ignore_list = $ingore[$int_to];
		}
	}
    
    private function getFieldValue($fieldId, $auth_data) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $auth_data = json_decode($auth_data);
        $endPoint = 'https://'.$auth_data->site.'/api/values_lists?code='.$fieldId;
		$cnt = 1;
		$max = 10;
		while ($cnt++ <= $max) {
			$field_values = $this->getRequest( $endPoint, $auth_data->apikey);
			if (($this->responseCode == 429) || ($this->responseCode == 504)) {
				echo " Dormindo por 1 minuto pois deu ".$this->responseCode ."\n";
				sleep(60);
			}	
			else {
				$cnt = $max+1;
			}
		}
       
	    if ($this->responseCode == 404) { 
             $erro = 'Não encontrou os valores da lista '.$fieldId;
             echo $erro."\n";
             $this->log_data('batch',$log_name, $erro ,"W");
             return false;
         }
	   
        if ($this->responseCode !== 200) {
             $erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true);
             echo $erro."\n";
             $this->log_data('batch',$log_name, $erro ,"E");
			 die;
             return false;
         }

         $field_values = json_decode($field_values);
         $field_values = $field_values->values_lists[0]->values;
 
         $values = array();      
         foreach($field_values as $key=>$value){
            array_push($values, (object)[
                'FieldValueId' => $value->code,
                'Value' => $value->label,
                'IsActive' => 'true',
                'Position' => $key,
            ]);
         }

         return json_encode($values);
		

	} 

    private function findAndSaveSpecifications($int_to, $auth_data, $id_integration, $category_id, $category_mkt_id, $categoriesTree) {

        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->model_atributos_categorias_marketplaces->removeByCategoryAndIntTo($category_mkt_id, $int_to); 

        //$specifications = $this->findSpecificationsByIds($categoriesTree);
        $auth_data_json = json_decode($auth_data);
        $endPoint = 'https://'.$auth_data_json->site.'/api/products/attributes?hierarchy='.$category_id;
        $cnt = 1;
        $max = 10;
        while ($cnt++ <= $max) {
          $specifications_json = $this->getRequest( $endPoint, $auth_data_json->apikey);
          if (($this->responseCode == 429) || ($this->responseCode == 504)) {
            echo " Dormindo por 1 minuto pois deu ".$this->responseCode ."\n";
            sleep(60);
          }	
          else {
            $cnt = $max+1;
          }
        }
        if ($this->responseCode !== 200) {
             $erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true);
             echo $erro."\n";
             $this->log_data('batch',$log_name, $erro ,"E");
             die;
             return;
         }
 
         $specifications = json_decode($specifications_json, true);
         $specifications = $specifications['attributes'];
      
        foreach ($specifications as $specification) {
            if (in_array($specification['code'],$this->ignore_list)) {
            	continue;
            }
            $value = null;
            $type = $this->getFieldType($specification['type']);
            if($type == 'list'){
                $value = $this->getFieldValue($specification['code'], $auth_data);
                if ($value === false) { // não encontrei o valor da lista então transformo em string 
                  $type = 'string';
                  $value = null;
                }
            }
			if ($int_to== 'GPA') {
				if ($specification['code'] == 'dimensao_do_produto' ) {
					$specification['label'] = 'Tamanho';
				}
			} 

			echo "    ".$specification['code'].": ".$specification['label']." \n";
            $specification_data = array(
                'id_integration' 	=> $id_integration,
                'id_categoria' 		=> $category_mkt_id,
                'id_atributo' 		=> $specification['code'],
                'obrigatorio' 		=> $specification['required'] === true ? 1 : 2,
                'int_to' 			=> $int_to,
                'variacao' 			=> $specification['variant'] === true ? 1 : 0,
                'nome' 				=> $specification['label'],
                'tipo' 				=> $type,
                'multi_valor' 		=> 0 ,
                'valor' 			=> $value,
                'atributo_variacao' => 0,
                'tooltip' 			=> $specification['description'],
            );

            if (!$this->exists($id_integration, $category_mkt_id, $specification['code'])) {
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

    private function getRequest($url, $api_key){

        $this->header = [
            'content-type: application/json',
            'accept: application/json;charset=UTF-8',
            'Authorization:'. $api_key,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $err        = curl_errno($ch);
        $errmsg     = curl_error($ch);
        $header     = curl_getinfo($ch);
        
        curl_close($ch);
        return $this->result;
	
    }

}
