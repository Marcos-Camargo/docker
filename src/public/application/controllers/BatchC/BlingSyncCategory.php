<?php
/*
 
 Sincroniza os SKUs do Bling (ML e Magalu)

*/   
class BlingSyncCategory extends BatchBackground_Controller {
		
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
		$this->load->model('model_products');
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
		
		/* faz o que o job precisa fazer */
		$this->getLinkedCats();  // Faz para cada loja de cada bling 
		
	    $sql = "TRUNCATE TABLE cat_bling";
		$query = $this->db->query($sql);
		$apikeys = $this->getBlingKeys();
		$feitos = array();
		foreach($apikeys as $mkt => $apikey) {
			echo 'Pegando categorias do marketplace '.$mkt."\n";
			if (!(in_array($apikey,$feitos))) {
				$feitos[] = $apikey;
				$this->syncCategories($apikey);  // só precisa fazer uma vez por bling 
			}
		}
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getBlingKeys() {
		$apikeys = Array();
		$sql = "select * from stores_mkts_linked where id_integration = 13";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}
		return $apikeys;
	}

    /**
     * Get All Linked Categories in BLING and Save them for Products Integration.
     *
     * @return Response
    */
	function getLinkedCats()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$apikeys = $this->getBlingKeys();
		$not_int_to = " AND apelido != 'B2W' AND apelido != 'CAR' ";  // Não trago mais a B2W e Carrefour 
		$not_int_to = " AND apelido = 'VIA' ";  // Agora só VIA 
		$sql = "select * from stores_mkts_linked WHERE id_integration = 13".$not_int_to;
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			$more = 'categoriasLoja/' . $row['id_loja'] . '/';
			//$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
			$apikey = $apikeys[$row['apelido']];
			$outputType = "json";
			$url = 'https://bling.com.br/Api/v2/'. $more . $outputType;
			$retorno = $this->executeGetCategories($url, $apikey);
			$linkedcats = json_decode($retorno,true);
			$linkedcats = $linkedcats['retorno'];
	 		if (isset($linkedcats['categoriasLoja'])) {
				$linkedcats = $linkedcats['categoriasLoja'];
				foreach($linkedcats as $ind => $lnk) {
					$cat = $lnk['categoriaLoja'];
					$sql = "REPLACE INTO categories_mkts_linked VALUES(13,".$row['id_mkt'].",'".$cat['idCategoria']."','".$cat['idVinculoLoja']."','".$cat['descricaoVinculo']."')";
					$query = $this->db->query($sql);
				}
			}
		}
        return;
	}

    /**
     * Sync Categories with BLING.
     *
     * @return Response
    */
    function syncCategories($apikey)
    {
		/*
		From BLING documentation	
		<?xml version="1.0" encoding="UTF-8"?>SchumacherFull/Conectala100
		<categorias>
		  <categoria>
		    <descricao>Casa, Mesa e Banho</descricao>
		    <idcategoriapai>0</idcategoriapai>
		  </categoria>
		</categorias>
		*/		    
		// Primeiro Sync Categorias Linkadas com BLING
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		
		// Agora Fase 2
		$more = "";
		// $apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$url = 'https://bling.com.br/Api/v2/produto/json/';

		$tem = true;
		$pagina = 0;		
		$mktcats = Array();
		while ($tem) {
			$pagina++;
			$more = "categorias/page=".$pagina."/";
			$outputType = "json";
			$url2 = 'https://bling.com.br/Api/v2/'. $more . $outputType;
			echo $url2 . "\n";
			$mktcat = $this->executeGetCategories($url2, $apikey);
			$mktcat = json_decode($mktcat,true);
			// var_dump($mktcat);
	 		if (isset($mktcat['retorno']['categorias'])) {
				$mktcat = $mktcat['retorno']['categorias'];
				foreach ($mktcat as $key => $cat) {
					$sql = "INSERT INTO cat_bling VALUES ('".$cat['categoria']['id']."','".trim($cat['categoria']['descricao'])."','".$cat['categoria']['idCategoriaPai']."')";
					$query = $this->db->query($sql);
				}
				$sql = "select a.cat as cat , a.id as id from cat_bling a where a.id_pai = 0 union select concat(b.cat,' > ',a.cat) , a.id from cat_bling a, cat_bling b where a.id_pai <> '0' and a.id_pai = b.id";
				$query = $this->db->query($sql);
				$mktcat = $query->result_array();
				$mktcats = Array();
				foreach ($mktcat as $ind => $val) {
					$mktcats[$val['cat']] = $val['id'];
				}
			} else {
				$tem = false;
			}	
		}
		$sql = "select * from categories where data_alteracao > date_sub(NOW(), interval 1 HOUR) ORDER BY id ";       // CHANGED IN THE LAST HOUR
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
		    $cat = $row['name'];
			if (isset($mktcats[$cat])) {
				
			} else {
			    $fcats = explode('>', $cat);
			    $fullcat = "";
			    $idant = 0;
			    $ccats = count($fcats) - 1;
			    for ($j = 0; $j <= $ccats; $j++) {
				    $fullcat .= trim($fcats[$j]);
					if (isset($mktcats[$fullcat])) {
						$idant = $mktcats[$fullcat];
					} else {
						// insere o item
						if ($j == $ccats) {
							$idant = $this->insereCatBling ($row['id'],$apikey,$fcats[$j],$idant);    // Pra só inserir na tabela o último
						} else {
							$idant = $this->insereCatBling (0,$apikey,$fcats[$j],$idant);   
						}
						$mktcats[$fullcat] = $idant;
						// pega id do novo pai			
					}	
					$fullcat .= " > ";
				}	
			} // Já existe
	    }
        return "CATEGORIES SYNCED WITH BLING";
    } 

	function insereCatBling ($myid,$apikey,$cat,$idpai) {	
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<categorias></categorias>
XML;
		$xml = new SimpleXMLElement($pruebaXml);
		$n1 = simplexml_load_string('<categoria></categoria>');
		$tag = "descricao";	
		$n1->addchild($tag, htmlspecialchars(trim($cat), ENT_QUOTES, "utf-8"));
		$tag = "idcategoriapai";	
		$n1->addchild($tag,$idpai);
		$this->sxml_append($xml, $n1);
		$dados = $xml->asXML();
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

	
	function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from) {
	    $toDom = dom_import_simplexml($to);
	    $fromDom = dom_import_simplexml($from);
	    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
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

?>