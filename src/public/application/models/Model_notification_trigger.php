<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Usuarios

 */

class Model_notification_trigger extends CI_Model
{
    const TABLE = 'notification_trigger';
    public function __construct()
    {
        parent::__construct();
    }
    public function create($data)
    {
        $create = $this->db->insert(self::TABLE, $data);
        return $create;
    }
    public function get_notification_trigger($id)
    {
        $sql = "SELECT * FROM " . self::TABLE . " WHERE id_user = ?";
        $query = $this->db->query($sql, array($id));
        $user = $query->row_array();
        if (!$user) {
            $data = ['id_user' => $id, 'order_notification' => 'receive_instantly'];
            $this->db->insert(self::TABLE, $data);
            $query = $this->db->query($sql, array($id));
            $user = $query->result_array();
        }
        return $user;
    }
    public function update_notification_trigger($id, $data)
    {
        $this->db->where('id', $id);
        $update = $this->db->update(self::TABLE, $data);
    }
    public function getNotificationTrigger()
    {
        return $this->db->select('*')->from(self::TABLE)->where('status', '1')->get()->result_array();
    }
    public function getNotificationTriggerId($id)
    {
        return $this->db->select('*')->from(self::TABLE)->where('id', $id)->get()->result_array();
    }
}
