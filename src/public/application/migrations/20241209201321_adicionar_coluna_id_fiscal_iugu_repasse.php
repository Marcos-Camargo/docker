<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if(!$this->db->field_exists('conciliacao_fiscal_id', 'iugu_repasse')){
        	$this->db->query('ALTER TABLE iugu_repasse MODIFY COLUMN conciliacao_id int NULL;');
			$this->db->query('ALTER TABLE `iugu_repasse` ADD conciliacao_fiscal_id INT NULL AFTER `conciliacao_id`;');
		}
	 }

	public function down()	{
		### Drop table anticipation_limits_store ##
		$this->dbforge->drop_column("iugu_repasse", 'conciliacao_fiscal_id');
	}
};