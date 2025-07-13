<?php 
/*

Model de Acesso ao BD para tabela de cotações de frete realizadas 

*/  

class Model_quotes_correios extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	// check is there is new quotes  in a period of minutes from a marketplace 
	public function getLastQuotesInTime($int_to, $minutes)
	{
		$sql = "SELECT * FROM quotes_correios WHERE marketplace = ? AND date_update >= date_sub(NOW(), interval ? minute) LIMIT 1";
		$query = $this->db->query($sql, array($int_to, $minutes));
		return $query->result_array();
	}

}