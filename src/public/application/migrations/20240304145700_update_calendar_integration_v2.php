<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->where('module_path', 'Integration_v2/Order/UpdateStatus')
            ->update('calendar_events', [
                'start' => '2024-03-04 06:00:00',
                'end'   => '2200-12-31 20:00:00'
            ]);

        $this->db->like('module_path', 'Integration_v2/Product/')
            ->group_start()
                ->like('module_path', '/CreateProduct')
                ->or_like('module_path', '/UpdateProduct')
                ->or_like('module_path', '/TrackingNotification')
                ->or_like('module_path', '/PartialProductNotification')
            ->group_end()
            ->update('calendar_events', [
                'start' => '2024-03-04 06:00:00',
                'end'   => '2200-12-31 20:00:00'
            ]);
	 }

	public function down()	{
	}
};