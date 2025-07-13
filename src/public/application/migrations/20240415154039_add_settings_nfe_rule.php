<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'nfe_rule_validation'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'nfe_rule_validation',
				'value'					=> 'nfe_rule_validation',
				'status'				=> '2',
				'user_id'				=> '0',
				'setting_category_id'	=> '7',
				'friendly_name'			=> 'Desabilita as regras de validação da NF-E',
				'description'			=> 'Quando ativado o sistema não mais verifica as regras de validação de NF-E, tanto em tela quando na API. A única validação que segue é a de 44 caracteres numéricos para a NF'
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'nfe_rule_validation')) {
			$this->db->delete('settings', array('name' => 'nfe_rule_validation'));
		}
	}
};