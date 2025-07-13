<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'getnet_billet_cancel'))
		{
			$this->db->insert('settings', array(
				'name'					=> 'getnet_billet_cancel',
				'value'					=> '1',
				'status'				=> '2',
				'user_id'				=> '1',
				'setting_category_id'	=> '3',
				'friendly_name'			=> 'Getnet Boleto Cancelamento',
				'description'			=> 'Quando ativo realiza o gatilho de boletos cancelados na liberação de pagamento e um ajuste em favor do marketplace no mesmo valor'
			));
		}

	}

	public function down()	{

		if ($this->dbforge->register_exists('settings', 'name', 'getnet_billet_cancel')) {
			$this->db->delete('settings', array('name' => 'getnet_billet_cancel'));
		}
	}
};