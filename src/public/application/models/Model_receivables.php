<?php 
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos

*/  

class Model_receivables extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/* get the orders data */
	public function getReceivablesData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM receivables WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

       // $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
		$more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " WHERE company_id = ".$this->data['usercomp'] : " WHERE store_id = ".$this->data['userstore']);
		
		$sql = "SELECT * FROM receivables ".$more." ORDER BY id DESC";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	/* get the Latest orders data */
	public function getLatestReceivables($more = "")
	{
		$sql = "SELECT * FROM receivables ".$more." ORDER BY id DESC LIMIT 15";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	// get the orders item data
	public function getReceivablesItemData($order_id = null)
	{
		if(!$order_id) {
			return false;
		}

		$sql = "SELECT * FROM orders WHERE order_id = ?";
		$query = $this->db->query($sql, array($order_id));
		return $query->result_array();
	}
	/* get the orders data */
	public function getAccountData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM account_history WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

        //$more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " WHERE company_id = ".$this->data['usercomp'] : " WHERE store_id = ".$this->data['userstore']);
		
		$sql = "SELECT * FROM account_history ".$more." ORDER BY id DESC";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create()
	{
		$user_id = $this->session->userdata('id');
		$bill_no = 'BILPR-'.strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    	$data = array(
    		'bill_no' => $bill_no,
    		'customer_name' => $this->postClean('customer_name'),
    		'customer_address' => $this->postClean('customer_address'),
    		'customer_phone' => $this->postClean('customer_phone'),
    		'date_time' => strtotime(date('Y-m-d h:i:s a')),
    		'gross_amount' => $this->postClean('gross_amount_value'),
    		'service_charge_rate' => $this->postClean('service_charge_rate'),
    		'service_charge' => ($this->postClean('service_charge_value') > 0) ?$this->postClean('service_charge_value'):0,
    		'vat_charge_rate' => $this->postClean('vat_charge_rate'),
    		'vat_charge' => ($this->postClean('vat_charge_value') > 0) ? $this->postClean('vat_charge_value') : 0,
    		'net_amount' => $this->postClean('net_amount_value'),
    		'discount' => $this->postClean('discount'),
    		'paid_status' => 1,
    		'company_id' => $this->session->userdata('usercomp'),
    		'user_id' => $user_id
    	);
		// SW - Log Create
		get_instance()->log_data('Receivables','create',json_encode($data),"I");

		$insert = $this->db->insert('receivables', $data);
		$order_id = $this->db->insert_id();

		$this->load->model('model_receivables');

		return ($order_id) ? $order_id : false;
	}

	public function update($id)
	{
		if($id) {
			$user_id = $this->session->userdata('id');
			// fetch the order data 
			$agora =  date('Y-m-d H:i:s');
			$data = array(
				'date_requested' => $agora,
	    		'status' => 2
	    	);

			$this->db->where('id', $id);
			$update = $this->db->update('receivables', $data);
			// SW - Log Update
			$data['id'] = $id;
			get_instance()->log_data('Receivables','update',json_encode($data),"I");

			return true;
		} 
	}



	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('orders');
			return ($delete == true) ? true : false;
		}
	}

	public function countTotalPaidReceivables($more = "")
	{
		$more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND company_id = ".$this->data['usercomp'] : " AND store_id = ".$this->data['userstore']);
		
		$sql = "SELECT * FROM receivables WHERE paid_status = ?".$more;
		$query = $this->db->query($sql, array(1));
		return $query->num_rows();
	}

}