<?php

require_once APPPATH."/third_party/PHPExcel.php";

class ImportaCEP extends BatchBackground_Controller {
	
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
    }
	
	function run($me = null) {
		echo "Executando...\n";
		//$this->importacep();
		
	    $this->marcaCapitais();
		$this->gravaTabelaLocal();
		$this->gravaTabelaDivisa();
		$this->quebraCepExecptions(); // chamar junto com os 2 acima....
		$this->gravaEstados();
		
		$this->gravaPrecos('SEDEX',4);
		$this->gravaPrecos('PAC',5);
		$this->gravaPrecos('MINI',6);
	}
	
	function quebraCepExecptions() {
		
		$sql = 'truncate cep_exceptions_local';
		$query = $this->db->query($sql);
		$sql = 'truncate cep_exceptions_divisa';
		$query = $this->db->query($sql);
		
		$sql = 'SELECT * FROM cep_exceptions WHERE type=1 ORDER BY origin_start_zipcode, destiny_start_zipcode';
		$query = $this->db->query($sql);
		$ceps = $query->result_array();
		foreach($ceps as $cep) {
			$data = array(
				'origin_start_zipcode' =>$cep['origin_start_zipcode'],
				'origin_end_zipcode' => $cep['origin_end_zipcode'],
				'destiny_start_zipcode' => $cep['destiny_start_zipcode'],
				'destiny_end_zipcode' => $cep['destiny_end_zipcode'],
				'nivel' => $cep['nivel']
			);
			$insert = $this->db->insert('cep_exceptions_local', $data);
		}
		
		$sql = 'SELECT * FROM cep_exceptions WHERE type=2 ORDER BY origin_start_zipcode, destiny_start_zipcode';
		$query = $this->db->query($sql);
		$ceps = $query->result_array();
		foreach($ceps as $cep) {
			$data = array(
				'origin_start_zipcode' =>$cep['origin_start_zipcode'],
				'origin_end_zipcode' => $cep['origin_end_zipcode'],
				'destiny_start_zipcode' => $cep['destiny_start_zipcode'],
				'destiny_end_zipcode' => $cep['destiny_end_zipcode'],
				'nivel' => $cep['nivel']
			);
			$insert = $this->db->insert('cep_exceptions_divisa', $data);
		}
		
	}
	
	function gravaPrecos($service,$sheet) {
		
		echo "Lendo excel\n";
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabela_Correios.xlsx",$sheet,1);
		
		echo $service." \n";
		
		$nivel = $linhas[0];
		$tempo = $linhas[1];
		unset ($linhas[0]);
		unset ($linhas[1]);

	//	var_dump($nivel);
	//	var_dump($tempo);
		foreach ($linhas as $linha) {
			if ((trim($linha[1]) == '') || is_null($linha[1])){
				echo "acabou \n";
				break; 
			}
			$peso = explode(" ",trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $linha[1]))));
			//var_dump($peso);
			
			if ($peso[0] != 'Kg') {
				$i=2;
				$peso[0] = str_replace(".", "", $peso[0]);
				$peso[2] = str_replace(".", "", $peso[2]);
				//var_dump($linha);
				while(array_key_exists($i,$linha)) {
					if (is_null($linha[$i])) {
						break; 
					}
					echo "peso_ini ".$peso[0]." peso fim ".$peso[2]." tempo ".$tempo[$i]." Nivel ".$nivel[$i]." valor ".$linha[$i]."\n";
					$data = array(
						'service' => $service, 
						'start_weight' => $peso[0],
						'end_weight' => $peso[2],
						'nivel' => $nivel[$i],
						'price' => round($linha[$i],2),
						'time' => $tempo[$i],
					);
					$insert = $this->db->replace('correios_prices', $data);
					$i++;
				}
				$ultima = $linha;
			} else {
				$adicional = $linha;
				for ($p=10000; $p<30000; $p=$p+1000) {
					$i=2; 
					while(array_key_exists($i,$ultima)) {
						if (is_null($adicional[$i])) {
							break; 
						}
						$ultima[$i]+= $adicional[$i];
						$peso_ini = $p+1;
						$peso_fim = $p+1000;
						//echo "peso_ini ".$peso_ini." peso fim ".$peso_fim." tempo ".$tempo[$i]." Nivel ".$nivel[$i]." valor ".$ultima[$i]."\n";
						$data = array(
							'service' => $service, 
							'start_weight' => $peso_ini,
							'end_weight' => $peso_fim,
							'nivel' => $nivel[$i],
							'price' => round($ultima[$i],2),
							'time' => $tempo[$i],
						);
						$insert = $this->db->replace('correios_prices', $data);
						$i++;
					}
				}
			}
			
		}
	}
	
	function gravaEstados() {
		
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabela_Correios.xlsx",3,3);
		
		$destiny = $linhas[0];
		unset ($linhas[0]);
		foreach ($linhas as $linha) {
			
			$origin = $linha[2];
			for ($i=3; $i<=29;$i++ ) {
				echo 'Origem '.$origin.' - Destino '.$destiny[$i].' nivel '. $linha[$i]."\n"; 
				$data = array(
					'origin' => $origin,
					'destiny' => $destiny[$i],
					'nivel' => $linha[$i],
				);
				$insert = $this->db->replace('correios_states', $data);
	
			}
			
		}
	}
	
	function marcaCapitais() {
		
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabela_Correios.xlsx",0,4);
		$sql ="UPDATE cep SET capital = false";
		$query = $this->db->query($sql);
		foreach ($linhas as $linha) {
			if (array_key_exists (6,$linha)) {
				if (substr($linha[6],0,8) == 'Excluída') {
					continue;
				}
				
			}
			echo $linha[4].'-'.$linha[5]."\n";
			$sql ="UPDATE cep SET capital = true WHERE zipcode >= ".$linha[4]." AND zipcode <=".$linha[5];
			$query = $this->db->query($sql);
		}
	}
	

	function gravaTabelaLocal()
	{
		
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabela_Correios.xlsx",1,6);
		$i=0;
		if (count($linhas)) {
			$sql ="DELETE FROM cep_exceptions WHERE type=1";
			$query = $this->db->query($sql);
		}
		foreach ($linhas as $linha) {
			if (array_key_exists (10,$linha)) {
				if (substr($linha[10],0,8) == 'Excluída') {
					echo "pulando".++$i."\n";
					continue;
				}
				
			}
			echo ++$i."\n";
			
			$data = array(
			    'type' => 1,
				'origin_start_zipcode' => $linha[3],
				'origin_end_zipcode' => $linha[4],
				'destiny_start_zipcode' => $linha[7],
				'destiny_end_zipcode' => $linha[8],
				'nivel' => $linha[9]
			);
			$insert = $this->db->insert('cep_exceptions', $data);
		}
	}
	function gravaTabelaDivisa()
	{
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabela_Correios.xlsx",2,6);
		$i=0;
		if (count($linhas)) {
			$sql ="DELETE FROM cep_exceptions WHERE type=2";
			$query = $this->db->query($sql);
		}
		foreach ($linhas as $linha) {
			if (array_key_exists (10,$linha)) {
				if (substr($linha[10],0,8) == 'Excluída') {
					echo "pulando".++$i."\n";
					continue;
				}
			}
			if (trim($linha[3]) == '') {
				echo "Acabou\n";
				break;
			}
			echo ++$i."\n";
			$data = array(
				'type' => 2,
				'origin_start_zipcode' => $linha[3],
				'origin_end_zipcode' => $linha[4],
				'destiny_start_zipcode' => $linha[7],
				'destiny_end_zipcode' => $linha[8],
				'nivel' => $linha[9]
			);
			$insert = $this->db->insert('cep_exceptions', $data);
		}
	}
	
	function importacep($me = null,$cmd = NULL) 
	{
		$fn = fopen(FCPATH."importacao/ceps.txt","r");
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
			
		while (! feof($fn)) {
			$linha = fgets($fn);
			$cep = explode("\t", $linha);
			
			// echo $linha."\n";
			$cidade = explode("/", $cep[1]);

			$data = array(
				'zipcode' => $cep[0], 
				'city' => $cidade[0],
				'state' => $cidade[1],
				'address' => trim($cep[3]),
				'neighborhood' => trim($cep[2]),
				'capital' => in_array($cep[1],$capitais),
			);
			$insert = $this->db->insert('cep', $data);
		}
		fclose($fn);
	}

	function lerExcel($file, $sheet = 0, $inicio = 0)
	{
		$objPHPExcel = PHPExcel_IOFactory::load($file);
		$objWorksheet = $objPHPExcel->getSheet($sheet);
		
		$linhas = array();
		foreach ($objWorksheet->getRowIterator() as $row) {
			$rowIndex = $row->getRowIndex();
			if ($rowIndex < $inicio) {
				continue; 
			}   
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(True); //varre todas as células
			$linha = array();
			foreach ($cellIterator as $cell) {
			    $colIndex = PHPExcel_Cell::columnIndexFromString($cell->getColumn());
				$linha[$colIndex] = $cell->getValue();
			}
			$linhas[] = $linha;
		}
		return $linhas;
	}
}