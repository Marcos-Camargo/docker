<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('integration_erps', 'description', 'Anyhub')) {
            $this->db->insert("integration_erps", array(
                'name'          => 'anyhub',
                'description'   => 'Anyhub',
                'type'          => 2,
                'hash'          => 'b562b0a1f969fd291a8eacfe206e9858c3ea640e',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'anyhub.png'
            ));
        } else {
            $this->db->where('description', 'Anyhub')->update('integration_erps', array('hash' => 'b562b0a1f969fd291a8eacfe206e9858c3ea640e'));
        }

        if (!$this->dbforge->register_exists('integration_erps', 'description', 'Wap.store')) {
            $this->db->insert("integration_erps", array(
                'name'          => 'wapstore',
                'description'   => 'Wap.store',
                'type'          => 2,
                'hash'          => 'b692a8ed12d60e58cf1bb6f06800323c2e7f32ef',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'wapstore.png'
            ));
        } else {
            $this->db->where('description', 'Wap.store')->update('integration_erps', array('hash' => 'b692a8ed12d60e58cf1bb6f06800323c2e7f32ef'));
        }
	 }

	public function down()	{
        $this->db->query("DELETE FROM integration_erps WHERE `description` = 'Anyhub';");
        $this->db->query("DELETE FROM integration_erps WHERE `description` = 'Wap.storeub';");
	}
};