<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->update(
            'calendar_events',
            array(
                'event_type' => '73',
                'start' => '2024-12-26 04:00:00'
            ),
            array(
                'module_path' => 'Marketplace/External/Fastshop',
                'module_method' => 'runDownloadNfse'
            )
        );
	 }

	public function down()	{
        $this->db->update(
            'calendar_events',
            array(
                'event_type' => '71',
                'start' => '2024-12-01 04:00:00'
            ),
            array(
                'module_path' => 'Marketplace/External/Fastshop',
                'module_method' => 'runDownloadNfse'
            )
        );
	}
};