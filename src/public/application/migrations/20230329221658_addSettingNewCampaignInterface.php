<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'enable_campaigns_v2_1'))
		{
			$this->db->insert('settings', array(
				'name'      => "enable_campaigns_v2_1",
				'value'     => 'Habilita o uso da Nova Interface de Campanhas (V2.1).',
				'status'    => 2,
				'user_id'   => 1
			));
		}
	}

	public function down()
	{
		$this->db->query("DELETE FROM settings WHERE `name` = 'enable_campaigns_v2_1';");
	}
};