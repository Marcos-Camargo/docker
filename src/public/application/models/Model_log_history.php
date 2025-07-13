<?php 
/*

Model de Acesso ao BD para tabela de log_history 

*/  

class Model_log_history extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
	public function getLogData($log = '',$id = null)
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
	
		if($id) {
			$sql = "SELECT * FROM log_history".$log." WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM log_history".$log." ORDER BY id DESC";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getLogDataCount($log = '',$procura='')
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		
		if ($procura == '') {
			$sql = "SELECT count(*) as qtd FROM log_history".$log;
		}
		else {
			$sql = "SELECT count(*) as qtd FROM log_history".$log." LEFT JOIN users ON user_id = users.id WHERE ".$procura;
		}
		
		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function getLogDataView($log = '',$offset = 0,  $procura ='', $order = '', $limit=200)
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		if ($offset=='') {
			$offset = 0;
		}
		if ($limit=='') {
			$limit=200;
		}
		if($procura=='') {
			$sql = "SELECT log.*, users.email AS email FROM log_history".$log." log LEFT JOIN users ON user_id = users.id ".$order." LIMIT ".$limit." OFFSET ".$offset;
		}
		else {
			$sql = "SELECT log.*, users.email AS email FROM log_history".$log." log LEFT JOIN users ON user_id = users.id WHERE ".$procura.' '.$order." LIMIT ".$limit." OFFSET ".$offset;
		}
		$query = $this->db->query($sql);
		//  $this->session->set_flashdata('success', $sql);
		return $query->result_array();
	}

	public function existLog($log = '',$id)
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		$sql = "SELECT * FROM log_history".$log." WHERE id = ?";
		$query = $this->db->query($sql, array($id));
		return !empty($query->row_array());
	}
	
	public function countTotalErrors($log = '',$module = null, $data_erro = " CURDATE() ")
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		if ($module) {
			$sql = "SELECT count(*) as qtd FROM log_history".$log." WHERE tipo ='E' AND date_log >= $data_erro AND module = ?";
			$query = $this->db->query($sql, array($module));
			$row = $query->row_array();
		return $row['qtd'];
		}
		$sql = "SELECT count(*) as qtd FROM log_history".$log." WHERE tipo ='E' AND date_log >= ?";
		$query = $this->db->query($sql, array($data_erro));
		$row = $query->row_array();
		return $row['qtd'];
		

		
	}

	public function create($log = '',$data)
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		if($data) {
			$insert = $this->db->insert('log_history'.$log, $data);
			return ($insert == true) ? true : false;
		}
	}

	public function update($log = '',$data, $id)
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('log_history'.$log, $data);
			
		}
	}

	public function remove($log = '',$id)
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('log_history'.$log);
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($log = '',$data)
	{
		if (($log == 'general') || ($log == '')) {
			$log='';
		}
		else{
			$log = '_'.$log; 
		} 
		if($data) {
			$insert = $this->db->replace('log_history'.$log, $data);
			return ($insert == true) ? true : false;
		}
	}


}