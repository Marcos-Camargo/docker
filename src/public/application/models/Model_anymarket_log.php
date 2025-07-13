<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

 */

class Model_anymarket_log extends CI_Model
{
    const TABLE = 'anymarket_log';
    public function __construct()
    {
        parent::__construct();
    }
    public function create($data)
    {
        $sucess = $this->db->insert(self::TABLE, $data);
        $id = $this->db->insert_id();
        return $sucess ? '' . $id : null;
    }

}
