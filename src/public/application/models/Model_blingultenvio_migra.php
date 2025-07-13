<?php 
/*

Model de Acesso ao BD para tabela de fretes de pedidos  

*/  

class Model_blingultenvio_migra extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM bling_ult_envio_migra WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM bling_ult_envio_migra";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataBySkumkt($skumkt) {
		$sql = "SELECT * FROM bling_ult_envio_migra WHERE skumkt = ?";
		$query = $this->db->query($sql, array($skumkt));
		return $query->row_array();
	}

	public function getDataSemItegracao($offset = 0,$orderby = '', $procura = '', $export = false)
	{
		if ($offset == '') { $offset = 0; };
		$sql = "SELECT b.id, int_to, b.EAN, p.name, b.price, b.qty, p.date_create, skubling FROM bling_ult_envio_migra b 

					LEFT JOIN products p ON b.prd_id = p.id WHERE marca_int_bling IS NULL ".$procura." ".$orderby;
        $sql.= $export ? '' : " LIMIT 200 OFFSET ".$offset;
		
		$query = $this->db->query($sql);
		return $query->result_array();

	}
	
	public function getCountSemItegracao($procura = '')
	{
		if ($procura == "") {
			$sql = " SELECT count(*) as qtd "; 
			$sql .=	" FROM bling_ult_envio_migra ";
			$sql .=	" WHERE marca_int_bling IS NULL "; 		
		} else {
			$sql = "SELECT count(*) as qtd ";
			$sql .=	" FROM bling_ult_envio_migra b";
			$sql .=	" LEFT JOIN products p ON b.prd_id = p.id WHERE marca_int_bling IS NULL ".$procura;
		}
		
		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];

	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('bling_ult_envio_migra', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('bling_ult_envio_migra', $data);
			return ($update == true) ? $id : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('bling_ult_envio_migra');
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('bling_ult_envio_migra', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function reduzEstoque( $mkt, $prd_id,   $qty)
	{
		$sql = "SELECT * FROM bling_ult_envio_migra WHERE int_to = ? AND prd_id = ?";
		$query = $this->db->query($sql, array($mkt, $prd_id ));
		if (count($query->result_array()) == 0) {
			// Não existe pois não foi o ultimo escolhido, então tudo bem
			return TRUE;	
		}
		$ult = $query->row_array();
		$qty = (int) $ult['qty_atual'] - (int) $qty; 
		$sql = "UPDATE bling_ult_envio_migra SET qty_atual = ".$qty." WHERE id = ".$ult['id'];
	    $update = $this->db->query($sql);
		return ($update == true) ? true : false;
	}
	
	public function semSkuML(){
		$sql = "SELECT * FROM bling_ult_envio_migra WHERE (int_to='ML' AND (skubling = skumkt OR skumkt ='00')) OR (int_to='MAGALU' AND (skubling = skumkt OR skumkt ='0')) ";
		$cmd = $this->db->query($sql);
		
		return ($cmd->num_rows());  
	}
	
	public function setMarcaInt($id, $valor){
		$sql = "UPDATE bling_ult_envio_migra SET marca_int_bling = ? WHERE id = ?";
		
		$cmd = $this->db->query($sql, array($valor, $id ));
		
		return ;  
	}
	
	public function getSkuMkt($int_to, $prd_id){
		if ($int_to == 'ML') {
			$sql = "SELECT skumkt as sku FROM bling_ult_envio_migra WHERE int_to= ? AND prd_id= ?";
		}
		else {
			$sql = "SELECT skubling as sku FROM bling_ult_envio_migra WHERE int_to= ? AND prd_id= ?";
		}
		$query = $this->db->query($sql, array($int_to, $prd_id ));
        $row = $query->row_array();
        return $row['sku'];
	}
	
	public function getDataIntegrationPriceQty($offset = 0,$orderby = '', $procura = '', $export = false)
	{
		if ($offset == '') { $offset = 0; };
		$sql = "SELECT b.id, int_to, p.EAN, p.name, b.price, b.qty, data_ult_envio, skubling, p.id as product_id, integrar_price, integrar_qty, s.name AS store
					FROM bling_ult_envio_migra b 
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
		$sql = "SELECT count(*) as qtd FROM bling_ult_envio_migra b 
					LEFT JOIN products p ON b.prd_id = p.id 
					LEFT JOIN stores s ON b.store_id = s.id
					WHERE int_to != 'B2W' AND int_to != 'CAR' ".$procura;

		$query = $this->db->query($sql, array());
		///$this->session->set_flashdata('success', $sql);
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function setIntegrarPrice($id, $valor){
		$sql = "UPDATE bling_ult_envio_migra SET integrar_price = ? WHERE id = ?";
		$cmd = $this->db->query($sql, array($valor, $id ));
		return ;  
	}
	
	public function setIntegrarQty($id, $valor){
		$sql = "UPDATE bling_ult_envio_migra SET integrar_qty = ? WHERE id = ?";
		$cmd = $this->db->query($sql, array($valor, $id ));
		return ;  
	}
	
	public function deleteByIntToPrdId($int_to,$prd_id)
	{
		$sql = "DELETE FROM bling_ult_envio_migra WHERE int_to = ? AND prd_id = ?";
		$cmd = $this->db->query($sql, array($int_to, $prd_id));
	}
	
	public function createIfNotExist($ean, $int_to, $data)
	{
		$sql = "SELECT id FROM bling_ult_envio_migra WHERE int_to= '".$int_to."' AND EAN = '".$ean."'";
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
			$sql = "SELECT * FROM bling_ult_envio_migra WHERE int_to=? AND prd_id = ? AND variant is null";
			$query = $this->db->query($sql, array($int_to,$prd_id));
		}
		else {
			$sql = "SELECT * FROM bling_ult_envio_migra WHERE int_to=? AND prd_id = ? AND variant = ?";
			$query = $this->db->query($sql, array($int_to,$prd_id,$variant));
		}
		
		return $query->row_array();
	}
	
	public function deleteByEANIntToPrdId($EAN,$int_to,$prd_id)
	{
		$sql = "DELETE FROM bling_ult_envio_migra WHERE int_to = ? AND prd_id = ?";
		$cmd = $this->db->query($sql, array($EAN, $int_to, $prd_id));
	}
	
	public function getDataBySkyblingAndIntto($skubling, $int_to)
    {
    	$sql = "SELECT * FROM bling_ult_envio_migra WHERE skubling = ? AND int_to= ?";
		$query = $this->db->query($sql,array($skubling, $int_to));
		return $query->row_array();
    }
}