<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if ($this->db->where('name', 'reject_order_with_high_percentage_discount')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "reject_order_with_high_percentage_discount",
                'value' => '',
                'setting_category_id' => 3,
                'status' => 2,
                'description' => 'Rejeita a criação de pedidos em que os produtos contidos na venda tenham uma diferença entre o preço de venda e o valor que veio no pedido, acima do percentual configurado no parâmetro',
                'friendly_name' => 'Não aceitar pedidos com desconto acima do percentual configurado'
            ));
        }
    }

    public function down()
    {
        $this->db->where('name', 'reject_order_with_high_percentage_discount')->delete('settings');
    }
};