<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'disable_publication_of_new_products'))
		{
			$this->db->insert('settings', array(
				'name'                  => "disable_publication_of_new_products",
				'value'                 => "Atenção: devido a Black Friday, não é possível publicar novos produtos",
                'description'           => "Quanto ativo, a irá parar de publicar novos produtos nos marketplaces marketplaces. Coloque no valor a mensagem que irá aparecer para o lojista",
				'status'                => 2,
                'setting_category_id'   => 6,
				'user_id'               => 1,
                'friendly_name'         => 'Bloqueia publicações novas'
			));
		}
	}

	public function down()
	{
		$this->db->delete('settings', array('name' => 'disable_publication_of_new_products'));
	}
};