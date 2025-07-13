<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'SendProductsWithTransformationError')) {
            $this->db->insert("calendar_events", array(
                'title'         => 'Adicionar na fila todos os produtos com erros de transformações.',
                'event_type'    => '71',
                'start'         => '2023-02-10 02:30:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'SendProductsWithTransformationError',
                'module_method' => 'run',
                'params'        => 'null',
                'alert_after'   => '60'
            ));
        }
    }

	public function down()	{
        $this->db->query("DELETE FROM calendar_events WHERE `module_path` = 'SendProductsWithTransformationError'");
	}
};