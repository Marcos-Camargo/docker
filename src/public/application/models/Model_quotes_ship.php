<?php 
/*

Model de Acesso ao BD para tabela de cotações de frete realizadas 

*/  

class Model_quotes_ship extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/* get the contratar_frete table data */
	public function getQuoteShipsData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM quotes_ship WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM quotes_ship";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('quotes_ship', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('quotes_ship', $data);
			return ($update == true) ? true : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('quotes_ship');
			return ($delete == true) ? true : false;
		}
	}
	
	function getQuoteShipByKey($marketplace, $zip, $sku, $cost) {
		
		if (is_array($sku)) {
			sort($sku);
			$sku = json_encode($sku);
		}
		if ((trim($marketplace) == '') ||(trim($zip) == '') || (trim($sku) == '') || (trim($cost) == '')) {
			return false;
		}
        $zip = str_replace("-", "", $zip);

		$sql = "SELECT * FROM quotes_ship WHERE marketplace = ? AND zip = ? AND sku = ? AND cost = ?";

		$query = $this->db->query($sql, array($marketplace, $zip, $sku, $cost));
		if ($query->num_rows() > 0) {
			return $query->row_array();
		} else {
			return false;
		}
	}

	function getQuoteShipByOrderId($order_id){

		if($order_id) {
			$sql = "SELECT * FROM quotes_ship WHERE order_id = ?";
			$query = $this->db->query($sql, array($order_id));
			return $query->row_array();
		}
		$sql = "SELECT * FROM quotes_ship";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

        public function getQuoteByMarketplaceNumber($marketplaceNumber, $int_to) {
                $this->db->where('quote_id', $marketplaceNumber);
                $this->db->where('integration', $int_to);
                $this->db->order_by('created_at', 'DESC');
                $query = $this->db->get('log_quotes');
                $row = $query->row_array();
                if ($row && !isset($row['is_multiseller'])) {
                        $row['is_multiseller'] = 0;
                }
                return $row;
	}
}