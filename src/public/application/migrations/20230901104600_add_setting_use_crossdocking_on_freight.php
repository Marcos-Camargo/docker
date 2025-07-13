<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'use_crossdocking_on_freight'))
		{
			$this->db->insert('settings', array(
				'name'                  => "use_crossdocking_on_freight",
				'value'                 => '0',
                'description'           => 'Parâmetro para somar o crossdocking do produto na cotação de frete',
				'status'                => 2,
                'setting_category_id'   => 5,
				'user_id'               => 1,
                'friendly_name'         => 'Usar crossdocking na cotação de frete'
			));
		}
	}

	public function down()
	{
		$this->db->delete('settings', array('name' => 'use_crossdocking_on_freight'));
	}
};