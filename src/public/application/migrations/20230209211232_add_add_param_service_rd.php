<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->insert('settings', array(
            'name' => "atendimento_rd",
            'value' => 'Botão de atendimento rd',
            'status' => 2,
            'user_id' => 1,
            'date_updated' => date('Y-m-d H:i:s')
        ));
    }

    public function down()	{
        $this->db->where('name', 'atendimento_rd')->delete('settings');
    }
};