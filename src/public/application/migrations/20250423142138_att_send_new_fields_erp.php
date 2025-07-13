<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("
			UPDATE settings 
			SET `value` = 'Enviar campos para ERP'
			WHERE `name` = 'send_new_fields_erp'");
	}

	public function down()	{
	}
};