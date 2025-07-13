<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'CSVFileProcessing/ChangeProductCategory')) {
            $this->db->insert('calendar_events', array(
                'title' => "Atualizar categoria de produtos em massa",
                'event_type' => '10',
                'start' => '2023-04-11 00:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'CSVFileProcessing/ChangeProductCategory',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'CSVFileProcessing/ChangeProductCategory')->delete('calendar_events');
	}
};