<?php

require APPPATH . "controllers/BatchC/SellerCenter/RD/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;
// php index.php BatchC/SellerCenter/RD/Category sync

class Category extends Main
{
    const FILENAME = 'categoriesMapping.xlsx';
    var $categories_mapping = array();
    var $rd_seller_field = null;

    //abstract function run($id = null, $params = null);

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
        $this->load->model('model_settings');
        $this->load->model('model_categorias_marketplaces');
        $this->load->model('model_category');
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->library('excel');

    }

    // php index.php BatchC/SellerCenter/RD/Category sync null Zema
    function sync($id = null, $params=null) {

        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        if(!is_null($params)){
            $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
            if($integration){
                $this->rd_seller_field = $this->model_settings->getValueIfAtiveByName('rd_seller_field_'.strtolower($params));
                if(!$this->rd_seller_field){
                    echo "Parametro rd_seller_field_".strtolower($params)." não criado ou inativo\n";
                }
                else {
                    echo 'Sync: '. $integration['int_to']."\n";
                    $this->syncIntTo($integration['id'], $integration['int_to'], $integration['auth_data'], 0, 100);
                    //  $this->updateSellerIdOCC($integration['int_to'], true);
                }
            }
            else {
                echo PHP_EOL ."INTEGRACAO COM ".$params." AINDA NÂO EXISTE".PHP_EOL;
            }
        }
        else {
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }

        echo PHP_EOL . "FIM SYNC CATEGORIAS" . PHP_EOL;
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function syncIntTo($id_integration, $int_to, $auth_data, $page, $size) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $credentials = json_decode($auth_data);
        $auth = $this->auth($credentials->api_url, $credentials->grant_type, $credentials->client_id, $credentials->client_secret);

        $endPoint = '/marketplace/catalogs/categories?page='.$page.'&size='.$size;

        $this->categories_active = array();

        $categories_json = $this->getCategories($credentials, $auth, $endPoint);
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

        if (($this->responseCode == 429) || ($this->responseCode == 504)) {
            sleep(60);
            $productBase = $this->getCategories($int_to, $auth_data, $endPoint);
        }

        if ($this->responseCode !== 200) {
            $erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true);
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return;
        }

        $categories = json_decode($this->result);

        foreach ($categories as $category) {
            // $this->findSpecifications($int_to, $auth_data, $id_integration, $category->id,  $category->name);
            $this->save($auth_data, $id_integration, $category->id,  $category->name, $int_to);
        }

        if (count($categories) > 0) {
            $this->syncIntTo($id_integration, $int_to, $auth_data, $page + 1, $size);
        }

        //$this->inactivateCategories($this->categories_active ,$int_to);

    }

    private function save($auth_data, $id_integration, $category_mkt_id, $name, $int_to) {
        echo 'Category: '. $category_mkt_id . PHP_EOL;
        echo "nome = ".$name."\n";

        $this->categories_active[$category_mkt_id] = $name;

        $sql = "SELECT * FROM categories WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        if (count($query->result_array()) == 0) {
            $category = array(
                'name' => $name,
                'active' => 1,
                'qty_products' => 0
            );

            $this->db->insert('categories', $category);
            $categories_id = $this->db->insert_id();
        }
        else {
            $categories = $query->row_array();
            $categories_id = $categories['id'];
        }

        $categoria_todos_marketplaces = array(
            'id_integration' => $id_integration,
            'id' => $category_mkt_id,
            'nome' => $name,
            'int_to' => $int_to
        );

        $this->db->replace('categorias_todos_marketplaces', $categoria_todos_marketplaces);

        $sql = "SELECT * FROM categorias_marketplaces WHERE int_to = ? and category_id =  ? ";
        $query = $this->db->query($sql, array($int_to, $categories_id));
        if (count($query->result_array()) == 0) {
            $categorias_marketplaces = array(
                'int_to' => $int_to,
                'category_id' => $categories_id,
                'category_marketplace_id' => $category_mkt_id
            );

            $this->db->replace('categorias_marketplaces', $categorias_marketplaces);
        }

    }

    private function getCategories($credential, $auth, $endPoint)
    {
        // $this->process($int_to, $end_point);
        $this->process($credential, $auth, $endPoint);
        return $this->result;
    }

    private function findSellerIdField($specifications) {
        foreach($specifications as $specification) {
            if ($specification['id'] == $this->rd_seller_field) {
                return true;
            }
        }
        return false;
    }

    private function saveSpecification($specifications, $id_integration, $category_mkt_id, $int_to, $isvariant, $prd_sku){

        foreach ($specifications as $specification) {

            if ($specification['hidden'] === true) continue ;

            $field_type = $this->getFieldType($specification['type']);

            $values = array();
            if ($field_type == 'list') {
                $values = array();
                foreach($specification['values'] as $index => $value){
                    array_push($values,(object)["FieldValueId"=> $value,"Value" => $value,"IsActive" => true,"Position" => $index]);
                }
            }

            echo "    ".$specification['label']." \n";
            $specification_data = array(
                'id_integration'    => $id_integration,
                'id_categoria'      => $category_mkt_id,
                'id_atributo'       => $specification['id'],
                'obrigatorio'       => $specification['required'] === true ? 1 : 2,
                'int_to'            => $int_to,
                'variacao'          => $isvariant,
                'nome'              => $specification['label'],
                'tipo'              => $field_type,
                'multi_valor'       => $specification['multiSelect'] === true ? 1 : 0 ,
                'valor'             => json_encode($values),
                'atributo_variacao' => 0,
                'prd_sku'           => $prd_sku
            );
            if (!$this->exists($id_integration, $category_mkt_id, $specification['id'])) {
                $this->db->insert('atributos_categorias_marketplaces', $specification_data);
            }
            else {
                $this->db->replace('atributos_categorias_marketplaces', $specification_data);
            }
        }

    }

    private function getFieldType($field) {
        $type = 'string';
        if (($field == 'shortText') || ($field == 'richText') || ($field == 'checkbox') || ($field == 'longText')) {
            $type = 'string';
        }
        elseif ($field=='number'){
            $type = 'number';
        }
        elseif ($field=='enumerated') {
            $type = 'list';
        }
        elseif ($field == 'date') {
            $type = 'date';
        }

        else {
            echo "Não achei este tipo ".$field. PHP_EOL;
            die;
        }
        return $type;
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
            }
        }
    }

}
