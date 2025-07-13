<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $exists = $this->db->get_where('settings', ['name' => 'match_produto_por_EAN'])->row();

        if (!$exists) {
            $data = [
                'name' => 'match_produto_por_EAN',
                'value' => 'Wake',
                'status' => 0,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Match Catalogo Wake por EAN.',
                'description' => 'Quando ativo enviamos o campo buy_box como true para Wake.',
                'date_updated' => '2025-04-14 20:04:18',
            ];

            $this->db->insert('settings', $data);
        }
    }

    public function down()
    {
        $this->db->where('name', 'match_produto_por_EAN')->delete('settings');
    }

};
