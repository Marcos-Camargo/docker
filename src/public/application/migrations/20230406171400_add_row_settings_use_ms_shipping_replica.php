<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if ($this->db->where('name', 'use_ms_shipping_replica')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "use_ms_shipping_replica",
                'value' => 'Parâmetro para replicar os dados também no monolito, caso o microsserviço esteja ativo.',
                'status' => 2,
                'user_id' => 1
            ));
        }
    }

    public function down()
    {
        $this->db->where('name', 'use_ms_shipping_replica')->delete('settings');
    }
};