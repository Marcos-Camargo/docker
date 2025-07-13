<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos  

*/

class Model_anymarket_temp_product extends CI_Model
{
    const TABLE = 'anymarket_temp_product';
    public function __construct()
    {
        parent::__construct();
    }
    public function create($data)
    {
        $this->db->trans_begin();
        $where = [
            'id_sku_product' => $data['id_sku_product'],
            'integration_id' => $data['integration_id']
        ];
        $qtd = $this->db->select('*')->from(Model_anymarket_temp_product::TABLE)->where($where)->get()->num_rows();
        if ($qtd != 0) {
            $response= $this->db->update(Model_anymarket_temp_product::TABLE,$data,$where);
            $this->db->trans_commit();
            return $response;
        }
        $response = $this->db->insert(Model_anymarket_temp_product::TABLE, $data);
        $this->db->trans_commit();
        return $response;
    }

    public function getData($whereData)
    {
        return $this->db->select('*')->from(Model_anymarket_temp_product::TABLE)->where($whereData)->get()->row_array();
    }
    public function getManyData($whereData)
    {
        return $this->db->select('*')->from(Model_anymarket_temp_product::TABLE)->where($whereData)->get()->result_array();
    }
    public function update($id,$data)
    {
        $where=[
            'id'=>$id
        ];
        return $this->db->update(Model_anymarket_temp_product::TABLE,$data,$where);
    }
    public function delete($id)
    {
        return $this->db->delete(Model_anymarket_temp_product::TABLE, array('id' => $id));
    }
}
