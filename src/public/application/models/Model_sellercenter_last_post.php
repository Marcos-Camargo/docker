<?php

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

use Microservices\v1\Logistic\Shipping;

/**
 * @property Shipping $ms_shipping
 */

class Model_sellercenter_last_post extends CI_Model
{
	private $table = 'sellercenter_last_post';

	public function __construct()
	{
		parent::__construct();
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
	}

	
	public function getData($id = null, string $procura = null)
	{
		if($id) {
			$sql = "SELECT * FROM ".$this->table." WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM ".$this->table;

        if (!is_null($procura)) {
            return $this->db->query($sql.$procura)->row_array();
        }

		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataSemItegracao($offset = 0,$orderby = '', $procura = '', $export = false)
	{
		if ($offset == '') { $offset = 0; };
		$sql = "SELECT b.id, int_to, b.EAN, p.name, b.price, b.qty, p.date_create, skulocal FROM ".$this->table." b 

					LEFT JOIN products p ON b.prd_id = p.id WHERE marca_int_nm IS NULL ".$procura." ".$orderby;
        $sql.= $export ? '' : " LIMIT 200 OFFSET ".$offset;
		
		$query = $this->db->query($sql);
		return $query->result_array();

	}
	
	public function getCountSemItegracao($procura = '')
	{
		if ($procura == "") {
			$sql = " SELECT count(*) as qtd "; 
			$sql .=	" FROM ".$this->table." ";
			$sql .=	" WHERE marca_int_nm IS NULL "; 		
		} else {
			$sql = "SELECT count(*) as qtd ";
			$sql .=	" FROM ".$this->table." b";
			$sql .=	" LEFT JOIN products p ON b.prd_id = p.id WHERE marca_int_nm IS NULL ".$procura;
		}
		
		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];

	}

	public function getDataBySkulocalAndIntto($sku_local, $int_to) {
		$sql = 'SELECT * FROM '.$this->table.' WHERE skulocal = ? AND int_to = ?';
		$query = $this->db->query($sql, array($sku_local, $int_to));
		return $query->row_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert($this->table, $data);
            if(!$insert)
                $error = $this->db->error();

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->createPivot($data);
                }
            } catch (Exception $exception) {}

