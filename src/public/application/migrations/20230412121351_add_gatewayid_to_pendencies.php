<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if (!$this->dbforge->column_exists('gateway_id', 'gateway_pendencies'))
		{
			$this->db->query("ALTER TABLE `gateway_pendencies` ADD COLUMN `gateway_id` INT(11) UNSIGNED NOT NULL DEFAULT '4' AFTER `id`;");
		}

		if(false === (
				$this->dbforge->register_exists('calendar_events', 'module_path', 'PagarmeBatch') &&
				$this->dbforge->register_exists('calendar_events', 'module_method', 'gatewayUpdateBalance')))
		{
			$this->db->query("INSERT INTO calendar_events (`title`, `event_type`, `start`, `end`, `module_path`, `module_method`, `params`) 
								VALUES (
									'Atualiza Saldos Pagar.me', 
									'60', 
									'2199-08-27 04:30:00', 
									'2200-12-31 23:59:00', 
									'PagarmeBatch', 
									'gatewayUpdateBalance', 
									'null'
									);"
			);
		}

		if (!$this->dbforge->column_exists('user_id', 'gateway_pendencies'))
		{
			$this->db->query("ALTER TABLE `gateway_pendencies` ADD COLUMN `user_id` VARCHAR(255) NULL AFTER `status`;");
		}

		if (!$this->dbforge->column_exists('user_email', 'gateway_pendencies'))
		{
			$this->db->query("ALTER TABLE `gateway_pendencies` ADD COLUMN `user_email` VARCHAR(255) NULL AFTER `user_id`;");
		}
	}

	public function down()
	{
		$this->dbforge->drop_column('gateway_pendencies', 'gateway_id');
		$this->dbforge->drop_column('gateway_pendencies', 'user_email');
	}
};