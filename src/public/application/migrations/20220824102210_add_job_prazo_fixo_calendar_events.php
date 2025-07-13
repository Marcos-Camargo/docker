<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        
        
        $this->db->query("INSERT INTO calendar_events  
         (title, event_type, start, end, module_path, module_method, params) 
         VALUES 
         ('Atualização de prazo operacional nos produtos', '60', '2022-09-22 03:10:00', '2200-12-31 23:59:00', 'Automation/UpdateSetDeadlineNovo', 'run', 'null')
        ");
	 }

	public function down()	{

        $this->db->query("DELETE FROM calendar_events 
            WHERE title = 'Atualização de prazo operacional nos produtos'
        ");

	}
};