<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'MigrationObjectStorage/SecureSendImageToObjectStorage')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Envia imagens de produtos para o Bucket.",
                'event_type' => '30',
                'start' => '2125-04-15 03:00:00',
                'end' => '2200-09-30 23:59:59',
                'module_path' => 'MigrationObjectStorage/SecureSendImageToObjectStorage',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'MigrationObjectStorage/SecureSendImageToObjectStorage'));
	}
};