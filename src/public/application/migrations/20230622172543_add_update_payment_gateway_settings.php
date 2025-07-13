<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $tableName = 'payment_gateway_settings';

        $values = [
            ['setting' => 'url_api_v2', 'gateway' => '1'],
            ['setting' => 'app_key_oob', 'gateway' => '1'],
            ['setting' => 'access_token_oob', 'gateway' => '1'],
            ['setting' => 'access_token_mgm', 'gateway' => '1'],
            ['setting' => 'client_secret_id_oob', 'gateway' => '1'],
            ['setting' => 'url_api_v1', 'gateway' => '1'],
            ['setting' => 'app_key_mgm', 'gateway' => '1'],
            ['setting' => 'seller_id', 'gateway' => '1'],
            ['setting' => 'client_id_mgm', 'gateway' => '1'],
            ['setting' => 'client_secret_id_mgm', 'gateway' => '1'],
            ['setting' => 'merchant_id', 'gateway' => '1'],
            ['setting' => 'client_id_oob', 'gateway' => '1'],
            ['setting' => 'app_key_v5', 'gateway' => '2'],
            ['setting' => 'banks_with_zero_fee', 'gateway' => '2'],
            ['setting' => 'external_id_prefix_v5', 'gateway' => '2'],
            ['setting' => 'pagarme_subaccounts_api_version', 'gateway' => '2'],
            ['setting' => 'allow_transfer_between_accounts', 'gateway' => '2'],
            ['setting' => 'primary_account', 'gateway' => '2'],
            ['setting' => 'active_transfer_tax_pagarme', 'gateway' => '2'],
            ['setting' => 'cost_transfer_tax_pagarme', 'gateway' => '2'],
            ['setting' => 'url_api_v5', 'gateway' => '2'],
            ['setting' => 'app_bank_id', 'gateway' => '4'],
            ['setting' => 'ymi_url', 'gateway' => '4'],
            ['setting' => 'ymi_token', 'gateway' => '4'],
            ['setting' => 'app_token', 'gateway' => '4'],
            ['setting' => 'api_url', 'gateway' => '4'],
            ['setting' => 'app_account', 'gateway' => '4'],
            ['setting' => 'app_id', 'gateway' => '4']
        ];

        foreach ($values as $value) {

            $this->db->where('name', $value['setting']);
            $this->db->where('gateway_id', $value['gateway']);
            $query = $this->db->get($tableName)->num_rows();

            if ($query == 0) {
                $this->db->insert($tableName, ['name' => $value['setting'], 'value' => '', 'gateway_id' => $value['gateway']]);
            }
        }
    }

    public function down()
    {

    }
};