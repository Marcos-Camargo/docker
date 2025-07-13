<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'tracking_new_version_sgpweb')) {
            // Se for conectala, entra como ativo.
            $sellerCenter = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();
            $status = $sellerCenter && $sellerCenter['value'] === 'vertem' ? 1 : 2;

            $this->db->insert('settings', array(
                'name'      => "tracking_new_version_sgpweb",
                'value'     => 'Habilita o rastreio com a SGPWeb para a nova versão.',
                'status'    => $status,
                'user_id'   => 1
            ));
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'tracking_new_version_sgpweb';");
    }
};