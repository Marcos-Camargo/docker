<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'SellerCenter/Marketplace/Order')) {
            $this->db->insert('calendar_events', array(
                'title'         => "Devolver valores de pedidos para Tuna",
                'event_type'    => '10',
                'start'         => '2024-12-09 01:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'SellerCenter/Marketplace/Order',
                'module_method' => 'run',
                'params'        => 'tuna'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'SellerCenter/Marketplace/Order')->delete('calendar_events');
	}
};