<?php
/*
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Apiitem extends Admin_Controller  
{
*/   

require APPPATH . '/libraries/REST_Controller.php';
     
class Products extends REST_Controller {
    
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

		$sql = "SELECT * FROM categories_mkts WHERE id_integration = 13";
		$query = $this->db->query($sql);
		$mktcat = $query->result_array();
		$mktcats = Array();
		foreach ($mktcat as $ind => $val) {
			$mktcats[$val['id_cat']] = $val['id_mkt'];
		}
		$sql = "SELECT * FROM products WHERE id in (52) ORDER BY id DESC";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
		    $cat = json_decode($row['category_id']);
		    $cat = $mktcats[$cat[0]];
			$id = $this->inserePrdBling ($row,$apikey,$cat,$url);
			// insere o filho apontando pro pai
			break;
	    }
        $this->response("-FILE LOADED", REST_Controller::HTTP_OK);
    } 

/* XML PRODUTO BLING
<produto>
   <codigo></codigo>
   <descricao></descricao>
   <situacao>Ativo</situacao>
   <descricaoCurta></descricaoCurta>
   <descricaoComplementar></descricaoComplementar>
   <un>Pc</un>
   <vlr_unit></vlr_unit>
   <peso_bruto></peso_bruto>
   <peso_liq></peso_liq>
   <class_fiscal>1000.01.01</class_fiscal>
   <marca></marca>
   <origem>0</origem>
   <estoque></estoque>
   <deposito>
      <id></id>
      <estoque></estoque>
   </deposito>
   <gtin></gtin>
   <largura></largura>
   <altura></altura>
   <profundidade></profundidade>
   <estoqueMinimo>1.00</estoqueMinimo>
   <estoqueMaximo>100.00</estoqueMaximo>
   <cest>28.040.00</cest>
   <condicao>Novo</condicao>
   <freteGratis>N</freteGratis>
   <producao>P</producao>
   <dataValidade>20/11/2019</dataValidade>
   <unidadeMedida>Centímetros</unidadeMedida>
   <garantia>6</garantia>
   <itensPorCaixa>1</itensPorCaixa>
   <volumes>1</volumes>
   <imagens>
     <url></url>
   </imagens>
</produto>

*/

	function inserePrdBling ($row,$apikey,$cat,$url) {	
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
   <situacao>Ativo</situacao>
   <descricaoCurta></descricaoCurta>
   <descricaoComplementar></descricaoComplementar>
   <un>Un</un>
   <vlr_unit></vlr_unit>
   <peso_bruto></peso_bruto>
   <peso_liq></peso_liq>
   <class_fiscal>1000.01.01</class_fiscal>
   <marca></marca>
   <origem>0</origem>
   <estoque></estoque>
   <deposito>
      <id>3075788878</id>
      <estoque></estoque>
   </deposito>
   <gtin></gtin>
   <largura></largura>
   <altura></altura>
   <profundidade></profundidade>
   <estoqueMinimo>1.00</estoqueMinimo>
   <estoqueMaximo>100.00</estoqueMaximo>
   <categoria><id></id><descricao>FOGAO</descricao></categoria>
   <cest>28.040.00</cest>
   <condicao>Novo</condicao>
   <freteGratis>N</freteGratis>
   <producao>P</producao>
   <dataValidade>20/11/2019</dataValidade>
   <unidadeMedida>Centímetros</unidadeMedida>
   <garantia>6</garantia>
   <itensPorCaixa>1</itensPorCaixa>
   <volumes>1</volumes>
   <imagens>
     <url></url>
   </imagens>
</produto>
XML;
		$xml = new SimpleXMLElement($pruebaXml);
		$xml->codigo[0] = $row['sku'];
		$xml->descricao[0] = htmlspecialchars($row['name'], ENT_QUOTES, "utf-8");
		$xml->descricaoCurta[0] = htmlspecialchars($row['description'], ENT_QUOTES, "utf-8");
		$xml->vlr_unit[0] = $row['price'];
		$xml->peso_bruto[0] = $row['peso_bruto'];
		$xml->peso_liq[0] = $row['peso_liquido'];
		$brand_id = json_decode($row['brand_id']);
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $this->db->query($sql, $brand_id);
		$brand = $query->row_array();
		$xml->marca[0] = $brand['name'];
		$xml->estoque[0] = $row['qty'];
		$xml->gtin[0] = $row['EAN'];
		$xml->altura[0] = $row['altura'];
		$xml->largura[0] = $row['largura'];
		$xml->profundidade[0] = $row['profundidade'];
		$xml->deposito[0]->estoque[0] = $row['qty'];
		$xml->categoria[0]->id[0] = $cat;
		$dados = $xml->asXML();
		print $dados;
		$posts = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);

		$retorno = $this->executeInsertProduct($url, $posts);
		$retorno = json_decode($retorno,true);
		$retorno = $retorno['retorno'];
		var_dump($retorno);
		if (isset($retorno['erros'])) {
			$id = 0;
		    // tratar erro	
		} else {
			$id = $retorno['produtos'][0][0]['produto']['id'];
//			if ($myid > 0) {   // nao insere o pai
//			$sql = "REPLACE INTO categories_mkts VALUES (".$myid.",13,'".$id."')";
//			$query = $this->db->query($sql);
//			} 
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
	    return $response;
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