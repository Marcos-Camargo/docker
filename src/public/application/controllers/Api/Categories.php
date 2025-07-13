<?php
/*
SW Serviços de Informática 2019

Controller de Categorias

*/  
require APPPATH . '/libraries/REST_Controller.php';
     
class Categories extends REST_Controller {
    
	  /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function __construct() {
       parent::__construct();
    }
       
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
	public function index_get($id = NULL)
	{
        if(!empty($id)){
			$more = 'categoria/' . $id . '/';
        }else{
			$more = "categorias/";
        }
		$idCategoria = "{idCategoria}";
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$outputType = "json";
		$url = 'https://bling.com.br/Api/v2/'. $more . $outputType;
		$retorno = $this->executeGetCategories($url, $apikey);
		echo $retorno;
	}
      
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_post()
    {
/*
<?xml version="1.0" encoding="UTF-8"?>SchumacherFull/Conectala100
<categorias>
  <categoria>
    <descricao>Casa, Mesa e Banho</descricao>
    <idcategoriapai>0</idcategoriapai>
  </categoria>
</categorias>
*/		    

		$linhas = 0;
		$more = "";
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$url = 'https://bling.com.br/Api/v2/produto/json/';

		$more = "categorias/";
		$outputType = "json";
		$url2 = 'https://bling.com.br/Api/v2/'. $more . $outputType;
		$mktcat = $this->executeGetCategories($url2, $apikey);
		$mktcat = json_decode($mktcat,true);
		$mktcat = $mktcat['retorno']['categorias'];
//var_dump($mktcat);		
		$sql = "TRUNCATE TABLE cat_bling";
		$query = $this->db->query($sql);
		$mktcats = Array();
		foreach ($mktcat as $key => $cat) {
			$sql = "INSERT INTO cat_bling VALUES ('".$cat['categoria']['id']."','".$cat['categoria']['descricao']."','".$cat['categoria']['idCategoriaPai']."')";
			$query = $this->db->query($sql);
		}
		$sql = "select a.cat as cat , a.id as id from cat_bling a where a.id_pai = 0 union select concat(b.cat,' > ',a.cat) , a.id from cat_bling a, cat_bling b where a.id_pai <> '0' and a.id_pai = b.id";
		$query = $this->db->query($sql);
		$mktcat = $query->result_array();
		$mktcats = Array();
		foreach ($mktcat as $ind => $val) {
			$mktcats[$val['cat']] = $val['id'];
		}
		$sql = "SELECT * FROM categories WHERE id in (391,403,405,408,411) ORDER BY id DESC";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
		    $cat = $row['name'];
		    list ($pai, $filho) = explode('>', $cat);
			if (isset($mktcats[$cat])) {
				
			} else {
				if (isset($mktcats[$pai])) {
					$idpai = $mktcats[$pai];
				} else {
					// insere o pai
					$idpai = $this->insereCatBling (0,$apikey,$pai,0);
					$mktcats[$pai] = $idpai;
					// pega id do pai			
				}	
				$id = $this->insereCatBling ($row['id'],$apikey,$filho,$idpai);
				// insere o filho apontando pro pai
				$linhas++;
				if ($linhas == 50) {
					break;
				}
			} // Já existe
	    }
        $this->response("-FILE LOADED", REST_Controller::HTTP_OK);
    } 

	function insereCatBling ($myid,$apikey,$cat,$idpai) {	
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<categorias></categorias>
XML;
		$xml = new SimpleXMLElement($pruebaXml);
		$n1 = simplexml_load_string('<categoria></categoria>');
		$tag = "descricao";	
//		print $cat;
		$n1->addchild($tag, htmlspecialchars($cat, ENT_QUOTES, "utf-8"));
		$tag = "idcategoriapai";	
		$n1->addchild($tag,$idpai);
		$this->sxml_append($xml, $n1);
		$dados = $xml->asXML();
//		print $dados;
		$url = 'https://bling.com.br/Api/v2/categoria/json/';
		$posts = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);
		$retorno = $this->executeInsertCategory($url, $posts);
		$retorno = json_decode($retorno,true);
		$retorno = $retorno['retorno'];
		if (isset($retorno['erros'])) {
			$id = 0;
		    // tratar erro	
		} else {
			$id = $retorno['categorias'][0][0]['categoria']['id'];
			if ($myid > 0) {   // nao insere o pai
			$sql = "REPLACE INTO categories_mkts VALUES (".$myid.",13,'".$id."')";
			$query = $this->db->query($sql);
			} 
		}	
		return $id;
	}	


	function executeInsertProduct($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    // return $response;
	}
	
	
	function Insere($xml) {
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$url = 'https://bling.com.br/Api/v2/produto/json/';
		// var_dump($xml);
		//print_r($xml,true);
		$dados = $xml->asXML();
		print $dados;
		$posts = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);
		$retorno = $this->executeInsertProduct($url, $posts);
		return $retorno;
	}
	
	function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from) {
	    $toDom = dom_import_simplexml($to);
	    $fromDom = dom_import_simplexml($from);
	    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
	}
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_put($id)
    {
        $input = $this->put();
        $this->db->update('items', $input, array('id'=>$id));
     
        $this->response(['Item updated successfully.'], REST_Controller::HTTP_OK);
    }
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_delete($id)
    {
        $this->db->delete('items', array('id'=>$id));
       
        $this->response(['Item deleted successfully.'], REST_Controller::HTTP_OK);
    } 
    
	function executeGetCategories($url, $apikey){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}
	function executeInsertCategory($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}    	
}