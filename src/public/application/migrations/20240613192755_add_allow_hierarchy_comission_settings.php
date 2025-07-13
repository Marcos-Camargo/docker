<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'allow_hierarchy_comission'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'allow_hierarchy_comission',
				'value'					=> '1',
				'status'				=> '2',
				'user_id'				=> '1',
				'setting_category_id'	=> '3',
				'friendly_name'			=> 'Permitir Cadastro de Hierarquia de Comissões',
				'description'			=> 'Quando ativo, habilita o acesso a tela de cadastro de hierarquia de comissões, desabilitando a opção de redução de comissão da tela de campanhas. Obs. Necessário também habilitar as devidas permissões em grupos de permissões'
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'allow_hierarchy_comission')) {
			$this->db->delete('settings', array('name' => 'allow_hierarchy_comission'));
		}
	}
};