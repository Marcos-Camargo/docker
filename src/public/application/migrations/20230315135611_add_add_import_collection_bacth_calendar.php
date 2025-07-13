<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if ($this->db->where('module_path', 'Collection/CollectionBatch')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Executa a importação de navegações salvas no csv.",
                'event_type' => '10',
                'start' => '2023-03-15 03:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'Collection/CollectionBatch',
                'module_method' => 'runSyncCollections',
                'params' => 'null'
            ));
        }
    }

    public function down()	{
        $this->db->where('module_path', 'Collection/CollectionBatch')->delete('calendar_events');
    }
};