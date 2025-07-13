<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'disable_creation_of_new_products'))
		{
			$this->db->insert('settings', array(
				'name'                  => "disable_creation_of_new_products",
				'value'                 => "Atenção: devido a Black Friday, não é possível criar novos produtos",
                'description'           => "Quanto ativo, a irá parar de aceitar a criação de novos produtos. Coloque no valor a mensagem que irá aparecer para o lojista",
				'status'                => 2,
                'setting_category_id'   => 6,
				'user_id'               => 1,
                'friendly_name'         => 'Bloqueia criação de novos produtos'
			));
		}
	}

	public function down()
	{
		$this->db->delete('settings', array('name' => 'disable_creation_of_new_products'));
	}
};