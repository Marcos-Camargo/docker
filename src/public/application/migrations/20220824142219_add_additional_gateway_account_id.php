<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {		    
    if(!$this->db->field_exists('secondary_gateway_account_id', 'gateway_subaccounts')){
      $this->db->query('ALTER TABLE `gateway_subaccounts` ADD COLUMN `secondary_gateway_account_id` varchar(90) AFTER `gateway_id`');
		}                
  }

	public function down()	{    
    $this->dbforge->drop_column("gateway_subaccounts", 'secondary_gateway_account_id');        
	}

};