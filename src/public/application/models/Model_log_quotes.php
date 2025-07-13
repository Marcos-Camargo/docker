<?php 

class Model_log_quotes extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function create($data)
	{
		
		if($data) {
			$insert = $this->db->insert('log_quotes', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false ; 
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('log_quotes', $data);
			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{

		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('log_quotes');
			return ($delete == true) ? true : false;
		}
		return false;
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('log_quotes', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function getLog($id)
	{
		$sql = "SELECT * FROM log_quotes WHERE id=? "; 
		$query = $this->db->query($sql, array($id));
		return $query->row_array();
	}
	
	public function getLogQuotesData($prd_id,  $procura = '',$orderby = '', $offset = 0, $limit =200) 
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
		$sql = "SELECT * FROM log_quotes WHERE product_id=? ".$procura.$orderby." LIMIT ".$limit." OFFSET ".$offset; 
		$query = $this->db->query($sql, array($prd_id));
		return $query->result_array();
	}
	
        public function getLogQuotesDataCount($prd_id,$procura = '') {
                $sql = "SELECT count(*) as qtd FROM log_quotes WHERE product_id=? ".$procura;
                $query = $this->db->query($sql, array($prd_id));
                $row = $query->row_array();
                return $row['qtd'];
        }

        public function getQuoteByMarketplaceNumber($marketplaceNumber, $int_to)
        {
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