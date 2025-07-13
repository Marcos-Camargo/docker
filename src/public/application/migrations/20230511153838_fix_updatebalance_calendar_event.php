<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if(true === (
				$this->dbforge->register_exists('calendar_events', 'module_path', 'PagarmeBatch') &&
				$this->dbforge->register_exists('calendar_events', 'module_method', 'gatewayUpdateBalance')))
		{
			$this->db->query("update calendar_events set module_path = 'PagarMe/PagarmeBatch' where module_path = 'PagarmeBatch' and module_method = 'gatewayUpdateBalance';");
		}

		if(true === (
				$this->dbforge->register_exists('calendar_events', 'module_path', 'PagarmeBatch') &&
				$this->dbforge->register_exists('calendar_events', 'module_method', 'runPayments')))
		{
			$this->db->query("update calendar_events set module_path = 'PagarMe/PagarmeBatch' where module_path = 'PagarmeBatch' and module_method = 'runPayments';");
		}
	}

	public function down()
	{

	}
};