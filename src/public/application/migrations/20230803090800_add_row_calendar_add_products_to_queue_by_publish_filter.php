<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'Publication/AddProductsToQueueByPublishFilter')) {
            $this->db->insert('calendar_events', array(
                'title' => "Publicar produtos enviados em massa na tela de publicação",
                'event_type' => '5',
                'start' => '2023-08-03 04:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'Publication/AddProductsToQueueByPublishFilter',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'Publication/AddProductsToQueueByPublishFilter')->delete('calendar_events');
	}
};