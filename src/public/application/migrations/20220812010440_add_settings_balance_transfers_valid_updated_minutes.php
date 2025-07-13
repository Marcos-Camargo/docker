<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('settings', 'name', 'balance_transfers_valid_updated_minutes')){
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('balance_transfers_valid_updated_minutes', '30', '1', '1')");
        }
	 }

	public function down()	{
		
		$this->db->query("DELETE FROM settings where name = 'balance_transfers_valid_updated_minutes'");

	}
};