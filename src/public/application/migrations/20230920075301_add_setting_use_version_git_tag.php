<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'use_version_git_tag'))
		{
			$this->db->insert('settings', array(
				'name'                  => "use_version_git_tag",
				'value'                 => '',
                'description'           => 'Quando ativo será usando a tag do git como versão',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Utilizar tag do git como versão'
			));
		}

		if (!$this->dbforge->register_exists('settings', 'name', 'show_version_git_tag'))
		{
			$this->db->insert('settings', array(
				'name'                  => "show_version_git_tag",
				'value'                 => '',
                'description'           => 'Quando ativo será apresentada a tag do git como versão no rodapé da aplicação',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Mostrar tag do git como versão'
			));
		}
	}

	public function down()
	{
		if ($this->dbforge->register_exists('settings', 'name', 'use_version_git_tag')) {
			$this->db->delete('settings', array('name' => 'use_version_git_tag'));
		}

		if ($this->dbforge->register_exists('settings', 'name', 'show_version_git_tag')) {
			$this->db->delete('settings', array('name' => 'show_version_git_tag'));
		}
	}
};