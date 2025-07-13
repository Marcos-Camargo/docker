<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->update('calendar_events',
            array(
                'event_type' => 71
            ),
            array(
                'module_path' => 'Integration_v2/Product/magalu/SyncProduct'
            )
        );
	}

	public function down()	{
        $this->db->update('calendar_events',
            array(
                'event_type' => 10
            ),
            array(
                'module_path' => 'Integration_v2/Product/magalu/SyncProduct'
            )
        );
	}
};