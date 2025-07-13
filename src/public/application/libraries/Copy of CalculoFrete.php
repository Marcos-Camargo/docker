<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CalculoFrete_copy {
	
	var $instance;
	
	public function __construct($cmd=null){
      	$this->instance = &get_instance();
        
    }

    function calculaTaxa($total_price) 
	{
		if ($total_price <= 40) {
			return(0.5);		
		}elseif ($total_price <= 70) {
			return(0.8);	
		}elseif ($total_price <= 100) {
			return(1);	
		}elseif ($total_price <= 150) {
			return(1.5);		
		}elseif ($total_price <= 200) {
			return(2);
		}elseif ($total_price <= 250) {
			return(3);	
		}else {
			return(3.5);	
		}
	}
	
	function verificaCorreios($row_ult) {
		$peso_cubico = ceil($row_ult['altura'] * $row_ult['largura'] *  $row_ult['profundidade'] /6000);	
		$sql = 'SELECT tipo_volume_codigo FROM freights_by_tipo_volume WHERE tipo_volume_codigo = ? LIMIT 1';
		$query = $this->instance->db->query($sql,array($row_ult['tipo_volume_codigo']));
		$resp = $query->result_array();

		  return (
		 	((float)$row_ult['peso_bruto'] <= 30) && 
		 	((float)$peso_cubico <= 30) &&
		 	((float)$row_ult['largura'] <= 105 )  && 
		 	((float)$row_ult['profundidade'] <= 105 ) && 
		 	((float)$row_ult['altura'] <= 105) && 
		 	(((float)$row_ult['largura']+(float)$row_ult['altura']+(float)$row_ult['profundidade']) <= 200 ) &&
		 	(count($resp) == 0)
			);
		
	}
	
	function cadastraCEP($cep) {
		$zip = substr('00000000'.$cep,-8); 
		$url =  'https://viacep.com.br/ws/'.$zip.'/json/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch); 
		if ($httpcode != 200 ) {
			return false;
		}
		$resp = json_decode($output,true);

		if (array_key_exists('erro', $resp)) {
			return false;
		}
		if (!array_key_exists('cep',$resp)) {
			return false;
		}
		$capitais = array(
			'Rio Branco/AC',
			'Maceió/AL',
			'Macapá/AP',
			'Manaus/AM', 
			'Salvador/BA',
			'Fortaleza/CE', 
			'Brasília/DF', 
			'Vitória/ES',
			'Goiânia/GO', 
			'São Luís/MA', 
			'Cuiabá/MT',
			'Campo Grande/MS',
			'Belo Horizonte/MG',
			'Belém/PA',
			'João Pessoa/PB', 
			'Curitiba/PR', 
			'Recife/PE',
			'Teresina/PI',
			'Rio de Janeiro/RJ', 
			'Natal/RN',
			'Porto Alegre/RS', 
			'Porto Velho/RO', 
			'Boa Vista/RR',
			'Florianópolis/SC',
			'São Paulo/SP', 
			'Aracaju/SE',
			'Palmas/TO', 
		); 
		$data = array(
				'zipcode' => $cep, 
				'city' => $resp['localidade'],
				'state' => $resp['uf'],
				'address' => $resp['logradouro'],
				'neighborhood' => $resp['bairro'],
				'capital' => in_array($resp['localidade'],$capitais),
			);
		$insert = $this->instance->db->insert('cep', $data);
		return true;
		
	}
	function calculaCorreios($fr) {
    	$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem = $fr['expedidor']['endereco']['cep'];
		
		// Pego as informações do CEP
		$sql = 'SELECT * FROM cep WHERE zipcode = '.$cepOrigem;
		$query = $this->instance->db->query($sql);
		$origem = $query->row_array();
		if (empty($origem)) { // não achei na base local
			if (!$this->cadastraCEP($cepOrigem)) { // Procuro na internet
				return array(
					'cep_origem' => $cepOrigem,
					'cep_destino' => $cepDestino,
					'erro' => 'CEP '.$cepOrigem.' não encontrado');
			}
			else {
				$sql = 'SELECT * FROM cep WHERE zipcode = '.$cepOrigem;
				$query = $this->instance->db->query($sql);
				$origem = $query->row_array();
			}
		}
		$destino = $origem; 
		if ($cepOrigem !=$cepDestino) {
			$sql = 'SELECT * FROM cep WHERE zipcode = '.$cepDestino;
			$query = $this->instance->db->query($sql);
			$destino = $query->row_array();
			if (empty($destino)) {  // não achei na base local
				if (!$this->cadastraCEP($cepDestino)) { // Procuro na internet
					return array(
						'cep_origem' => $cepOrigem,
						'cep_destino' => $cepDestino,
						'erro' => 'CEP '.$cepDestino.' não encontrado');
				}
				else {
					$sql = 'SELECT * FROM cep WHERE zipcode = '.$cepDestino;
					$query = $this->instance->db->query($sql);
					$destino = $query->row_array();
				}
			}
		}
		
		// Vejos se tem exceções a consulta normal
		$excecaoSedex = '';
		$excecaoDivisa = '';
		if ($origem['state'] != $destino['state']) {
			$sql = 'SELECT * FROM cep_exceptions WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino.'
			AND type=2';
			$query = $this->instance->db->query($sql);
			$excecaoDivisa = $query->row_array();
		}
		else {
			$sql = 'SELECT * FROM cep_exceptions WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino.'
			AND type=1';
			$query = $this->instance->db->query($sql);
			$excecaoSedex = $query->row_array();
		}
		
		if (empty($excecaoSedex)) { 
			if (empty($excecaoDivisa)) {
				// Se não tem exceção, consulto a tabela normal
				$sql = 'SELECT * FROM correios_states WHERE origin = "'.$origem['state'].'" AND destiny = "'.$destino['state'].'"';
				$query = $this->instance->db->query($sql);
				$states = $query->row_array();
				$nivel = $states['nivel'];
				if (strlen($nivel) == 1) { // Vejo se é capital x capital ou não 
					if (($origem['capital']) && ($destino['capital'])) {
						$nivel = 'N'.$nivel;
					}	else {
						$nivel = 'I'.$nivel;
					}
				}
			}
			else {
				$nivel = $excecaoDivisa['nivel'];
			}
		} else {
			$nivel = $excecaoSedex['nivel'];
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'nivel' => $nivel,
			'servicos' => array(
				'MINI' => array ('preco'=>0,'prazo'=>0),
				'PAC' => array ('preco'=>0,'prazo'=>0),
				'SEDEX' => array ('preco'=>0,'prazo'=>0),
				
			),
		);
		$mini = 0;
		$total_price = 0; 
		foreach ($fr['volumes'] as $item) {
			$total_price+= $item['valor'];
			$peso = $item['peso'];
			$peso_cubico = ceil($item['altura'] * 100 * $item['largura'] * 100 *  $item['comprimento'] * 100 /6000);

			if ($peso_cubico > 5 ) {
				if ($peso_cubico > $item['peso']) {
					$peso = $peso_cubico;
				}
			}
			$peso = ceil($peso * 1000);
			if (($peso <=300) && ($item['valor'] <= 100) && ($item['comprimento']<=0.24) && ($item['largura']<=0.16) && 
				($item['altura']<=0.02)) {
				$mini++;
			} 
			$sql = 'SELECT * FROM correios_prices WHERE nivel = "'.$nivel.'" AND start_weight <= '.$peso.' 
							AND end_weight >='.$peso.' ORDER BY CAST(price AS DECIMAL(12,2)) ASC ';
			$query = $this->instance->db->query($sql);
			$prices = $query->result_array();
			foreach($prices as $price) {
				$resposta['servicos'][$price['service']]['preco'] += $price['price'] * $item['quantidade'];
				if ($price['time'] > $resposta['servicos'][$price['service']]['prazo']) {
					$resposta['servicos'][$price['service']]['prazo'] = $price['time'];
				}
			}	
		
		}
		if ($mini != count($fr['volumes'])) { // se todos não forem mini, desconsidero o preço do mini. 
			$resposta['servicos']['MINI']['preco'] = 0;
		}
		if (($resposta['servicos']['PAC']['preco'] == 0) && ($total_price > 3000)) {
			unset($resposta['servicos']['PAC']);
		}
		if (($resposta['servicos']['SEDEX']['preco'] == 0) && ($total_price > 10000)) {
			unset($resposta['servicos']['SEDEX']);
		}	
		if (($resposta['servicos']['MINI']['preco'] == 0) && ($total_price > 100)){
			unset($resposta['servicos']['MINI']);
		}	
		return ($resposta);
		
	}  

	function lerCep($cep) 
	{
		
		$sql = 'SELECT * FROM cep WHERE zipcode = '.$cep;
		$query = $this->instance->db->query($sql);
		$origem = $query->row_array();
		if (empty($origem)) { // não achei na base local
			if (!$this->cadastraCEP($cep)) { // Procuro na internet
				return false;
			}
			else {
				$sql = 'SELECT * FROM cep WHERE zipcode = '.$cep;
				$query = $this->instance->db->query($sql);
				$origem = $query->row_array();
			}
		}
		return $origem;
	}

	function verificaEspecial($row_ult,$cep_dest) 
	{
		if ($row_ult['int_to'] == 'CAR') {
			if (in_array($row_ult['skubling'], array('D6BDFB51D22AF','92D6D92E2EC98','A3596C88A392F','63B7E861E8360',
				'03DBEE0C31ECF','856E0BEEF04A2','CD31080AF142C','DD773CBE19CCB'))) {
				$cep = $this->lerCep($cep_dest);
				if ($cep) {
					if (($cep['state'] == 'SP') || ($cep['state'] == 'RJ') || ($cep['state'] == 'MG') || ($cep['state'] == 'PR')) {
						return true;
					} 
				}
			}
		    return(false);
		}
		if ($row_ult['int_to'] == 'B2W') {
			if (in_array($row_ult['skubling'], array('D9B2D4A7F5833','9D628BA642FD8','1E8FED423A7EB','4DE7022A6D4DC',
				'C993A429A3A2A','45FE26CDA0E64','82310B7D28F7D','86596244FCEE6'))) {
				$cep = $this->lerCep($cep_dest);
				if ($cep) {
					if (($cep['state'] == 'SP') || ($cep['state'] == 'RJ') || ($cep['state'] == 'MG') || ($cep['state'] == 'PR')) {
						return true;
					} 
				}
			}
		    return(false);
		}
		if ($row_ult['int_to'] == 'VIA') {
			if (in_array($row_ult['skubling'], array('84DD5A29B2E1B','2608E1D36E5CB','201259B9A58CC','CBC594F74318C',
				'BAA23080A59A0','0D62040EB91AF','2EEB1672C3944','663650E11A3AB'))) {
				$cep = $this->lerCep($cep_dest);
				if ($cep) {
					if (($cep['state'] == 'SP') || ($cep['state'] == 'RJ') || ($cep['state'] == 'MG') || ($cep['state'] == 'PR')) {
						return true;
					} 
				}
			}
		    return(false);
		}
		return(false);
	}

	function verificaTipoVolume($row_ult,$origem,$destino) 
	{

		$sql = 'SELECT * FROM freights_by_tipo_volume WHERE tipo_volume_codigo = ? AND origin_state = ? AND destiny_state = ? ';
		$query = $this->instance->db->query($sql,array($row_ult['tipo_volume_codigo'],$origem,$destino));
		$resp = $query->result_array();
		return (count($resp));	
	}
	
	function calculaTipoVolume($fr,$origem,$destino ) {
		$capitais = array(
			'Rio Branco/AC',
			'Maceió/AL',
			'Macapá/AP',
			'Manaus/AM', 
			'Salvador/BA',
			'Fortaleza/CE', 
			'Brasília/DF', 
			'Vitória/ES',
			'Goiânia/GO', 
			'São Luís/MA', 
			'Cuiabá/MT',
			'Campo Grande/MS',
			'Belo Horizonte/MG',
			'Belém/PA',
			'João Pessoa/PB', 
			'Curitiba/PR', 
			'Recife/PE',
			'Teresina/PI',
			'Rio de Janeiro/RJ', 
			'Natal/RN',
			'Porto Alegre/RS', 
			'Porto Velho/RO', 
			'Boa Vista/RR',
			'Florianópolis/SC',
			'São Paulo/SP', 
			'Aracaju/SE',
			'Palmas/TO', 
		); 
		
		$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem  = $fr['expedidor']['endereco']['cep'];
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
		); 	
		
		$capital = in_array($destino['city'].'/'.$destino['state'], $capitais);
		if (!$capital) {
			$capital = $destino['capital_shipping'];
		}
		$servicos = array();
		foreach ($fr['volumes'] as $item) {
			$sql = 'SELECT * FROM freights_by_tipo_volume WHERE tipo_volume_codigo = ? AND origin_state = ? AND destiny_state = ? AND capital = ? order by price, time';
			$query = $this->instance->db->query($sql,array($item['tipo'],$origem['state'], $destino['state'], $capital));
			$transportadoras = $query->result_array();
			foreach ($transportadoras as $transportadora)
			{
				$key = $transportadora['ship_company']."|".$transportadora['service'];
				if (!key_exists($key, $servicos) ) {
					$servicos[$key]['empresa'] = $transportadora['ship_company']; 
					$servicos[$key]['servico'] = $transportadora['service']; 
					$servicos[$key]['preco'] = $transportadora['price'] * $item['quantidade']; 
					$servicos[$key]['prazo'] = $transportadora['time'];
					$servicos[$key]['count'] = 1;
				} else {
					$servicos[$key]['preco'] += $transportadora['preco'] * $item['quantidade'];  // somo os preços
					if ($servicos[$key]['prazo'] < $transportadora['time']) {   // pego o maior tempo
						$servicos[$key]['prazo'] = $transportadora['time'];  
					}
					$servicos[$key]['count']++; 
				}
			}
		}
		
		foreach ($servicos as $servicekey => $servico) {
			if ($servico['count'] !=  count($fr['volumes'])) {  // uma transportadora não atende todos
				unset($servicos[$servicekey]); 
			}
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'calculo' => 'tipo_volume',
			'servicos' => $servicos
		);
		
		return ($resposta);
	}

	function verificaPorPeso($row_ult,$destino) 
	{
		$like = '%,'.$row_ult['tipo_volume_codigo'].',%'; 
		$sql = 'SELECT * FROM freights_by_weight WHERE start_weight < ? AND end_weight >= ? AND tipo_volume_exceptions NOT LIKE ? AND state = ? ';
		$query = $this->instance->db->query($sql,array($row_ult['peso_bruto'],$row_ult['peso_bruto'],$like,$destino));
		$resp = $query->result_array();
		return (count($resp));	
	}
	
	function calculaPorPeso($fr,$origem,$destino) {
		$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem = $fr['expedidor']['endereco']['cep'];
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
		); 	
		
		$servicos = array();
		foreach ($fr['volumes'] as $item) {
			$like = '%,'.$item['tipo'].',%';
			$sql = 'SELECT * FROM freights_by_weight WHERE start_weight < ? AND end_weight >= ? AND tipo_volume_exceptions NOT LIKE ? AND state = ? order by price, time';
			$query = $this->instance->db->query($sql,array($item['peso'],$item['peso'], $like, $destino['state']));
			$transportadoras = $query->result_array();
			foreach ($transportadoras as $transportadora)
			{
				$key = $transportadora['ship_company']."|".$transportadora['service'];
				if (!key_exists($key, $servicos) ) {
					$servicos[$key]['empresa'] = $transportadora['ship_company']; 
					$servicos[$key]['servico'] = $transportadora['service']; 
					$servicos[$key]['preco'] = $transportadora['price'] * $item['quantidade']; 
					$servicos[$key]['prazo'] = $transportadora['time'];
					$servicos[$key]['count'] = 1;
				} else {
					$servicos[$key]['preco'] += $transportadora['preco'] * $item['quantidade'];  // somo os preços
					if ($servicos[$key]['prazo'] < $transportadora['time']) {   // pego o maior tempo
						$servicos[$key]['prazo'] = $transportadora['time'];  
					}
					$servicos[$key]['count']++; 
				}
			}
		}
		
		foreach ($servicos as $servicekey => $servico) {
			if ($servico['count'] !=  count($fr['volumes'])) {  // uma transportadora não atende todos
				unset($servicos[$servicekey]); 
			}
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'calculo' => 'por_peso',
			'servicos' => $servicos
		);
		
		return ($resposta);
	}

	function calculaCorreiosNovo($fr,$origem,$destino) {
    	$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem = $fr['expedidor']['endereco']['cep'];
		
		// Vejos se tem exceções a consulta normal
		$excecaoSedex = '';
		$excecaoDivisa = '';
		if ($origem['state'] != $destino['state']) {
			$sql = 'SELECT * FROM cep_exceptions WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino.'
			AND type=2';
			$query = $this->instance->db->query($sql);
			$excecaoDivisa = $query->row_array();
		}
		else {
			$sql = 'SELECT * FROM cep_exceptions WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino.'
			AND type=1';
			$query = $this->instance->db->query($sql);
			$excecaoSedex = $query->row_array();
		}
		
		if (empty($excecaoSedex)) { 
			if (empty($excecaoDivisa)) {
				// Se não tem exceção, consulto a tabela normal
				$sql = 'SELECT * FROM correios_states WHERE origin = "'.$origem['state'].'" AND destiny = "'.$destino['state'].'"';
				$query = $this->instance->db->query($sql);
				$states = $query->row_array();
				$nivel = $states['nivel'];
				if (strlen($nivel) == 1) { // Vejo se é capital x capital ou não 
					if (($origem['capital']) && ($destino['capital'])) {
						$nivel = 'N'.$nivel;
					}	else {
						$nivel = 'I'.$nivel;
					}
				}
			}
			else {
				$nivel = $excecaoDivisa['nivel'];
			}
		} else {
			$nivel = $excecaoSedex['nivel'];
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'calculo' => 'correios',
			'nivel' => $nivel,
			'servicos' => array(
				'MINI' => array ('empresa'=>'CORREIOS','servico'=>'MINI', 'preco'=>0,'prazo'=>0,),
				'PAC' => array ('empresa'=>'CORREIOS','servico'=>'PAC','preco'=>0,'prazo'=>0),
				'SEDEX' => array ('empresa'=>'CORREIOS','servico'=>'SEDEX','preco'=>0,'prazo'=>0,),
				
			),
		);
		$mini = 0;
		$total_price = 0; 
		foreach ($fr['volumes'] as $item) {
			
			$total_price+= $item['valor'];
			$peso = $item['peso'];
			$peso_cubico = ceil($item['altura'] * 100 * $item['largura'] * 100 *  $item['comprimento'] * 100 /6000);

			if ($peso_cubico > 5 ) {
				if ($peso_cubico > $item['peso']) {
					$peso = $peso_cubico;
				}
			}
			$peso = ceil($peso * 1000);
			if (($peso <=300) && ($item['valor'] <= 100) && ($item['comprimento']<=0.24) && ($item['largura']<=0.16) && 
				($item['altura']<=0.02)) {
				$mini++;
			} 
			$taxa_adicional =0 ;
			if (($item['altura'] * 100 > 70) || ($item['largura'] * 100 > 70) || ($item['comprimento'] * 100 > 70)) {
				$taxa_adicional = 79 ;
			}
			$sql = 'SELECT * FROM correios_prices WHERE nivel = "'.$nivel.'" AND start_weight <= '.$peso.' 
							AND end_weight >='.$peso.' ORDER BY CAST(price AS DECIMAL(12,2)) ASC ';
			$query = $this->instance->db->query($sql);
			$prices = $query->result_array();
			foreach($prices as $price) {
				$resposta['servicos'][$price['service']]['preco'] += $price['price'] * $item['quantidade'] + $taxa_adicional* $item['quantidade'];
				if ($price['time'] > $resposta['servicos'][$price['service']]['prazo']) {
					$resposta['servicos'][$price['service']]['prazo'] = $price['time'] ; 
				}
			}	
		
		}
		if ($mini != count($fr['volumes'])) { // se todos não forem mini, desconsidero o preço do mini. 
			$resposta['servicos']['MINI']['preco'] = 0;
		}
		if (($resposta['servicos']['PAC']['preco'] == 0) || ($total_price > 3000)){
			unset($resposta['servicos']['PAC']);
		}
		if (($resposta['servicos']['SEDEX']['preco'] == 0) || ($total_price > 10000)) {
			unset($resposta['servicos']['SEDEX']);
		}	
		if (($resposta['servicos']['MINI']['preco'] == 0) || ($total_price > 100)) {
			unset($resposta['servicos']['MINI']);
		}

		// se liberarem, ainda tem que rever a lógica aqui para os 5% da diferença entre PAC e SEDEX
		//if (isset($resposta['servicos']['PAC']['preco']) && isset($resposta['servicos']['SEDEX']['preco'])) { // Se existe PAC e SEDEX
		//	$defirencaFrete = (float)$resposta['servicos']['SEDEX']['preco'] - (float)$resposta['servicos']['PAC']['preco'];
		//	if($defirencaFrete > 0) { // Se a diferença for maior que zero
		//		if( (($defirencaFrete * 100)/ (float)$total_price) <= 5) { // Se for menor que 5% define SEDEX e apaga PAC 
		//			unset($resposta['servicos']['PAC']);
		//		}
		//	}
		//}


		return ($resposta);
		
	}  
}