<?php

require APPPATH . "controllers/BatchC/SellerCenter/OCC/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;
// php index.php BatchC/SellerCenter/OCC/Collection sync 
 class Collection extends Main
{
	//abstract function run($id = null, $params = null);
	
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
        $this->load->model('model_collections');

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
            echo PHP_EOL . "FIM SYNC COLLECTIONS" . PHP_EOL;
            $this->log_data('batch',$log_name,'finish',"I");
            $this->gravaFimJob();
            die;
        }

        $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
        if($integration){
            echo 'Sync: '. $integration['int_to']."\n";
            $this->syncIntTo($integration['id'], $integration['int_to'], $integration['auth_data']);
        }


        echo PHP_EOL . "FIM SYNC COLLECTIONS" . PHP_EOL;
        $this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
    }

    function syncIntTo($id_integration, $int_to, $auth_data) {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $endPoint = '/ccadmin/v1/collections?asHierarchy=true';
        $collections_json = $this->getCollections($int_to, $auth_data, $endPoint);
        echo 'true';
		if (($this->responseCode == 429) || ($this->responseCode == 504)) {
			sleep(60);
			$collections_json = $this->getCollections($int_to, $auth_data, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' RESPOSTA '.print_r($this->result,true); 
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
        $collections = json_decode($collections_json);
        $collections = $collections->items;

    
        foreach ($collections as $collection) {

            $this->save($auth_data, $id_integration, $collection, $int_to);

        }

    }

    private function save($auth_data, $id_integration, $collection, $int_to) {
        
        echo 'Collection: '. $collection->id . PHP_EOL;
        echo "nome = ".$collection->displayName."\n";
        $path = str_replace("/Navegação na Vitrine/","",$collection->categoryPaths[0]);
        echo "path = ".$path."\n";
        $collectionUpdate = $this->model_collections->getCollectionByMktpId($collection->id);

        if($collectionUpdate){

            $data = array(
				'mktp_id' => $collection->id,
				'name' => $collection->displayName,
				'long_description' => $collection->longDescription,
				'path' => $path,
                'active' => $collection->active  === true ? 1 : 0
			);

            $this->model_collections->update($data, $collectionUpdate['id']);
            echo 'update '.$collection->id. "\n";

        }else{

            $data = array(
				'mktp_id' => $collection->id,
				'name' => $collection->displayName,
				'long_description' => $collection->longDescription,
				'path' => $path,
                'active' => $collection->active  === true ? 1 : 0
			);

          $this->model_collections->create($data);

        }

    }

    private function getCollections($int_to, $auth_data, $end_point)
    {
        $this->process($int_to, $end_point);
        return $this->result;
    }

    
}