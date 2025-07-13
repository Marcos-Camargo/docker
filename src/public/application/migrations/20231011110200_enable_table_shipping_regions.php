<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $use_ms_shipping = $this->db->where('name', 'use_ms_shipping')->get('settings')->row_array();
        if (!$use_ms_shipping || $use_ms_shipping['status'] != 1) {
            $this->db->update('settings', array('status' => 1), array('name' => 'enable_table_shipping_regions'));
        }
	 }

	public function down()	{
        $use_ms_shipping = $this->db->where('name', 'use_ms_shipping')->get('settings')->row_array();
        if (!$use_ms_shipping || $use_ms_shipping['status'] != 1) {
            $this->db->update('settings', array('status' => 0), array('name' => 'enable_table_shipping_regions'));
        }
	}
};