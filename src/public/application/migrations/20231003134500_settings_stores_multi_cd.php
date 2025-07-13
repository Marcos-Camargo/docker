<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'stores_multi_cd'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'stores_multi_cd',
				'value'					=> 'Multi CD',
				'status'				=> '2',
				'user_id'				=> '1',
				'setting_category_id'	=> '6',
				'friendly_name'			=> 'Multi CD',
				'description'			=> 'Quando ativo, será utilizado Multi CD no cadastro de empresa e lojas.'
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'stores_multi_cd')) {
			$this->db->delete('settings', array('name' => 'stores_multi_cd'));
		}
	}
};