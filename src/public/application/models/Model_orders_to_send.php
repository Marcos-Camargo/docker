<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Marcas/Fabricantes

 */

class Model_orders_to_send extends CI_Model
{
    const TABLE = 'orders_to_send';
    public function __construct()
    {
        parent::__construct();
    }

    public function getAllDontSendUser()
    {
        $sql = "SELECT * FROM orders_to_send where status='wait'";
        $query = $this->db->query($sql, array());
        return $query->result_array();
    }
    public function getAllDontSendByUser($user_id)
    {

        $sql = 'SELECT * FROM orders_to_send where status=\'wait\' and user_id=?;';
        $query = $this->db->query($sql, array($user_id));
        return $query->result_array();
    }
    public function userOrdersSetSent($order_id, $user_id)
    {
        $order_to_sent = $this->db->select()->from(self::TABLE)->where(['order_id' => $order_id, 'user_id' => $user_id, 'status' => 'sent'])->get()->row_array();
        if ($order_to_sent) {
            $response=$this->db->update(self::TABLE, ['status' => 'sent'], ['order_id' => $order_id, 'user_id' => $user_id]);
        } else {
            $response=$this->db->insert(self::TABLE, ['order_id' => $order_id, 'user_id' => $user_id, 'status' => 'sent']);
        }
        return $response;
    }
    public function isSent($order_id, $user_id)
    {
        return $this->db->select()->from(self::TABLE)->where(['order_id' => $order_id, 'user_id' => $user_id, 'status' => 'sent'])->get()->row_array();
    }
}
