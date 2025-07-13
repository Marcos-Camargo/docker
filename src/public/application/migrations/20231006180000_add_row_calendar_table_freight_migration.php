<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('module_path', 'Logistic/TableFreightMigration')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Migrar tabela de frete para as tabelas por estado",
                'event_type' => '74',
                'start' => '2023-10-07 03:00:00',
                'end' => '2023-10-07 04:00:00',
                'module_path' => 'Logistic/TableFreightMigration',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'Logistic/TableFreightMigration')->where('module_method', 'run')->delete('calendar_events');
	}
};