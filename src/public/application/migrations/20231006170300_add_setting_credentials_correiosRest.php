<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'credentials_correiosV2'))
		{
			$this->db->insert('settings', array(
				'name'                  => "credentials_sgpV2",
				'value'                 => '{"user":"","pass":""}',
                'description'           => 'Credenciais para utilizar na api sgpweb',
				'status'                => 2,
                'setting_category_id'   => 5,
				'user_id'               => 1,
                'friendly_name'         => 'Credenciais para utilizar na api sgpweb'
			));
		}
	}

	public function down()
	{
		$this->db->delete('settings', array('name' => 'credentials_correiosV2'));
	}
};