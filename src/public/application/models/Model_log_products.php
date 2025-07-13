<?php 

class Model_log_products extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function create($data)
	{
		
		if($data) {
			$insert = $this->db->insert('log_products', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false ; 
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('log_products', $data);
			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{

		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('log_products');
			return ($delete == true) ? true : false;
		}
		return false;
	}
	
	public function replace($data)
	{

		if($data) {
			$insert = $this->db->replace('log_products', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}
	
	public function getLogProductsData($prd_id,  $procura = '',$orderby = '', $offset = 0, $limit =200) 
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
		$sql = "SELECT * FROM log_products WHERE prd_id=? ".$procura.$orderby." LIMIT ".$limit." OFFSET ".$offset; 
		$query = $this->db->query($sql, array($prd_id));
		return $query->result_array();
	}
	
	public function getLogProductsDataCount($prd_id,$procura = '') {
		$sql = "SELECT count(*) as qtd FROM log_products WHERE prd_id=? ".$procura;
		$query = $this->db->query($sql, array($prd_id));
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function create_log_products($data, $id, $funcao) {
		
		$price = '-';
		$qty = '-'; 
		
		if (isset($data['qty'])) {
			$qty = $data['qty'];
		}
		if (isset($data['price'])) {
			$price = $data['price'];	
		}

		if (($price !== '-') || ($qty !== '-') || likeText('%Alterar por API%', $funcao)) {
			if (session_status() === PHP_SESSION_NONE) {
				$username = 'api@conectala.com.br';
			}
			else {
				// $this->session->userdata('serial') ?? false;
				$username = $this->session->userdata('email');
			}
			
			$log_products_array = array (
				'prd_id' 	=> $id, 
			  	'qty' 		=> $qty,
			  	'price' 	=> $price,
			  	'username' 	=> $username,
			  	'change' 	=> $funcao, 
			); 
			$this->create($log_products_array);
		}
		else {
			//var_dump($data);
			//var_dump($user);
			//die;
		}
	
	}

}