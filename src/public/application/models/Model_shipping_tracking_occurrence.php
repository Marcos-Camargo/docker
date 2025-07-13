<?php
/*

Model de Acesso ao BD para a tabela de registro de ocorrências de frete, enviados pela transportadora integrada

*/

class Model_shipping_tracking_occurrence extends CI_Model
{
    public function __construct()
	{
		parent::__construct();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('shipping_tracking_occurrence', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function selectByRequisitionID($data)
	
	{
		if($data) {
			$query = $this->db->get_where('shipping_tracking_occurrence', array('requisition_ID' => $data))->row_array();
			return $query ? $query['requisition_ID'] : null;
		}
	}

	public function getOccurrencesByTrackingCode($data)
    {
        if ($data) {
            $sql = "SELECT * FROM shipping_tracking_occurrence where recipient_doc = ? AND tracking_code = ? AND occurrence_updated = ? ORDER BY occurrence_date ASC";
            $query = $this->db->query($sql, array($data['recipient_doc'], $data['tracking_code'], 0));
            return $query->result_array();
        }
    }

	public function updateOccurrence($data){
		if($data) {
			$sql = "UPDATE shipping_tracking_occurrence SET occurrence_updated = ? , update_date = ? WHERE id = ?";
			$query = $this->db->query($sql,[1,date('Y-m-d H:i:s'),$data]);
			}  
	}
	
}