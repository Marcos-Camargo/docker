<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'ProductsModified')) {
            $this->db->insert('calendar_events', array(
                'title'         => "Produtos Alterados no Seller",
                'event_type'    => '71',
                'start'         => '2024-08-21 04:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'ProductsModified',
                'module_method' => 'run',
                'params'        => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'ProductsModified')->delete('calendar_events');
	}
};