<?php
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos

*/

class Model_plans extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getPlans($id = null)
    {
        $sql = "select p.id, p.description from plans as p order by p.description";
        $query = $this->db->query($sql);
        $query = $query->result_array();
        return $query;
    }
}