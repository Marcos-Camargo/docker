<?php 
/*
SW Serviços de Informática 2019 

Model de Acesso ao BD para Clientes

*/  

class Model_clients extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/* get the orders data */
	public function getClientsData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM clients WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

        $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
		$sql = "SELECT * FROM clients ".$more." ORDER BY id DESC";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getByOrigin($orig,$orig_id)
	{
		$sql = "SELECT * FROM clients WHERE origin = '".$orig."' and origin_id = '". $orig_id . "' ORDER BY id DESC";
		$query = $this->db->query($sql);
		return $query->row_array();
	}

	public function create($data = '')
	{

		if($data) {
			$create = $this->db->insert('clients', $data);

			$client_id = $this->db->insert_id();

			// SW - Log Create
			get_instance()->log_data('Clientes','Create',json_encode($data),"I");

			return ($create == true) ? $client_id : false;
		}
	}

	public function update($data = array(), $id = null)
	{
		$this->db->where('id', $id);
		$update = $this->db->update('Clientes', $data);
		// SW - Log Update
		get_instance()->log_data('Clientes','edit after',json_encode($data),"I");

		return ($update == true) ? true : false;	
	}

	public function countTotalClients($more = "")
	{
		$sql = "SELECT * FROM Clientes".$more;
		$query = $this->db->query($sql);
		return $query->num_rows();
	}

	public function insert($data = null)
	{
		$insert = $this->db->insert('clients', $data);
		$client_id = $this->db->insert_id();
		// get_instance()->log_data('Clients','insert',json_encode($data),"I");

		return ($client_id) ? $client_id : false;
	}

	public function replace($data = null)
	{
		$insert = $this->db->replace('clients', $data);
		$client_id = $this->db->insert_id();
		// get_instance()->log_data('Clients','insert',json_encode($data),"I");

		return ($client_id) ? $client_id : false;
	}


	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('clients');
			// SW - Log update
			get_instance()->log_data('Clients','remove',$id,"I");
			return ($delete == true) ? true : false;
		}
	}

	/* get the orders data */
	public function ExcelList($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM clients WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

        $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
		$sql = "SELECT * FROM clients ".$more." ORDER BY id DESC";
		get_instance()->log_data('Clients','export',$sql,"I");
		
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function orderByClient($cpfCnpj, $type = 'three')
	{
		//o.*,
		$sql = "SELECT o.*
		FROM orders o 
		inner join clients c on o.customer_id  = c.id
		where  o.paid_status not in (95,96,97,98,99) and
		REPLACE( REPLACE( c.cpf_cnpj, '.', '' ), '-', '' )  = ".str_replace(['.','_','/','-'],'',$this->db->escape($cpfCnpj));

		if ( $type == 'three') {
			$sql.=" and  STR_TO_DATE(REPLACE(date_time,\"'\",''), '%Y-%m-%d')  between STR_TO_DATE(REPLACE('".date("Y-m-d", strtotime("-3 months"))."',\"'\",''), '%Y-%m-%d') and STR_TO_DATE(REPLACE('".date('Y-m-d')."',\"'\",''), '%Y-%m-%d')";
		}
		//echo $sql;
		$query = $this->db->query($sql);
	
		return $query->result_array();
	}

	public function orderItemByClient($id)
	{
		$sql = "SELECT *
		FROM orders_item o 
		WHERE o.order_id  = ?";

		$query = $this->db->query($sql, array($id));

	
		return $query->result_array();
	}

	public function orderStatus($id)
	{
		$sql = "SELECT o.paid_status, o.ship_company_preview, o.ship_service_preview, o.date_time, o.data_envio, o.data_entrega
		FROM orders o 
		WHERE o.id  = ? ";

		$query = $this->db->query($sql, array($id));

	
		return $query->result_array();

	}

	public function historyOrder($id)
	{
		$sql = "SELECT fo.*
		from freights f
		join frete_ocorrencias fo on fo.freights_id = f.id
		where f.order_id = ?";

		$query = $this->db->query($sql, array($id));

	
		return $query->result_array();

	}

	public function anonymizeByClientId($client_id)
	{
		if($client_id) {

			$data = array(
				'customer_name' 	=> '***********',
				'customer_address' 	=> '***********',
				'addr_num' 			=> '***********',
				'addr_compl' 		=> '***********',
				'addr_neigh' 		=> '***********',
				'addr_city' 		=> '***********',
				'addr_uf' 			=> '***********',
				'country' 			=> '***********',
				'phone_1' 			=> '***********',
				'phone_2' 			=> '***********',
				'email' 			=> '***********',
				'cpf_cnpj' 			=> '***********',
				'ie' 				=> '***********',
				'rg' 				=> '***********'
			);

            $this->db->where('id', $client_id);
            $update = $this->db->update('clients', $data);
            return ($update == true) ? true : false;
        }	
	}

}