<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
        if (
            $this->dbforge->column_exists('gateway_account_id', 'gateway_subaccounts') &&
            $this->dbforge->column_exists('secondary_gateway_account_id', 'gateway_subaccounts') &&
            $this->dbforge->column_exists('secondary_gateway_account_id', 'gateway_subaccounts')
        ) {
            $sql = "update gateway_subaccounts set 
					gateway_account_id = 're_cl7qbf7cs3us7019t3hrxgeyy',
					secondary_gateway_account_id = 'rp_dwp3VlRsedt2Yjrl'
				where
					gateway_account_id = 're_cl7qbf7cs3us7019t3hrxgeyy_old_48_old_48'
				and
					secondary_gateway_account_id = 'rp_dwp3VlRsedt2Yjrl_old_48_old_48'
				";

            $this->db->query($sql);
        }
	}

	public function down()	{
	}
};