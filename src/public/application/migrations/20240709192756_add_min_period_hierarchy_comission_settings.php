<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'min_period_hierarchy_comission'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'min_period_hierarchy_comission',
				'value'					=> '0',
				'status'				=> '2',
				'user_id'				=> '1',
				'setting_category_id'	=> '3',
				'friendly_name'			=> 'Período mínimo de vigência do comissionamento (em dias)',
				'description'			=> ''
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'min_period_hierarchy_comission')) {
			$this->db->delete('settings', array('name' => 'min_period_hierarchy_comission'));
		}
	}
};