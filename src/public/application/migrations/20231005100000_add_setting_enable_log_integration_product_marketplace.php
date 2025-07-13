<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'enable_log_integration_product_marketplace'))
		{
			$this->db->insert('settings', array(
				'name'                  => "enable_log_integration_product_marketplace",
				'value'                 => "Quanto ativo, a irá gerar log de todas as integrações com os marketplaces.",
                'description'           => "Quanto ativo, a irá gerar log de todas as integrações com os marketplaces",
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Log de integração de produtos com os marketplaces'
			));
		}
	}

	public function down()
	{
		$this->db->delete('settings', array('name' => 'enable_log_integration_product_marketplace'));
	}
};