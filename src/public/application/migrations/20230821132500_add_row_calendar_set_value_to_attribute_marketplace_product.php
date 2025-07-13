<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'Publication/SetValueToAttributeMarketplaceProduct')) {
            $this->db->insert('calendar_events', array(
                'title'         => "Atualizar atributos de produtos recebido pelo seller",
                'event_type'    => '60',
                'start'         => '2023-08-21 04:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'Publication/SetValueToAttributeMarketplaceProduct',
                'module_method' => 'run',
                'params'        => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'Publication/SetValueToAttributeMarketplaceProduct')->delete('calendar_events');
	}
};