<?php

require_once APPPATH."/third_party/PHPExcel.php";

class ImportaTabelasFrete extends BatchBackground_Controller {
	
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
		$this->gravaTipoVolume(0);
		$this->gravaPorKilo(1);
		$this->alteraCapitais(2);
	}
	
	function gravaTipoVolume($sheet) {
		
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabelas_Transportadoras.xlsx",$sheet,0);
		
		$states_capital = $linhas[0];
		$time  = $linhas[1];
		
		$i = 1;
		foreach ($states_capital as $st_cp) {
			if ($i >=6 ) {
				$st = explode(" ",$st_cp);
				$states[$i]= $st[0];
				$capitals[$i] = $st[2];
			}
			$i++;
		}

		unset ($linhas[0]);
		unset ($linhas[1]);
		
		//marca todos com uma data antiga para remover quem não recebeu update
		$sql = 'UPDATE freights_by_tipo_volume SET date_update = "1980-01-01 12:00:00"';
		$result = $this->db->query($sql);
		
		foreach($linhas as $linha) {
			//var_dump($linha);
			$i = 6; 
			while(array_key_exists($i,$linha)) {
				echo "{$linha[5]} {$states[$i]} cap={$capitals[$i]}\n";
				if (!is_null($linha[$i])) {  // verifica se está sem preço
					$data = array (
						'tipo_volume_codigo' =>  $linha[1], 
						'origin_state' => $linha[5],
						'destiny_state' => $states[$i],
						'price' => round($linha[$i],2),
						'time' => $time[$i],
						'ship_company' => $linha[3],
						'service' =>$linha[4],
						'capital' => ($capitals[$i] == 'CAPITAL'),
					);
					$insert = $this->db->replace('freights_by_tipo_volume', $data);
				}
				$i++;
			}
			
		}
		// remove quem não recebeu update
		$sql = 'DELETE FROM freights_by_tipo_volume WHERE date_update = "1980-01-01 12:00:00"';
		$result = $this->db->query($sql);
	}
	
	function gravaPorKilo($sheet)
	{
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabelas_Transportadoras.xlsx",$sheet,0);
		$states = $linhas[0];
		$time  = $linhas[1];
		
		unset ($linhas[0]);
		unset ($linhas[1]);

		foreach ($linhas as $linha) { 
			$peso = explode(" ",trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $linha[1]))));
			//var_dump($peso);
			$peso[0] = str_replace(".", "", $peso[0]);
			$peso[2] = str_replace(".", "", $peso[2]);
			$i=5;
			//echo  $linha[4]."\n";
			//echo  $linha[3]."\n";
			$exption = ",".$linha[4].",";
			while(array_key_exists($i,$linha)) {
				//echo "peso_ini ".$peso[0]." peso fim ".$peso[2]." tempo ".$tempo[$i]." Nivel ".$nivel[$i]." valor ".$linha[$i]."\n";
				$data = array(
					'start_weight' => $peso[0],
					'end_weight' => $peso[2],
					'state' => $states[$i],
					'price' => round($linha[$i],2),
					'time' => $time[$i],
					'ship_company' => $linha[2],
					'tipo_volume_exceptions' => $exption,
					'service' => $linha[3],
				);
				$insert = $this->db->replace('freights_by_weight', $data);
				$i++;
			}

		}
	}
	
	function alteraCapitais($sheet) {
		
		
		
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
		
		// gravo primeiro as capitais oficiais
		$sql ="UPDATE cep SET capital_shipping = 0";
		$query = $this->db->query($sql);
		
		foreach($capitais as $capital) {
			$city = explode('/',$capital);
			$sql = 'UPDATE cep SET capital_shipping=1 WHERE city=? AND state=?';
			$result = $this->db->query($sql, array($city[0], $city[1]));
		}
		
		//agora leio a tabela para trocar 
		$linhas = $this->lerExcel(FCPATH."/importacao/Tabelas_Transportadoras.xlsx",$sheet,1);
		foreach ($linhas as $linha) {
			$capital = false;
			if (strpos($linha[6],'Capital') !== FALSE) {
				$capital = true;
			}elseif (strpos($linha[6],'Metropolitano') !== FALSE) {
				$capital = true;
			}elseif (strpos($linha[6],'Metropolitana') !== FALSE) {
				$capital = true;
			}
			$linha[1] = preg_replace('/\D/', '', $linha[1]);
			$linha[2] = preg_replace('/\D/', '', $linha[2]);
			echo $linha[1].'-'.$linha[2].' capital_shipping '.$linha[6].' = '.$capital."\n";
			$sql ="UPDATE cep SET capital_shipping = ? WHERE zipcode >= ? AND zipcode <= ?";
			$query = $this->db->query($sql,array($capital,$linha[1],$linha[2]));
		}
		
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