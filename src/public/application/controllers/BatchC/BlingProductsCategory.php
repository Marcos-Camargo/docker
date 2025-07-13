<?php
/*

Baixa as categorias de cada produto do Bling. Necessário para os Campos Customizados do ML 

*/   
class BlingProductsCategory extends BatchBackground_Controller {
		
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
		$retorno = $this->getCategoriaProdutos();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function executeUpdateProduct($url, $data){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
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

	function executeGetProduct($url, $apikey,$loja){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey . "&loja=" . $loja);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}		
	
	function getCategoriaProdutos() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// Pega somente a categoria do produtos - Rodar a cada 15 minutos 
				
		//$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$apikeys = $this->getBlingKeys();
		
		$not_int_to = " AND int_to != 'B2W' AND int_to != 'CAR' ";  // Não trago mais a B2W e CAR
		$not_int_to = " AND (int_to = 'VIA') ";  // Agora só Via Varejo
		
		$outputType = "json";
		$sql = "SELECT * FROM bling_ult_envio WHERE (categoria_bling is null OR categoria_bling = '1122016') ".$not_int_to;
		$query = $this->db->query($sql);
		$rows = $query->result_array();
		foreach ($rows as $ind => $row) {
			$code = $row['skubling'];  
			$url = 'https://bling.com.br/Api/v2/produto/' . $code . '/' . $outputType;
			// echo "-------------------------------------\n";
			$apikey = $apikeys[$row['int_to']];
			$retorno = $this->executeGetProduct($url, $apikey,$row['mkt_store_id']);
			$linkedmkt = json_decode($retorno,true);
			if (isset($linkedmkt['retorno']['erros'])) {
				echo 'produto '.$code.' não encontrado no bling'."\n";	
				$this->log_data('batch',$log_name,'produto '.$code.' nao encontrado no bling',"E");
				continue;
			} else {
				echo 'verificando o produto '.$code."\n";
			}
			$linkedmkt = $linkedmkt['retorno']['produtos'][0]['produto'];
			// var_dump($linkedmkt);
	 		if (!(isset($linkedmkt['categoria']))) {
	 			echo $code." sem categoria\n";
	 			continue;
	 		}
	 		$cat_id = $linkedmkt['categoria']['id'];
	 		$cat_desc = $linkedmkt['categoria']['descricao'];
			
			if ($cat_id == $row['categoria_bling']) {
				// Nada a alterar pois ninguem linkou o produto no Bling 
				continue;
			}
			// pode não ser mais nulo, então altero o bling
	 		echo $code . " : " . $cat_id . '-'. $cat_desc. "\n";
			$sql = "UPDATE bling_ult_envio set categoria_bling = '".$cat_id."' WHERE id = ".$row['id'];  
			$query = $this->db->query($sql);
			
			if ($cat_id =='1122016') {
				// categoria padrão
				continue;
			}
			// Alterou a categoria. Vejo se tem campos customizados aguardando para serem enviados
		    // Adiciona campos das categorias do ML 
		    // leio os valores dos campos por produto 
		
			$this->load->model('model_atributos_categorias_marketplaces', 'myatributoscategorias');
			$this->load->model('model_products', 'myproducts');		
			$produtos_atributos = $this->myatributoscategorias->getAllProdutosAtributos($row['prd_id']);
			// vejo se tem campos customizados
			if (empty($produtos_atributos)) {
				// Sem nenhum campo 	
				continue;
			}
			$criaChildXml = true;
			
			$produto = $this->myproducts->getProductData(0,$row['prd_id']); 
		
			$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
</produto>
XML;
			$xml = new SimpleXMLElement($pruebaXml);
			$xml->codigo[0] = $row['skubling'];
			if ($row['int_to']=="ML") {
				$xml->descricao[0] = htmlspecialchars(substr($produto['name'],0,60), ENT_QUOTES, "utf-8");  // SO 60 chars por causa do ML
			} else {
				$xml->descricao[0] = htmlspecialchars($produto['name'], ENT_QUOTES, "utf-8");
			}
			$xml->descricaoCurta[0] = htmlspecialchars($produto['description'], ENT_QUOTES, "utf-8");
				
			// $produtos_atributos =array();
			foreach ($produtos_atributos as $produto_atributo) {
				if ($criaChildXml) {
					$xml->addChild("camposCustomizados");
					// $xml->camposCustomizados->addChild('marca',$brand['name']);
					$criaChildXml=false; 
				}
				$id_atributo =  $produto_atributo['id_atributo']; 
				$valor = $produto_atributo['valor'];
				$atributo = $this->myatributoscategorias->getAtributo($id_atributo);
				if ($atributo['tipo']=='list') {
					$valores = json_decode($atributo['valor'],true );
					foreach ($valores as $valId) {					
						if ($valId['id'] == $produto_atributo['valor']) {
							$valor = $valId['name'];
						}
					}
					
				}
				$xml->camposCustomizados->addChild(strtolower($id_atributo),$valor);
			} 
			$dados = $xml->asXML();
			var_dump($dados);
			echo '---------------';
			$url = 'https://bling.com.br/Api/v2/produto/'.$row['skubling'].'/json/';
			$posts = array (
			    "apikey" => $apikey,
			    "xml" => rawurlencode($dados)
			);
			echo "**************** VAI MANDAR BLING ***************\n";
			$retorno = $this->executeUpdateProduct($url, $posts);
			$retorno = json_decode($retorno,true);
			$retorno = $retorno['retorno'];
			if (array_key_exists('erros',$retorno)) {
				if ($retorno['erros'][0]['cod']=163) {
					echo $retorno['erros'][0]['msg']."\n";
					$this->log_data('batch',$log_name, 'ERRO [site:'.$url.' RESPOSTA BLING: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($posts,true),"W");
				}
				else {
					echo "Erro na respota do bling\n";
					echo " URL: ". $url. "\n"; 
					echo " RESPOSTA BLING: ".print_r($retorno,true)." \n"; 
					echo " Dados enviados: ".print_r($posts,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO [site:'.$url.' RESPOSTA BLING: '.print_r($retorno,true).' DADOS ENVIADOS:'.print_r($posts,true),"E");
				}	
				// Volto a tras a categoria padrão para que tente enviar de novo
				$sql = "UPDATE bling_ult_envio set categoria_bling = '1122016' WHERE id = ".$row['id'];  
				$query = $this->db->query($sql);
				
			}
		}	
	}

}


?>