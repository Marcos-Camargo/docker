<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if ($this->db->where('name', 'limit_add_product_queue_publish_filter')->get('settings')->num_rows() === 0) {
			$this->db->insert('settings', array(
				'name' => "limit_add_product_queue_publish_filter",
				'value' => "10000",
				'status' => 1,
				'user_id' => 1,
				'setting_category_id' => 6,
				'friendly_name' => 'Limite de produtos filtrados para publicação',
				'description' => 'Limite de produtos filtrados para publicação'
			));
		}
	}

	public function down()
	{
		$this->db->where('name', 'limit_add_product_queue_publish_filter')->delete('settings');
	}
};
