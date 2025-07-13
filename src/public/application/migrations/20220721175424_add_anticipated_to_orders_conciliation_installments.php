<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if(!$this->db->field_exists('anticipated', 'orders_conciliation_installments')){
        	$this->db->query('ALTER TABLE `orders_conciliation_installments` ADD COLUMN `anticipated` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `digitos_cartao`');
		}
	 }

	public function down()	{
		### Drop table anticipation_limits_store ##
		$this->dbforge->drop_column("orders_conciliation_installments", 'anticipated');
	}

};