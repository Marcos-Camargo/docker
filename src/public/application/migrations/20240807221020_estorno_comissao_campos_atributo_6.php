<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("ALTER TABLE orders_commision_charges ADD legal_panel_id INT NULL;");
		$this->db->query("ALTER TABLE orders_commision_charges CHANGE date_create date_create timestamp DEFAULT current_timestamp() NULL AFTER legal_panel_id;");
		
	}

	public function down()	{
	}
};