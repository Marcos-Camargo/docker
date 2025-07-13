<?php

require_once APPPATH."/third_party/PHPExcel.php";

class ImportaCategoriasTipoVolume extends BatchBackground_Controller {
	
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
		// $this->gravaTipoVolume(0);
		$this->acertaTipoVolumeBling();
	}
	
	function gravaTipoVolume($sheet) {
		
		$linhas = $this->lerExcel("/var/www/html/app/importacao/ConectaLa_Categorias_novo.xlsx",$sheet,0);
	

		unset ($linhas[0]);
		
		foreach($linhas as $linha) {
			//var_dump($linha);
			echo $linha[1].' '.$linha[3];
			$sql = 'SELECT * FROM tipos_volumes WHERE codigo = ?'; 
			$query = $this->db->query($sql,array($linha[3]));
			$tipo_volume  = $query->row_array();
			echo ' tipo_volume '.$tipo_volume['id']." ".$tipo_volume['produto']."\n"; 
			
			$sql ="UPDATE categories SET tipo_volume_id = ? WHERE id = ? ";
			
			$query = $this->db->query($sql,array($tipo_volume['id'],$linha[1]));
			
		}
	}
	
	function acertaTipoVolumeBling() {
		
		$sql = 'SELECT * FROM bling_ult_envio';
		$query = $this->db->query($sql);
		$blings = $query->result_array();
		
		foreach($blings as $bling) {
			$sql = "SELECT * FROM products WHERE id = ".$bling['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			
			$cat_id = json_decode ( $prd['category_id']);
			$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
					 WHERE id =".intval($cat_id[0]).")";
			$cmd = $this->db->query($sql);
			$tipo_volume_codigo = $cmd->row_array();
			
			if ($tipo_volume_codigo['codigo'] !=$bling['tipo_volume_codigo']) {
				$sql = "UPDATE bling_ult_envio SET tipo_volume_codigo = ? WHERE id = ? ";		
				echo "Faria o update $bling[id] de $bling[tipo_volume_codigo] para $tipo_volume_codigo[codigo] \n";
				$query = $this->db->query($sql,array($tipo_volume_codigo['codigo'],$bling['id']));
			}
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