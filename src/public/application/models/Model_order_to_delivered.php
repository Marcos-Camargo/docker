<?php
class Model_order_to_delivered extends CI_Model
{
    public function getFirst($marketplace){
        $query = $this->db->get_where('order_to_delivered_config', ['marketplace' => $marketplace]);
        return $query->row_array();
    }

    public function create($data){
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('order_to_delivered_config', $data);
    }

    public function update($marketplace, $data){
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('marketplace', $marketplace);
        return $this->db->update('order_to_delivered_config', $data);
    }


    public function getAllMarketplaces() {
        $this->db->select('name AS marketplace, int_to');
        $this->db->from('integrations');
        $this->db->where("store_id", 0);
        $this->db->where("active", 1);
        $query = $this->db->get();
        return $query->result();
    }

    //pega lojas que atuam no marketplace selecionado
    public function getByMarketplace($marketplace){
        $this->db->select('s.id, s.name');
        $this->db->select("CASE WHEN odt.id IS NOT NULL THEN 1 ELSE 0 END as has_order_to_send_config", false);
        $this->db->from('stores s');
        $this->db->join('integrations i', 'i.store_id = s.id');
        $this->db->join('order_to_delivered_tracking odt', 
                    "odt.store_id = s.id AND odt.marketplace = " . $this->db->escape($marketplace),
                    'left');
        $this->db->where('i.int_to', $marketplace); //int_to
        $this->db->where('s.active', 1);          
        return $this->db->get()->result_array();
    }

    public function getValueByField($field, $store_id){
        $this->db->select('int_to');
        $this->db->from('integrations');
        $this->db->where('store_id', $store_id);
        $row = $this->db->get()->row();

        if (!$row || !$row->name) {
            return null;
        }

        $marketplace = strtolower(trim($row->name));

        $this->db->select($field);
        $this->db->from('order_to_delivered_config');
        $this->db->where('marketplace', $marketplace);

        $result = $this->db->get()->row();

        return $result ? $result->$field : null;
    }

    //pega o valor da flag que diz que a loja esta configurada ou nao
    public function getHasOrderToDeliveredField($store_id){
        $this->db->select('has_order_to_send_config');
        $this->db->from('stores s');
        $this->db->where('s.id', $store_id );
        return $this->db->get()->result_array();
    }


    //pega a data que o pedido foi criado
    public function getOrderDateCreate($order_id){
        $this->db->select('date_time');
        $this->db->from('orders o');
        $this->db->where('o.id', $order_id );
        $result = $this->db->get()->row_array();

        return $result['date_time'] ?? null;
    }
    public function updateFlagOrder($order_id){
        if (!$order_id) return;

        $this->db->where('id', $order_id);
        $this->db->update('orders', ['forced_to_delivery' => 1]);

    }

    public function getOriginByOrderId($order_id){
        $this->db->select('origin');
        $this->db->from('orders o');
        $this->db->where('o.id', $order_id );
        $result = $this->db->get()->row_array();

        return $result['origin'] ?? null;
    }

    public function getConfigById($config_id) {
        return $this->db
                    ->where('id', $config_id)
                    ->get('order_to_delivered_config')
                    ->row_array();
    }

    public function getTrackingByStoreAndMarketplace($store_id, $marketplace) {
        return $this->db
                    ->where('store_id', $store_id)
                    ->where('marketplace', $marketplace)
                    ->get('order_to_delivered_tracking')
                    ->row_array();
    }

    public function saveTracking($store_id, $marketplace, $order_to_delivered_config_id) {
        $exists = $this->db
                    ->where('store_id', $store_id)
                    ->where('marketplace', $marketplace) //marteplace = int_to
                    ->get('order_to_delivered_tracking')
                    ->row_array();

        if ($exists) {
            if ($exists['order_to_delivered_config_id'] != $order_to_delivered_config_id) {
                $this->db->where('id', $exists['id'])
                        ->update('order_to_delivered_tracking', [
                            'order_to_delivered_config_id' => $order_to_delivered_config_id,
                            'date_create' => date('Y-m-d H:i:s')
                        ]);
            }
            return;
        }

        $this->db->insert('order_to_delivered_tracking', [
            'store_id' => $store_id,
            'marketplace' => $marketplace,
            'order_to_delivered_config_id' => $order_to_delivered_config_id,
            'date_create' => date('Y-m-d H:i:s')
        ]);
    }
    public function deleteUnselectedTrackings($store_ids, $marketplace) {
        if (!empty($store_ids)) {
            $this->db->where_in('store_id', $store_ids);
            $this->db->where('marketplace', $marketplace);
            $this->db->delete('order_to_delivered_tracking');
        }
    }
}
