<?php

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

use Microservices\v1\Logistic\Shipping;

/**
 * @property Shipping $ms_shipping
 */

class Model_b2w_ult_envio extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
	}

	
	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM b2w_ult_envio WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM b2w_ult_envio";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('b2w_ult_envio', $data);

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->createPivot($data);
                }
            } catch (Exception $exception) {}

			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('b2w_ult_envio', $data);

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->updatePivot($data);
                }
            } catch (Exception $exception) {}

			return ($update == true) ? $id : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('b2w_ult_envio');

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $vtex_ult_envio = $this->getData($id);
                    if ($vtex_ult_envio) {
                        $this->ms_shipping->removePivot($vtex_ult_envio);
                    }
                }
            } catch (Exception $exception) {}

            return $delete == true;
		}
	}
	
	/*public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('b2w_ult_envio', $data);
			return ($insert == true) ? true : false;
		}
	}*/
	
	public function createIfNotExist($int_to, $prd_id, $variant, $data)
	{
		if (is_null($variant)) {
			$sql = "SELECT * FROM b2w_ult_envio WHERE int_to= ? AND prd_id = ? ";
			$query = $this->db->query($sql, array($int_to, $prd_id));
		}
		else {
			$sql = "SELECT * FROM b2w_ult_envio WHERE int_to= ? AND prd_id = ? AND variant = ?";
			$query = $this->db->query($sql, array($int_to, $prd_id, $variant));
		}
		$row = $query->row_array();
		if ($row) {
			return $this->update($data,$row['id']);
		}
		else {
			return $this->create($data);
		}
	}

	public function getBySku($sku) 
	{
		$sql = "SELECT * FROM b2w_ult_envio WHERE skulocal = ? ";
		$query = $this->db->query($sql, array($sku));
		return $query->row_array();
	}
	
	
	public function reduzEstoque($id, $qty) 
	{
		$sql = "UPDATE b2w_ult_envio SET qty=qty-?, qty_total=qty_total-? WHERE id = ? ";
		$query = $this->db->query($sql, array($qty, $qty, $id));
        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $ult = $this->getData($id);
                if ($ult) {
                    $this->ms_shipping->updatePivot(array(
                        'qty_atual' => $qty,
                        'int_to'    => $ult['int_to'],
                        'prd_id'    => $ult['prd_id'],
                        'variant'   => $ult['variant']
                    ));
                }
            }
        } catch (Exception $exception) {}

		return $query;
	}
	
}