<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'marketplace_pluggto'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'marketplace_pluggto',
				'value'					=> null,
				'status'				=> '2',
				'user_id'				=> '1',
				'setting_category_id'	=> '7',
				'friendly_name'			=> 'Marketplace Pluggto',
				'description'			=> 'Parâmetro utilizado para montar a URL de integração/cotação do Marketplace com a Pluggto, se inativo usa o parâmetro sellercenter como padrão'
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'marketplace_pluggto')) {
			$this->db->delete('settings', array('name' => 'marketplace_pluggto'));
		}
	}
};