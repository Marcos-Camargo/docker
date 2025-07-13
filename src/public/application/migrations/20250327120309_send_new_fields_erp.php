<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if (!$this->dbforge->register_exists('settings', 'name', 'send_new_fields_erp')){
			$this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`, `setting_category_id`, `friendly_name`, `description`) VALUES (
				'send_new_fields_erp',
				'0',
				'2',  
				'1',  
				'4',
				'Enviar campos para ERP',     
				'Quando ativado, exibirá campos adicionais no cadastro da loja para configuração, e a rotina de integração de pedidos passará a considerar essas configurações.'
				)"
			);
		}
	}

	public function down()	{
		$this->db->query("DELETE FROM settings where name = 'send_new_fields_erp'");
	}
};