<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("ALTER TABLE orders_commision_charges ADD order_id INT NOT NULL;");
		$this->db->query("ALTER TABLE orders_commision_charges CHANGE order_id order_id INT NOT NULL AFTER id;");
		
	}

	public function down()	{
	}
};