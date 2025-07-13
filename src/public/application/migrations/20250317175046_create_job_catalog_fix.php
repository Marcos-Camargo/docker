<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'Automation/Fix/FixCatalogImages')) {
            $this->db->insert('calendar_events', array(
                'title'         => "Corrige URL das imagens de produtos de catálogo",
                'event_type'    => '71',
                'start'         => '2025-03-17 04:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'Automation/Fix/FixCatalogImages',
                'module_method' => 'run',
                'params'        => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'Automation/Fix/FixCatalogImages')->delete('calendar_events');
	}
};