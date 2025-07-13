<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if(!$this->db->field_exists('tipo_saldo', 'getnet_saldos')){
        	$this->db->query('ALTER TABLE `getnet_saldos` ADD COLUMN `tipo_saldo` varchar(50) NULL AFTER `subseller_id`');
		}
	 }

	public function down()	{
		### Drop table anticipation_limits_store ##
		$this->dbforge->drop_column("getnet_saldos", 'tipo_saldo');
	}
};