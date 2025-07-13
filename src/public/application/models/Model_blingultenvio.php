<?php

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

use Microservices\v1\Logistic\Shipping;

/**
 * @property Shipping $ms_shipping
 */

class Model_blingultenvio extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
	}

	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM bling_ult_envio WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM bling_ult_envio";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataByPrdIdAndIntTo($prd_id, $int_to) {
		$sql = "SELECT * FROM bling_ult_envio where prd_id = ? and int_to = ?";
		$query = $this->db->query($sql, array($prd_id, $int_to));
		return $query->result_array();
	}

	public function getDataBySkumkt($skumkt) {
		$sql = "SELECT * FROM bling_ult_envio WHERE skumkt = ?";
		$query = $this->db->query($sql, array($skumkt));
		return $query->row_array();
	}

	public function getDataSemItegracao($offset = 0,$orderby = '', $procura = '', $export = false)
	{
		if ($offset == '') { $offset = 0; };
		$sql = "SELECT b.id, int_to, b.EAN, p.name, b.price, b.qty, p.date_create, skubling FROM bling_ult_envio b 

					LEFT JOIN products p ON b.prd_id = p.id WHERE marca_int_bling IS NULL ".$procura." ".$orderby;
        $sql.= $export ? '' : " LIMIT 200 OFFSET ".$offset;
		
		$query = $this->db->query($sql);
		return $query->result_array();

	}
	
	public function getCountSemItegracao($procura = '')
	{
		if ($procura == "") {
			$sql = " SELECT count(*) as qtd "; 
			$sql .=	" FROM bling_ult_envio ";
			$sql .=	" WHERE marca_int_bling IS NULL "; 		
		} else {
			$sql = "SELECT count(*) as qtd ";
			$sql .=	" FROM bling_ult_envio b";
			$sql .=	" LEFT JOIN products p ON b.prd_id = p.id WHERE marca_int_bling IS NULL ".$procura;
		}
		
		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];

	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('bling_ult_envio', $data);
			if ($insert == false) {
				echo " Erro no banco no create :". print_r($this->db->error(),true)."\n ultima query :".print_r($this->db->last_query(),true)."\n"; 
			}

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
			$update = $this->db->update('bling_ult_envio', $data);

			if ($update == false) {
				echo " Erro no banco no update :". print_r($this->db->error(),true)."\n ultima query :".print_r($this->db->last_query(),true)."\n"; 
			}

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
			$delete = $this->db->delete('bling_ult_envio');

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
			$insert = $this->db->replace('bling_ult_envio', $data);
			return ($insert == true) ? true : false;
		}
	}*/
	
	public function reduzEstoque( $mkt, $prd_id,   $qty)
	{
		$sql = "SELECT * FROM bling_ult_envio WHERE int_to = ? AND prd_id = ?";
		$query = $this->db->query($sql, array($mkt, $prd_id ));
		if (count($query->result_array()) == 0) {
			// Não existe pois não foi o ultimo escolhido, então tudo bem
			return TRUE;	
		}
		$ult = $query->row_array();
		$qty = (int) $ult['qty_atual'] - (int) $qty; 
		$sql = "UPDATE bling_ult_envio SET qty_atual = ".$qty." WHERE id = ".$ult['id'];
	    $update = $this->db->query($sql);

        try {
            if ($this->ms_shipping->use_ms_shipping) {
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

		return ($update == true) ? true : false;
	}
	
	public function semSkuML(){
		$sql = "SELECT * FROM bling_ult_envio WHERE (int_to='ML' AND (skubling = skumkt OR skumkt ='00')) OR (int_to='MAGALU' AND (skubling = skumkt OR skumkt ='0')) ";
		$cmd = $this->db->query($sql);
		
		return ($cmd->num_rows());  
	}
	
	public function setMarcaInt($id, $valor){
		$sql = "UPDATE bling_ult_envio SET marca_int_bling = ? WHERE id = ?";
		
		$cmd = $this->db->query($sql, array($valor, $id ));
		
		return ;  
	}
	
	public function getSkuMkt($int_to, $prd_id){
		if ($int_to == 'ML') {
			$sql = "SELECT skumkt as sku FROM bling_ult_envio WHERE int_to= ? AND prd_id= ?";
		}
		else {
			$sql = "SELECT skubling as sku FROM bling_ult_envio WHERE int_to= ? AND prd_id= ?";
		}
		$query = $this->db->query($sql, array($int_to, $prd_id ));
        $row = $query->row_array();
        return $row['sku'];
	}
	
	public function getDataIntegrationPriceQty($offset = 0,$orderby = '', $procura = '', $export = false)
	{
		if ($offset == '') { $offset = 0; };
		$sql = "SELECT b.id, int_to, p.EAN, p.name, b.price, b.qty, data_ult_envio, skubling, p.id as product_id, integrar_price, integrar_qty, s.name AS store
					FROM bling_ult_envio b 
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
		$sql = "SELECT count(*) as qtd FROM bling_ult_envio b 
					LEFT JOIN products p ON b.prd_id = p.id 
					LEFT JOIN stores s ON b.store_id = s.id
					WHERE int_to != 'B2W' AND int_to != 'CAR' ".$procura;

		$query = $this->db->query($sql, array());
		///$this->session->set_flashdata('success', $sql);
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function setIntegrarPrice($id, $valor){
		$sql = "UPDATE bling_ult_envio SET integrar_price = ? WHERE id = ?";
		$cmd = $this->db->query($sql, array($valor, $id ));
		return ;  
	}
	
	public function setIntegrarQty($id, $valor){
		$sql = "UPDATE bling_ult_envio SET integrar_qty = ? WHERE id = ?";
		$cmd = $this->db->query($sql, array($valor, $id ));
		return ;  
	}
	
	public function deleteByIntToPrdId($int_to,$prd_id)
	{
		$sql = "DELETE FROM bling_ult_envio WHERE int_to = ? AND prd_id = ?";
		$cmd = $this->db->query($sql, array($int_to, $prd_id));

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $vtex_ult_envio = $this->getDataByPrdIdAndIntTo($prd_id, $int_to);
                if (count($vtex_ult_envio)) {
                    foreach ($vtex_ult_envio as $vtex_ult_envio_) {
                        $this->ms_shipping->removePivot($vtex_ult_envio_);
                    }
                }
            }
        } catch (Exception $exception) {}
	}

	public function deleteByIntToPrdIdAndDiffEan($prd_id,$int_to,$ean)
	{
		$sql = "DELETE FROM bling_ult_envio WHERE prd_id = ? and int_to = ? AND ean != ?";
		$cmd = $this->db->query($sql, array($prd_id, $int_to, $ean));

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $vtex_ult_envio = $this->db->query("SELECT * FROM bling_ult_envio WHERE prd_id = ? and int_to = ? AND ean != ?", array($prd_id, $int_to, $ean))->row_array();
                if ($vtex_ult_envio) {
                    $this->ms_shipping->removePivot($vtex_ult_envio);
                }
            }
        } catch (Exception $exception) {}
	}
	
	public function createIfNotExist($ean, $int_to, $data)
	{
		$sql = "SELECT * FROM bling_ult_envio WHERE int_to= '".$int_to."' AND EAN = '".$ean."'";
		$query = $this->db->query($sql);
		$row = $query->row_array();
		if ($row) {
			return $this->update($data,$row['id']);
		}
		else {
			return $this->create($data);
		}
	}

	public function createIfNotExistByPrdId($prd_id, $int_to, $data)
	{
		$sql = "SELECT * FROM bling_ult_envio WHERE int_to= '".$int_to."' AND prd_id = '".$prd_id."'";
		$query = $this->db->query($sql);
		$row = $query->row_array();
		if ($row) {
			return $this->update($data,$row['id']);
		}
		else {
			return $this->create($data);
		}
	}
	
	public function getDataByIntToPrdIdVariant($int_to,$prd_id,$variant = null)
	{
		if (is_null($variant)) {
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to=? AND prd_id = ? AND variant is null";
			$query = $this->db->query($sql, array($int_to,$prd_id));
		}
		else {
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to=? AND prd_id = ? AND variant = ?";
			$query = $this->db->query($sql, array($int_to,$prd_id,$variant));
		}
		
		return $query->row_array();
	}
	
	/*public function deleteByEANIntToPrdId($EAN,$int_to,$prd_id)
	{
		$sql = "DELETE FROM bling_ult_envio WHERE int_to = ? AND prd_id = ?";
		$cmd = $this->db->query($sql, array($EAN, $int_to, $prd_id));
	}*/
	
	public function getDataBySkyblingAndIntto($skubling, $int_to)
    {
    	$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to= ?";
		$query = $this->db->query($sql,array($skubling, $int_to));
		return $query->row_array();
	}

	public function blingUltEnvioBySkuMkt($sku){
		$sql = "SELECT b.*, s.zipcode FROM bling_ult_envio b
		join stores s on s.id = b.store_id
		WHERE b.skumkt like '".$sku."'";
		$query = $this->db->query($sql);
		
		return $query->row_array();;  
	}
}