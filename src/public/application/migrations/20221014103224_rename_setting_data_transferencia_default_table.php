<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("UPDATE settings SET name = 'automatic_anticipation_days_default', value='1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31' WHERE name = 'data_transferencia_default'");
        $this->db->query("ALTER TABLE stores CHANGE COLUMN `transfer_day` `automatic_anticipation_days` varchar(255) NULL DEFAULT NULL AFTER `number_days_advance`");
        $this->db->query("UPDATE stores SET automatic_anticipation_days = '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31'");
	}

	public function down()	{
        $this->db->query("UPDATE settings SET name = 'data_transferencia_default' WHERE name = 'automatic_anticipation_days_default'");
	}
};