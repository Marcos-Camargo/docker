<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->where('module_path', 'Integration_v2/Product/tiny/UpdatePriceStock')
            ->update('calendar_events', [
                'event_type' => '20'
            ]);

        $this->db->where('module_path', 'Integration_v2/Product/tiny/CreateProduct')
            ->or_where('module_path', 'Integration_v2/Product/tiny/UpdateProduct')
            ->update('calendar_events', [
                'event_type' => '480'
            ]);

        $this->db->where('module_path', 'Integration_v2/Order/UpdateStatus')
            ->like('title', 'tiny: Atualização de Status')
            ->update('calendar_events', [
                'event_type' => '30'
            ]);
	 }

	public function down()	{
	}
};