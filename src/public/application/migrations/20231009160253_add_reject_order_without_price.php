<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if ($this->db->where('name', 'reject_order_without_price')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "reject_order_without_price",
                'value' => '',
                'setting_category_id' => 3,
                'status' => 2,
                'description' => 'Rejeita a criação de pedidos em que os produtos contidos na venda tenham preço zerado',
                'friendly_name' => 'Não aceitar pedidos com item sem preço'
            ));
        }
    }

    public function down()
    {
        $this->db->where('name', 'reject_order_without_price')->delete('settings');
    }
};