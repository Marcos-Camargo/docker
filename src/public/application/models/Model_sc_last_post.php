<?php

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

use Microservices\v1\Logistic\Shipping;

/**
 * @property Shipping $ms_shipping
 */

class Model_sc_last_post extends CI_Model
{
	var $table = "_last_post";
	var $int_to = false;

	public function __construct()
	{
		parent::__construct();
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
	}

	public function setIntTo($int_to) {
		$this->int_to = $int_to;
		$this->table = $int_to . $this->table;
	}
	
	private function verifyInit() {
		if ($this->int_to === false) {
			throw new Exception("Informe o int_to utilizando o método setIntTo antes de utilizar o método");
		}
	}

	public function getData($id = null)
	{
		$this->verifyInit();
		if($id) {
			$sql = "SELECT * FROM ". $this->table ." WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM ". $this->table ;
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getVariantsProduct($prd_id, $int_to = '') {
		if ($int_to == '') {
			$int_to = $this->int_to;
		}
		$sql = "SELECT * FROM ". $this->table . " where prd_id = ? and int_to = ?";
		$query = $this->db->query($sql, array($prd_id, $int_to));
		return $query->result_array();
	}

	public function create($data)
	{
		$this->verifyInit();
		if($data) {
			$insert = $this->db->insert($this->table, $data);

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
		$this->verifyInit();
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update($this->table, $data);

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
		$this->verifyInit();
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete($this->table);

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $integration_last_post = $this->getData($id);
                    if ($integration_last_post) {
                        $this->ms_shipping->removePivot($integration_last_post);
                    }
                }
            } catch (Exception $exception) {}

			return $delete == true;
		}
	}
	
	/*public function replace($data)
	{
		$this->verifyInit();
		if($data) {
			$insert = $this->db->replace($this->table, $data);
			return ($insert == true) ? true : false;
		}
	}*/
	
	public function createIfNotExist($int_to, $prd_id, $variant, $data)
	{
		$this->verifyInit();
		if (is_null($variant)) {
			$sql = "SELECT * FROM ". $this->table ." WHERE int_to= ? AND prd_id = ? ";
			$query = $this->db->query($sql, array($int_to, $prd_id));
		}
		else {
			$sql = "SELECT * FROM ". $this->table ." WHERE int_to= ? AND prd_id = ? AND variant = ?";
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
		$this->verifyInit();
		$sql = "SELECT * FROM ". $this->table ." WHERE skulocal = ? ";
		$query = $this->db->query($sql, array($sku));
		return $query->row_array();
	}
	
	public function getBySkuMkt($sku) 
	{
		$this->verifyInit();
		$sql = "SELECT * FROM ". $this->table ." WHERE skumkt = ? ";
		$query = $this->db->query($sql, array($sku));
		return $query->row_array();
	}

	public function getBySkuMktIntTo($skumkt, $int_to) 
	{
		$this->verifyInit();
		$sql = "SELECT * FROM ". $this->table ." WHERE skumkt = ? and int_to = ?";
		$query = $this->db->query($sql, array($skumkt, $int_to));
		return $query->row_array();
	}

    public function getDataByPrdIdAndIntTo($prd_id, $int_to) {
        $sql = "SELECT * FROM ".$this->table." where prd_id = ? and int_to = ?";
        $query = $this->db->query($sql, array($prd_id, $int_to));
        return $query->result_array();
    }
	
	public function reduzEstoque($prd_id, $qty, $int_to) 
	{
		$this->verifyInit();
		$sql = "UPDATE ". $this->table ." SET qty=qty-?, qty_total=qty_total-? WHERE prd_id = ? and int_to = ? ";
		$query = $this->db->query($sql, array($qty, $qty, $prd_id, $int_to));

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $ults = $this->getDataByPrdIdAndIntTo($prd_id, $int_to);
                foreach ($ults as $ult) {
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
	
	public function getDataBySkuLocalIntto($skulocal, $int_to)
    {
		$this->verifyInit();
    	$sql = "SELECT * FROM ". $this->table ." WHERE skulocal = ? AND int_to= ?";
		$query = $this->db->query($sql,array($skulocal, $int_to));
		return $query->row_array();
	}

	/**
     * Atualiza dados da last_post relacionados a loja
     * -----------------------------------------------------
     * zipcode
     * freight_seller
     * freight_seller_end_point
     * freight_seller_type
     * -----------------------------------------------------
     * @param   int     $store_id   Código da loja
     * @param   array   $data       Dados da stores para atualizar na last_post
     * @return  bool                Status da atualização
     */
    public function updateDatasStore(int $store_id, array $data): bool
    {
        if($store_id && $data) {
            $this->db->where('store_id', $store_id);
            $update = $this->db->update($this->table, $data);

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->updatePivotByStore($store_id, $data);
                }
            } catch (Exception $exception) {}

            return $update == true;
        }
        return false;
    }
	
}