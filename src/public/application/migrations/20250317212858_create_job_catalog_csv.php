<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		// Cria o job para ler carga de produtos de catálogo.
        if ($this->db->where('module_path', 'Automation/ImportCSVCatalogProductMarketplace')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Ler carga de produto de catálogo importada pelo usuário",
                'event_type' => '30',
                'start' => '2024-03-17 04:00:00',
                'end' => '2200-12-31 23:59:59',
                'module_path' => 'Automation/ImportCSVCatalogProductMarketplace',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Automation/ImportCSVCatalogProductMarketplace'));
	}
};