<?php

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

use Microservices\v1\Logistic\Shipping;

/**
 * @property Shipping $ms_shipping
 */

class Model_vtex_ult_envio extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
	}

	
	public function getData($id = null, $procura =  null)
	{
		if($id) {
			$sql = "SELECT * FROM vtex_ult_envio WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
		if($procura){
			$sql = "SELECT * FROM vtex_ult_envio " . $procura;
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM vtex_ult_envio";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('vtex_ult_envio', $data);
            $id = $this->db->insert_id(); 

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->createPivot($data);
                }
            } catch (Exception $exception) {}

			return ($insert == true) ? $id : false;
		}
		return false;
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('vtex_ult_envio', $data);

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->updatePivot($data);
                }
            } catch (Exception $exception) {}

			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('vtex_ult_envio');

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $vtex_ult_envio = $this->getData($id);
                    if ($vtex_ult_envio) {
                        $this->ms_shipping->removePivot($vtex_ult_envio);
                    }
                }
            } catch (Exception $exception) {}

			return ($delete == true) ? $id : false;
		}
		return false;
	}

	/*public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('vtex_ult_envio', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}*/
	
	public function getSkuMkt($int_to, $prd_id){

		$sql = "SELECT skumkt as sku FROM vtex_ult_envio WHERE int_to= ? AND prd_id= ?";
		$query = $this->db->query($sql, array($int_to, $prd_id ));
        $row = $query->row_array();
        return $row['sku'];
	}
	
	public function updateQty($prd_id, $int_to, $qty)
    {
    	$sql = "SELECT * FROM vtex_ult_envio WHERE int_to= ? AND prd_id= ?";
		$query = $this->db->query($sql, array($int_to, $prd_id ));
		$row = $query->row_array();
		if (!$row) {
			return false;
		}

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $this->ms_shipping->updatePivot(array(
                    'qty_atual' => $qty,
                    'int_to'    => $row['int_to'],
                    'prd_id'    => $row['prd_id'],
                    'variant'   => $row['variant']
                ));
            }
        } catch (Exception $exception) {}

        return $this->update(array('qty_atual' => $qty), $row['id']); 
    }
	
	public function updateByIntTo($int_to, $prd_id, $variant, $data)
    {
		$this->db->where('int_to', $int_to);
		$this->db->where('prd_id', $prd_id);
		$this->db->where('variant', $variant);
		$update = $this->db->update('vtex_ult_envio', $data);

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                if (!isset($data['int_to'])) {
                    $data['int_to'] = $int_to;
                }
                if (!isset($data['prd_id'])) {
                    $data['prd_id'] = $prd_id;
                }
                if (!isset($data['variant'])) {
                    $data['variant'] = $variant;
                }
                $this->ms_shipping->updatePivot($data, $int_to, $prd_id, $variant);
            }
        } catch (Exception $exception) {}

		return ($update == true) ? true : false;
    }
	
	public function createIfNotExist($prd_id, $int_to, $variant, $data)
	{
		if (is_null($variant)) {
			$sql = "SELECT * FROM vtex_ult_envio WHERE int_to= ? AND prd_id= ? AND variant IS NULL";
			$query = $this->db->query($sql, array($int_to, $prd_id));
		}
		else {
			$sql = "SELECT * FROM vtex_ult_envio WHERE int_to= ? AND prd_id= ? AND variant= ?";
			$query = $this->db->query($sql, array($int_to, $prd_id, $variant));
		}
		$row = $query->row_array();
		if (!$row) {
			return $this->create($data);
		}

        return $this->update($data, $row['id']); 
	}
	
	public function reduzEstoque($mkt, $prd_id, $qty)
    {
        $sql = "SELECT * FROM vtex_ult_envio WHERE int_to = ? AND prd_id = ?";
        $query = $this->db->query($sql, array($mkt, $prd_id ));
        if (count($query->result_array()) == 0) {
            // Não existe pois não foi o ultimo escolhido, então tudo bem
            return TRUE;
        }
        $ult = $query->row_array();
        $qty = (int) $ult['qty_atual'] - ((int) $qty);
        $sql = "UPDATE vtex_ult_envio SET qty_atual = ".$qty." WHERE id = ".$ult['id'];
        $update = $this->db->query($sql);

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $this->ms_shipping->updatePivot(array(
                    'qty_atual' => $qty,
                    'int_to'    => $ult['int_to'],
                    'prd_id'    => $ult['prd_id'],
                    'variant'   => $ult['variant']
                ));
            }
        } catch (Exception $exception) {}

        return ($update == true) ? true : false;
    }

    public function adicionaEstoque($mkt, $prd_id, $qty)
    {
        $sql = "SELECT * FROM vtex_ult_envio WHERE int_to = ? AND prd_id = ?";
        $query = $this->db->query($sql, array($mkt, $prd_id ));
        if (count($query->result_array()) == 0) {
            // Não existe pois não foi o ultimo escolhido, então tudo bem
            return TRUE;
        }
        $ult = $query->row_array();
        $qty = (int) $ult['qty_atual'] + ((int) $qty);
        $sql = "UPDATE vtex_ult_envio SET qty_atual = ".$qty." WHERE id = ".$ult['id'];
        $update = $this->db->query($sql);

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $this->ms_shipping->updatePivot(array(
                    'qty_atual' => $qty,
                    'int_to'    => $ult['int_to'],
                    'prd_id'    => $ult['prd_id'],
                    'variant'   => $ult['variant']
                ));
            }
        } catch (Exception $exception) {}

        return ($update == true) ? true : false;
    }

    public function getProductBySkumktAndStore($skumkt, $store_id)
    {
        $sql = "SELECT * FROM vtex_ult_envio WHERE skumkt = ? AND store_id = ?";
        $query = $this->db->query($sql, array($skumkt, $store_id ));
        return $query->row_array();
    }

    /**
     * Atualiza dados da vtex_ult_envio relacionados a loja
     * -----------------------------------------------------
     * zipcode
     * freight_seller
     * freight_seller_end_point
     * freight_seller_type
     * -----------------------------------------------------
     * @param   int     $store_id   Código da loja
     * @param   array   $data       Dados da stores para atualizar na vtex_ult_envio
     * @return  bool                Status da atualização
     */
    public function updateDatasStore(int $store_id, array $data): bool
    {
        if($store_id && $data) {
            $this->db->where('store_id', $store_id);
            $update = $this->db->update('vtex_ult_envio', $data);

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->updatePivotByStore($store_id, $data);
                }
            } catch (Exception $exception) {}

            return $update == true;
        }
        return false;
    }

	public function getSellerIdByMktPrd(string $int_to, int $prd_id)
	{
		if(($int_to) && ($prd_id)) {
			$sql = "SELECT seller_id FROM vtex_ult_envio WHERE int_to = ? AND prd_id = ?";
			$query = $this->db->query($sql, array($int_to,$prd_id));
			$result = $query->row_array();
			if($result) return $result['seller_id'];
		}

		return false;


	}

    public function getByPrdIdAndIntTo(int $prd_id, string $int_to, int $variant = null): ?array
    {
        if (!is_null($variant)) {
            $this->db->where('variant', $variant);
        }

        return $this->db
            ->where(array(
                'int_to' => $int_to,
                'prd_id' => $prd_id
            ))
            ->get('vtex_ult_envio')->row_array();
    }
	
}