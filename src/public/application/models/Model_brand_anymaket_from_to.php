<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

 */

class Model_brand_anymaket_from_to extends CI_Model
{
    const TABLE = 'brand_anymaket_from_to';
    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        $this->db->trans_begin();
        $query = $this->db->select('*')->from(Model_brand_anymaket_from_to::TABLE)->where([
            'idBrandAnymarket' => $data['idBrandAnymarket'],
            'api_integration_id' => $data['api_integration_id']
        ])->get();
        $qtd = $query->num_rows();
        if ($qtd != 0) {
            $data = $query->row_array();
            $response = $this->db->update(Model_brand_anymaket_from_to::TABLE, $data, $data['id']);
            return $response;
        }
        $response = $this->db->insert(Model_brand_anymaket_from_to::TABLE, $data);
        $this->db->trans_commit();
        return $response;
    }

    public function getData($whereData)
    {
        return $this->db->select('*')->from(Model_brand_anymaket_from_to::TABLE)->where($whereData)->get()->row_array();
    }
    public function update($id, $data)
    {
        return $this->db->update(Model_brand_anymaket_from_to::TABLE, $data, ['id' => $id]);
    }
    public function delete($id)
    {
        return $this->db->delete(Model_brand_anymaket_from_to::TABLE, array('id' => $id));
    }
}
