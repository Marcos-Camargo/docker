<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'product_image_rules'))
		{
			$this->db->insert('settings', array(
				'name'      => "product_image_rules",
				'value'     => '600;3200',
				'status'    => 2
			));
		}
	}

	public function down()
	{
		$this->db->where('name', 'product_image_rules')->delete('settings');
	}
};