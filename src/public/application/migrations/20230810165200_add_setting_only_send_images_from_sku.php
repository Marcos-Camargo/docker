<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'only_send_images_from_sku'))
		{
			$this->db->insert('settings', array(
				'name'                  => "only_send_images_from_sku",
				'value'                 => 'Quando ativo será enviado somente a imagem da variação, caso contrário será enviado a imagem do produto pai e da variação juntas.',
                'description'           => 'Quando ativo será enviado somente a imagem da variação, caso contrário será enviado a imagem do produto pai e da variação juntas.',
				'status'                => 2,
                'setting_category_id'   => 6,
				'user_id'               => 1,
                'friendly_name'         => 'Enviar apenas imagem do Sku'
			));
		}
	}

	public function down()
	{
		$this->db->delete('settings', array('name' => 'only_send_images_from_sku'));
	}
};