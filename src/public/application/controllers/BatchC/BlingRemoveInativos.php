<?php
/*
Remove os produtos que foram inativados do Bling 
*/   

 class BlingRemoveInativos extends BatchBackground_Controller {
		
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
		$retorno = $this->removeBlingInative();

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
	
    function removeBlingInative()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$apikeys = $this->getBlingKeys();
		$not_int_to = " AND p.int_to != 'B2W' AND p.int_to != 'CAR' ";  // Não trago mais a B2W e Carrefour 
		$not_int_to = " AND (p.int_to = 'VIA') ";  // Agota só VIA 
		
		$sql = "select p.id as id,int_id,p.company_id,prd_id,p.int_to from prd_to_integration p, integrations i where p.int_type = 13 AND status = 0 and int_id = i.id ".$not_int_to." order by prd_id";
		$query = $this->db->query($sql);
		$mktlkd = $query->result_array();
		foreach ($mktlkd as $ind => $val) {
			// Verifica se precisa sair
			$sql = "SELECT * FROM bling_ult_envio WHERE qty > 0 AND int_to = '".$val['int_to']."' AND prd_id = '".$val['prd_id']."'";
			$cmd = $this->db->query($sql);
			if($cmd->num_rows() > 0) {    // Existe um antigo
				echo "EXISTE 1 NO BLING...".$old['prd_id']." ".$old['skubling']."\n";
				$old = $cmd->row_array();
				
				// precisa cair do bling
				//echo "TEM QUE ZERAR...\n";
				//$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
				$apikey = $apikeys[$old['int_to']];
				
				$code = $old['skubling'];
				
				$sql = "SELECT name FROM products WHERE id = ".$old['prd_id'];
				$prdsql = $this->db->query($sql);
				$descricao = $prdsql->row_array();
				
				$outputType = "json";
				$url = 'https://bling.com.br/Api/v2/produto/' . rawurlencode($code) . '/' . $outputType;
				echo $url."\n";
				$retorno = $this->executeDeleteProduct($url, $apikey,$code,$descricao['name']);					
				if ($retorno != '201') {
					// deu erro. 
					continue;
				}
				//echo "ZERADO DO BLING\n-------------------\n";
				// precisa ser excluido de bling_ult_envio
				//echo "ZERA ESTOQUE DO ULT_ENVIO\n-------------------\n";
				$sql = "UPDATE bling_ult_envio SET qty = 0, qty_atual = 0 WHERE int_to = '".$old['int_to']."' AND prd_id=".$old['prd_id'];
				$cmd = $this->db->query($sql);
			}
			// precisa ser excluido de prd_to_integration
			//echo "DELETADO DA INTEGRACAO\n-------------------\n";
			//$sql = "DELETE FROM prd_to_integration WHERE id = ".$val['id'];
			$sql = "update prd_to_integration set status=0  WHERE id = ".$val['id'];
			$cmd = $this->db->query($sql);
		}
		
		
		$not_int_to = " AND pi.int_to != 'B2W' AND pi.int_to != 'CAR' ";  // Não trago mais a B2W e Carrefour 
		$not_int_to = " AND (pi.int_to = 'VIA') ";  // Agora só VIA 
		// Verifico se o produto ficou inativo ou incompleto e coloco disabled do bling e removo do envio 
		$sql = "SELECT DISTINCT b.*, p.name as descricao FROM bling_ult_envio b ";
		$sql.= " LEFT JOIN products p ON p.id=b.prd_id "; 
		$sql.= " LEFT JOIN prd_to_integration pi ON pi.prd_id=b.prd_id AND b.int_to = pi.int_to";
		$sql.= " WHERE (p.status!=1 OR p.situacao=1) AND pi.status_int!=90 ".$not_int_to;
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
	    	$apikey = $apikeys[$row['int_to']];
	    	
			$outputType = "json";
			$url = 'https://bling.com.br/Api/v2/produto/' . rawurlencode($row['skubling']) . '/' . $outputType;
			echo $url."\n";
			$retorno = $this->executeDeleteProduct($url, $apikey,$row['skubling'],$row['descricao']);					
			if ($retorno != '201') {
				// deu erro. 
				continue;
			}				
			$int_date_time = date('Y-m-d H:i:s');
			$sql = "UPDATE bling_ult_envio SET data_ult_envio ='".$int_date_time."' WHERE id = ".$row['id'];
			$cmd = $this->db->query($sql);
			$sql = "UPDATE prd_to_integration SET status=0, status_int=90, date_last_int = '".$int_date_time."' WHERE int_to='".$row['int_to']."' AND prd_id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
	    }

	}		
	
	function executeDeleteProduct($url,$apikey,$code, $descricao) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<produto>
   <codigo></codigo>
   <descricao></descricao>
   <estoque>0</estoque>
</produto>
XML;

		$xml = new SimpleXMLElement($pruebaXml);
		$xml->codigo[0] = $code;
		$xml->descricao[0] = $descricao;
		$dados = $xml->asXML();
		//var_dump($dados);
		$data = array (
		    "apikey" => $apikey,
		    "xml" => rawurlencode($dados)
		);

	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl_handle, CURLOPT_POST, count($data));
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);	
		$httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		if (!($httpcode=="201") ) {
			echo "Erro na respota do bling. httpcode=".$httpcode."\n";
			echo " URL: ". $url. "\n"; 
			echo " RESPOSTA BLING: ".print_r($response,true)." \n"; 
			echo " Dados enviados: ".print_r($data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO [ site:'.$url.' - httpcode: '.$httpcode.' RESPOSTA BLING: '.print_r($response,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
		}
		curl_close($curl_handle);
	    return $httpcode;
	}

	/* ********************************************
		Não pode deletar do BLING pq ele não sincroniza 
		a exclusão, zerando o estoque o produto fica 
		inativo no bling e nos marketplaces
	********************************************  */
}

?>