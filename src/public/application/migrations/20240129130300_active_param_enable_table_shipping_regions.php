<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->update('settings', array('status' => 1), array('name' => 'enable_table_shipping_regions'));
	}

	public function down()	{}
};