<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->where_in('name', ['view_product_creation', 'view_product_edit', 'view_product_listing']);
        $this->db->update('settings', ['status' => 1]);
	}

	public function down()	{
	}
};