<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $results = $this->db->where('module_path', 'DeleteImagesProductsTrash')->get('calendar_events')->result_array();

        if (empty($results)) {
            $this->db->insert("calendar_events", array(
                'title'         => 'Remove as imagens dos produtos na lixeira depois de 2 semanas',
                'event_type'    => '71',
                'start'         => '2023-01-04 23:30:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'DeleteImagesProductsTrash',
                'module_method' => 'run',
                'params'        => 'null',
                'alert_after'   => '120'
            ));
        } 
	 }

	public function down()	{
        $this->db->query("DELETE FROM calendar_events WHERE `module_path` = 'DeleteImagesProductsTrash'");
	}
};