			return ($insert) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) 
        {
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

			return ($delete == true) ? true : false;
		}
	}
	
	/*public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace($this->table, $data);
			return ($insert == true) ? true : false;
		}
	}*/
	

	public function reduzEstoque($mkt, $prd_id, $qty)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE int_to = ? AND prd_id = ?";
		$query = $this->db->query($sql, array($mkt, $prd_id ));

		if (count($query->result_array()) == 0)
			return true;	
		
		$ult = $query->row_array();
		$qty = (int) $ult['qty_total'] - (int) $qty;
		$sql = "UPDATE ".$this->table." SET qty_total = ".$qty." WHERE id = ".$ult['id'];
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

		return $update == true;
	}
		
	public function setMarcaInt($id, $valor){
		$sql = "UPDATE ".$this->table." SET marca_int_nm = ? WHERE id = ?";
		
		$cmd = $this->db->query($sql, array($valor, $id ));
		
		return ;  
	}
	
	public function getSkuMkt($int_to, $prd_id){
		$sql = "SELECT skulocal as sku FROM ".$this->table." WHERE int_to= ? AND prd_id= ?";
		$query = $this->db->query($sql, array($int_to, $prd_id ));
        $row = $query->row_array();
        return $row['sku'];
	}
	
	public function getDataIntegrationPriceQty($offset = 0,$orderby = '', $procura = '', $export = false)
	{
		if ($offset == '') { $offset = 0; };
		$sql = "SELECT b.id, int_to, p.EAN, p.name, b.price, b.qty, data_ult_envio, skulocal, p.id as product_id, integrar_price, integrar_qty, s.name AS store
					FROM ".$this->table." b 
					LEFT JOIN products p ON b.prd_id = p.id 
					LEFT JOIN stores s ON b.store_id = s.id
					WHERE int_to = 'VIA' ".$procura." ".$orderby;
        $sql.= $export ? '' : " LIMIT 200 OFFSET ".$offset;
		
		//$this->session->set_flashdata('success', $sql);
		$query = $this->db->query($sql);
		return $query->result_array();

	}
	
	public function getCountIntegrationPriceQty($procura = '')
	{
		$sql = "SELECT count(*) as qtd FROM ".$this->table." b 
					LEFT JOIN products p ON b.prd_id = p.id 
					LEFT JOIN stores s ON b.store_id = s.id
					WHERE int_to != 'B2W' AND int_to != 'CAR' ".$procura;

		$query = $this->db->query($sql, array());
		///$this->session->set_flashdata('success', $sql);
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function setIntegrarPrice($id, $valor){
		$sql = "UPDATE ".$this->table." SET integrar_price = ? WHERE id = ?";
		$cmd = $this->db->query($sql, array($valor, $id ));
		return ;  
	}
	
	public function setIntegrarQty($id, $valor){
		$sql = "UPDATE ".$this->table." SET integrar_qty = ? WHERE id = ?";
		$cmd = $this->db->query($sql, array($valor, $id ));
		return ;  
	}

    public function getDataByPrdIdAndIntTo($prd_id, $int_to) {
        $sql = "SELECT * FROM ".$this->table." where prd_id = ? and int_to = ?";
        $query = $this->db->query($sql, array($prd_id, $int_to));
        return $query->result_array();
    }
	
	public function deleteByIntToPrdId($int_to,$prd_id)
	{
		$sql = "DELETE FROM ".$this->table." WHERE int_to = ? AND prd_id = ?";
		$cmd = $this->db->query($sql, array($int_to, $prd_id));

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $datas = $this->getDataByPrdIdAndIntTo($prd_id, $int_to);
                if (count($datas)) {
                    foreach ($datas as $data) {
                        $this->ms_shipping->removePivot($data);
                    }
                }
            }
        } catch (Exception $exception) {}
	}
	
	public function createIfNotExist($prod_id, $int_to, $data, $variant_num = null)
	{
		if (is_null($variant_num)) {
			$sql = "SELECT * FROM ".$this->table." WHERE int_to = ? AND prd_id = ? AND variant is null";
			$query = $this->db->query($sql, array($int_to,$prod_id));
		} else{
			$sql = "SELECT * FROM ".$this->table." WHERE int_to = ? AND prd_id = ? AND variant = ?";
			$query = $this->db->query($sql, array($int_to,$prod_id , $variant_num));
		}
		
		$row = $query->row_array();
		if ($row) {
			return $this->update($data, $row['id']);
		}
		else {
			return $this->create($data);
		}
	}
	
	public function getDataByIntToPrdIdVariant($int_to,$prd_id,$variant = null)
	{
		if (is_null($variant)) {
			$sql = "SELECT * FROM ".$this->table." WHERE int_to=? AND prd_id = ? AND variant is null";
			$query = $this->db->query($sql, array($int_to,$prd_id));
		}
		else {
			$sql = "SELECT * FROM ".$this->table." WHERE int_to=? AND prd_id = ? AND variant = ?";
			$query = $this->db->query($sql, array($int_to,$prd_id,$variant));
		}
		
		return $query->row_array();
	}
	
	public function getInttoBySkulocal($sku_local) {
		$sql = 'SELECT * FROM '.$this->table.' WHERE skulocal = ? LIMIT 1';
		$query = $this->db->query($sql, array($sku_local));
		return $query->row_array();
	}

	public function updateDatasStore(int $store_id, array $data): bool
    {
        if($store_id && $data) {
            $this->db->where('store_id', $store_id);
            $update = $this->db->update($this->table, $data);

            return $update == true;
        }
        return false;
    }
	
}