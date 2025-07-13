<?php 
/*
*/  

class Model_integration_logistic_api_parameters extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}


	public function getData($id = null)
	{
		if ($id) {
			$sql = "SELECT * FROM integration_logistic_api_parameters WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM integration_logistic_api_parameters";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getDataByIntegrationId($id = null)
	{
		if ($id) {
			$sql = "SELECT * FROM integration_logistic_api_parameters WHERE integration_logistic_id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->result_array();
		} else { 
			return array();
		}
	}

	public function create($data)
	{
		if ($data) {
			$insert = $this->db->insert('integration_logistic_api_parameters', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

    public function createBatch(array $data): bool
    {
        return $data && $this->db->insert_batch('integration_logistic_api_parameters', $data);
    }

	public function update($data, $id)
	{
		if ($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('integration_logistic_api_parameters', $data);
			return ($update == true) ? $id : false;
		}
	}

	public function remove($id)
	{
		if ($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('integration_logistic_api_parameters');
			return ($delete == true) ? true : false;
		}
	}

	public function replace($data)
	{
		if ($data) {
			$insert = $this->db->replace('integration_logistic_api_parameters', $data);
			return ($insert == true) ? true : false;
		}
	}

}