<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        $this->db->where(['table' => 'table_shipping_sp'])->update('table_shipping_regions', array('state' => 'São Paulo'));
        $this->db->where(['table' => 'table_shipping_se'])->update('table_shipping_regions', array('state' => 'Sergipe'));
	}

	public function down()	{
        $this->db->where(['table' => 'table_shipping_sp'])->update('table_shipping_regions', array('state' => 'Sergipe'));
        $this->db->where(['table' => 'table_shipping_se'])->update('table_shipping_regions', array('state' => 'São Paulo'));
	}
};