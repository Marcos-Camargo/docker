<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'min_value_hierarchy_comission'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'min_value_hierarchy_comission',
				'value'					=> '0',
				'status'				=> '2',
				'user_id'				=> '1',
				'setting_category_id'	=> '3',
				'friendly_name'			=> 'Valor Mínimo Permitido para Comissões no Cadastro de Hierarquia de Comissões',
				'description'			=> 'Caso não informado, o sistema entenderá como mínimo 1'
			));
		}
		if (!$this->dbforge->register_exists('settings', 'name', 'max_value_hierarchy_comission'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'max_value_hierarchy_comission',
				'value'					=> '0',
				'status'				=> '2',
				'user_id'				=> '1',
				'setting_category_id'	=> '3',
				'friendly_name'			=> 'Valor Máximo Permitido para Comissões no Cadastro de Hierarquia de Comissões',
				'description'			=> 'Caso não informado, o sistema entenderá como máximo 100, caso ultrapasse 100, o máximo também permanecerá 100.'
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'mix_value_hierarchy_comission')) {
			$this->db->delete('settings', array('name' => 'mix_value_hierarchy_comission'));
		}
		if ($this->dbforge->register_exists('settings', 'name', 'max_value_hierarchy_comission')) {
			$this->db->delete('settings', array('name' => 'max_value_hierarchy_comission'));
		}
	}
};