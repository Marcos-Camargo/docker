<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if ($this->db->where('name', 'publish_without_category')->get('settings')->num_rows() === 0) {
			$this->db->insert('settings', array(
				'name' => "publish_without_category",
				'value' => 'Quando ativo, permite que um produto seja publicado sem categoria.',
				'status' => 2,
				'user_id' => 1,
				'setting_category_id' => 6,
				'friendly_name' => 'Publicar produtos sem categoria.',
				'description' => 'Quando ativo, trata produtos sem categoria como completos.'
			));
		}
	}

	public function down()
	{
		$this->db->where('name', 'publish_without_category')->delete('settings');
	}
};
