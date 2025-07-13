<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Marcas/Fabricantes
 
 */

class Model_notification_popup extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getNotificationByCategory($page_view, $category_id)
    {
        $sql = "select np.* from notification_popup np
            join notification_popup_category npc on npc.notification_popup_id = np.id
        where np.status = 1 and np.page_view = ? and npc.category_id = ?
        order by priority ";

        $query = $this->db->query($sql, array($page_view, $category_id));
        return $query->result_array();
    }

    public function getNotificationAmountShowedByStore($notification_popup_id, $store_id) {
        $sql = "SELECT * FROM control_notification_popup 
        where notification_popup_id = ? and store_id = ?";

        $query = $this->db->query($sql, array($notification_popup_id, $store_id));
        return $query->row_array();
    }

    public function insertControlNotificationPopup($notification_popup_id, $store_id) {
        $data = array(
            'notification_popup_id' => $notification_popup_id,
            'store_id' => $store_id
        );
        $insert = $this->db->insert('control_notification_popup', $data);
        return ($insert == true) ? true : false;
    }

    public function updateControlNotificationPopup($notification_popup_id, $store_id) {
        $sql = "UPDATE control_notification_popup SET notification_showed = notification_showed + 1 WHERE notification_popup_id = ? and store_id = ?";
        $result = $this->db->query($sql, array($notification_popup_id, $store_id));
        return true; 
    }

}