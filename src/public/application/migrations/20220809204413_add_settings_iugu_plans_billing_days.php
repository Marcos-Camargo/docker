<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->register_exists('settings','name','iugu_plans_billing_days')){
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('iugu_plans_billing_days', '7', '2', '1')");
        }
	 }

	public function down()	{
		
		$this->db->query("DELETE FROM settings where name = 'iugu_plans_billing_days'");

	}
};