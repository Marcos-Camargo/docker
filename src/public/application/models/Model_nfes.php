<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

*/

class Model_nfes extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getNfesDataByOrderId($order_id, $is_admin=false)
    {
        if($is_admin) $more = '';
        else $more = $this->data['usercomp'] == 1 ? "": (($this->data['userstore'] == 0) ? " AND company_id = ".$this->data['usercomp'] : " AND store_id = ".$this->data['userstore']);
        
        $sql = "SELECT * FROM nfes WHERE order_id = ? " . $more;
        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }
    
    public function getAllOrderIdHaveNfe($filter = "")
    {
        $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " WHERE company_id = ".$this->data['usercomp'] : " WHERE store_id = ".$this->data['userstore']);
        $result = array();

        if($more == "")
            $filter = "WHERE " . substr($filter,4);
        
        $sql = "SELECT * FROM nfes {$more} {$filter}";
        $query = $this->db->query($sql);
        foreach($query->result_array() as $id)
            array_push($result, $id['order_id']);
            
            return $result;
    }
	
	public function getNfesData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM nfes WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM nfes";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('nfes', $data);
			return ($insert == true) ? true : false;
		}
	}

    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('nfes', $data);
        }
    }

    public function updateForOrderId($data, $order_id)
    {
        if($data && $order_id) {
            $this->db->where('order_id', $order_id);
            $update = $this->db->update('nfes', $data);
            return ($update == true) ? true : false;
        }
    }

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('nfes');
			return ($delete == true) ? true : false;
		}
	}

    public function replace($data)
    {
        if($data) {
            $insert = $this->db->replace('nfes', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function createForIntegration($data)
    {
        if($data) {
            $insert = $this->db->insert('invoices_to_integration', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function getNfesForIntegrationEmission()
    {
        $sql = "SELECT * FROM invoices_to_integration WHERE operation = 'E' AND error_message = ''";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function updateNfeForIntegration($data, $id)
    {
        if($data && $id) {
            $this->db->where('order_id', $id);
            $update = $this->db->update('invoices_to_integration', $data);
            return $update == true ? true : false;
        }
    }
    
    public function anonymizeByOrderId($order_id)
	{
		if($order_id) {

			$data = array(
				'chave' 	=> '***********'
			);

            $this->db->where('order_id', $order_id);
            $update = $this->db->update('nfes', $data);
            return ($update == true) ? true : false;
        }	
	}

    public function getCountNfe($order_id, $company_id)
    {
        $sql = "SELECT * FROM nfes WHERE order_id = ? AND company_id = ?";
        $query = $this->db->query($sql, array($order_id, $company_id));
        return $query->num_rows();
    }

    public function deleteInvoiceIntegration($order_id, $store_id, $company_id)
    {
        if($order_id && $store_id && $company_id) {
            $this->db->where(array('order_id' => $order_id, 'store_id' => $store_id, 'company_id' => $company_id));
            $delete = $this->db->delete('invoices_to_integration');
            return ($delete == true) ? true : false;
        }
    }

    public function getErrorInvoiceIntegration($order_id)
    {
        $result = array();

        $sql = "SELECT error_message FROM invoices_to_integration WHERE order_id = ?";
        $query = $this->db->query($sql, array($order_id));
        if($query->num_rows() == 0) return $result;

        foreach ($query->result_array() as $msg)
            array_push($result, $msg['error_message']);

        return $result;
    }

    public function sendInvoiceWithError($order_id)
    {
        $sql = "SELECT * FROM orders WHERE id = ? AND paid_status = 57";
        $query = $this->db->query($sql, array($order_id));
        if($query->num_rows() == 0) return false;

        $sql = "UPDATE invoices_to_integration SET error_message = '' WHERE order_id = ?";
        $this->db->query($sql, array($order_id));

        $sql = "UPDATE orders SET paid_status = '56' WHERE id = ?";
        $this->db->query($sql, array($order_id));

        return true;
    }

    public function getDataInvoice($order_id, $more = "")
    {
        $result = array();

        $sql = "SELECT * FROM nfes WHERE order_id = ? {$more}";
        $query = $this->db->query($sql, array($order_id));
        if($query->num_rows() == 0) return $result;

        foreach ($query->result_array() as $nfe){
            $dataExp = explode(" ", $nfe['date_emission']);
            $dataExp = explode("/", $dataExp[0]);
            $result = array(
                'date_emission' => $nfe['date_emission'],
                'serie'         => $nfe['nfe_serie'],
                'numero'        => $nfe['nfe_num'],
                'chave'         => $nfe['chave'],
                'id_nota_tiny'  => $nfe['id_nota_tiny'],
                'link'          => $nfe['link_tiny'],
                'linkXml'       => base_url('assets/images/xml/' . $nfe['store_id'] . '/' . $dataExp[1] . '-' . $dataExp[2] . '/' .  $nfe['chave'] . '.xml'),
                'store_id'      => $nfe['store_id'],
            );
        }

        return $result;
    }

    public function requestCancel($order_id)
    {
        $sql = "UPDATE nfes SET request_cancel = 1 WHERE order_id = ?";
        $this->db->query($sql, array($order_id));

    }

    public function getInvoiceKeyByOrderId($order_id)
    {
        $sql = "SELECT chave FROM nfes WHERE order_id = ?";
        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }

    public function getNfeByChaveAndStore(string $chave, ?int $ignore_order_id = null)
    {
        $this->db
            ->select('nfes.*')
            ->join('orders', 'orders.id = nfes.order_id')
            ->where(array(
                'nfes.chave' => $chave
            ));


        // Ignorar o order id, é uma atualização.
        if (!is_null($ignore_order_id)) {
            $this->db->where('nfes.order_id !=', $ignore_order_id);
        }

        return $this->db
            ->get('nfes')
            ->row_array();
    }

    public function getNfesDataByOrderIds(array $order_id): array
    {
        return $this->db->where_in('order_id', $order_id)->get('nfes')->result_array();
    }


}