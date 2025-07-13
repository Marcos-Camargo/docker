<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'UpdateMSSettings')) {
            $this->db->insert("calendar_events", array(
                'title'         => 'Atualizar tabela de parâmetros no MS a cada 4 horas.',
                'event_type'    => '240',
                'start'         => '2023-09-11 00:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'UpdateMSSettings',
                'module_method' => 'run',
                'params'        => 'null',
                'alert_after'   => 'null'
            ));

            $this->db->insert("calendar_events", array(
                'title'         => 'Atualizar toda a tabela de parâmetros no MS uma vez ao dia.',
                'event_type'    => '71',
                'start'         => '2023-09-11 01:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'UpdateMSSettings',
                'module_method' => 'run',
                'params'        => 'ALL',
                'alert_after'   => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->query("DELETE FROM calendar_events WHERE `module_path` = 'UpdateMSSettings'");
	}
};