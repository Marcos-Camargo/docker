<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

*/

class Model_frete_ocorrencias extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getFreteOcorrenciasDataByFreightsId($freights_id)
	{
		$sql = "SELECT * FROM frete_ocorrencias WHERE freights_id = ? ORDER BY data_ocorrencia";
		$query = $this->db->query($sql, array($freights_id));
		return $query->result_array();
	}
	
	public function getFreteOcorrenciasDataByFreightsIdOrderDate($freights_id)
	{
		$sql = "SELECT * FROM frete_ocorrencias WHERE freights_id = ? ORDER BY data_ocorrencia DESC";
		$query = $this->db->query($sql, array($freights_id));
		return $query->result_array();
	}
	
	public function getOcorrenciasByFreightIdCodigo($freights_id, $codigo)
	{
		$sql = "SELECT * FROM frete_ocorrencias WHERE freights_id = ? AND codigo = ?";
		$query = $this->db->query($sql, array($freights_id, $codigo));
		return $query->row_array();
	}
	
	public function getFreteOcorrenciasData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM frete_ocorrencias WHERE id = ? ORDER BY freights_id, codigo";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM frete_ocorrencias ORDER BY freights_id, codigo";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('frete_ocorrencias', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('frete_ocorrencias', $data);
			
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('frete_ocorrencias');
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		$replace = $this->db->replace('frete_ocorrencias', $data);
		return ($replace == true) ? true : false;

	}
	
	public function getFreightsOcorrenciasByCodigoSemAviso($codigo)
	{
		$sql = "SELECT fr.*,f.order_id as order_id FROM "; 
		$sql.= " frete_ocorrencias fr ";
		$sql.= " LEFT JOIN freights f ON fr.freights_id = f.id ";
		$sql.= " WHERE fr.codigo=5 AND avisado_marketplace = 0 ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function updateFreightsOcorrenciaAviso($id,$avisado)
	{
		$data = array(
			'avisado_marketplace' => $avisado
			 );
		$this->db->where('id', $id);
		$update = $this->db->update('frete_ocorrencias', $data);
		return ($update == true) ? true : false;
	}

	public function getOcorrenciasByFreightIdName($freights_id, $name)
    {
        $sql = "SELECT * FROM frete_ocorrencias WHERE freights_id = ? AND nome = ?";
        $query = $this->db->query($sql, array($freights_id, $name));
        return $query->row_array();
    }

    public function getAllNomeOcorrencia()
    {
        $sql = "SELECT * FROM `frete_ocorrencias` GROUP BY nome ORDER BY nome";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOcorrenciaByOrderId($order_id, $naoAvisadoMkt = false, $naoAvisadoErp = false)
    {
        $sql = 'SELECT frete_ocorrencias.*, freights.codigo_rastreio FROM freights JOIN frete_ocorrencias ON freights.id = frete_ocorrencias.freights_id WHERE freights.order_id = ?';
        $sql .= $naoAvisadoMkt ? ' AND frete_ocorrencias.avisado_marketplace = 0' : '';
        $sql .= $naoAvisadoErp ? ' AND frete_ocorrencias.avisado_erp = 0' : '';
        $sql .= " ORDER BY frete_ocorrencias.data_ocorrencia asc";
        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }

    public function updateFreightsOcorrenciaAvisoErp($id,$avisado)
    {
        $data = array(
            'avisado_erp' => $avisado
        );
        $this->db->where('id', $id);
        $update = $this->db->update('frete_ocorrencias', $data);
        return ($update == true) ? true : false;
    }

    public function getOcorrenciasByCodeAndFreight(int $freightId, array $code)
    {
        return $this->db->where('freights_id', $freightId)
                        ->where_in('codigo', $code)
                        ->get('frete_ocorrencias')
                        ->result_array();
    }
}