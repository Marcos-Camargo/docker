<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("ALTER TABLE `legal_panel` ADD COLUMN `lote` VARCHAR(255) NULL DEFAULT NULL AFTER `conciliacao_id`");


	}

	public function down()	{
		$this->dbforge->drop_column('legal_panel', 'lote');
	}
};