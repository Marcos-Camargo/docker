<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'enable_table_shipping_regions'))
		{
			$this->db->insert('settings', array(
				'name'                  => "enable_table_shipping_regions",
				'value'                 => "Quanto ativo, a cotação de frete não irá para a tabela 'table_shipping', mas sim para a tabela de cada estado.",
                'description'           => "Quanto ativo, a cotação de frete não irá para a tabela 'table_shipping', mas sim para a tabela de cada estado.",
				'status'                => 2,
                'setting_category_id'   => 5,
				'user_id'               => 1,
                'friendly_name'         => 'Cotação de frete por tabela de estados'
			));
		}
	}

	public function down()
	{
		$this->db->delete('settings', array('name' => 'enable_table_shipping_regions'));
	}
};