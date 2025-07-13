<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'getnet_payment_plan'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'getnet_payment_plan',
				'value'					=> '8',
				'status'				=> '1',
				'user_id'				=> '1',
				'setting_category_id'	=> '3',
				'friendly_name'			=> 'Plano de Pagamento Getnet',
				'description'			=> 'Define qual o plano de pagamento será cadastrado nas subcontas getnet'
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'getnet_payment_plan')) {
			$this->db->delete('settings', array('name' => 'getnet_payment_plan'));
		}
	}
};