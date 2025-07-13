<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $data = array(
            'name' => 'agidesk_default_role',
            'value' => '',
            'status' => 2,
            'user_id' => 1,
            'setting_category_id' => 2,
            'friendly_name' => 'Grupo de Permissão Agidesk',
            'description' => 'Configure no valor deste parâmetro a permissão default para que os usuários virem agentes dentro do Agidesk',
            'date_updated' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('settings', $data);
    }

    public function down()
    {
        $this->db->where('name', 'agidesk_default_role');
        $this->db->delete('settings');
    }
};