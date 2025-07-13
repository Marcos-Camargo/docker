<?php 
/*

Model de Acesso ao BD para freights_by_tipo_volume

*/  

class Model_freights_by_tipo_volume extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function update($data)
	{	
		$sql = "SELECT * FROM freights_by_tipo_volume WHERE tipo_volume_codigo = ? AND origin_state = ? AND destiny_state = ? AND capital = ? AND ship_company = ? AND `service` = ?;";
        $query = $this->db->query($sql, array($data['tipo_volume_codigo'], $data['origin_state'], $data['destiny_state'], $data['capital'], $data['ship_company'], $data['service'] ));
        $select_result = $query->row_array();

		if ($select_result == true) { 
			$this->db->where($select_result)->update('freights_by_tipo_volume', $data);
		}
		else {
			$sql = "INSERT INTO freights_by_tipo_volume	(tipo_volume_codigo, origin_state, destiny_state, capital, `percentage`, price, `time`, ship_company, `service`)";
			$sql .= "VALUES(?,?,?,?,?,?,?,?,?)";
			$query = $this->db->query($sql, array($data['tipo_volume_codigo'], $data['origin_state'], $data['destiny_state'], $data['capital'],$data['percentage'], '3000', $data['time'], $data['ship_company'], $data['service']));
		};
		
		return ;
	}
}	