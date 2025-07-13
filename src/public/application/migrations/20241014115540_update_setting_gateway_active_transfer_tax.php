<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->where('name', 'active_transfer_tax_pagarme')
            ->update('payment_gateway_settings', [
                'name' => 'charge_seller_tax_pagarme',
                'value' => 0
            ]);

	 }

	public function down()	{
        $this->db->where('name', 'charge_seller_tax_pagarme')
            ->update('payment_gateway_settings', [
                'name' => 'active_transfer_tax_pagarme',
                'value' => 1
            ]);
	}
};