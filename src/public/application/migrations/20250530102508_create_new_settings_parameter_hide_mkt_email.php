<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $exists = $this->db->get_where('settings', ['name' => 'hide_marketplace_email'])->row();

        if (!$exists) {
            $data = [
                'name' => 'hide_marketplace_email',
                'value' => 'Hide Marketplace Email',
                'status' => 0,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Alterar e-mail do pedido vindo do marketplace',
                'description' => 'Quanto ativo, o seller center irá ignorar o pedido vindo do marketplace para gerar um novo e-mail que o seller não consiga contactar o cliente final. O e-mail será composto pelo “Nome do marketplace + id do pedido no marketplace”@example.com'
            ];

            $this->db->insert('settings', $data);
        }
    }

    public function down()
    {
        $this->db->where('name', 'hide_marketplace_email')->delete('settings');
    }
};