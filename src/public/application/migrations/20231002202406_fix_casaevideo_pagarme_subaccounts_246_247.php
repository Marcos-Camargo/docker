<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		$sql_246 = "
			update 
			    gateway_subaccounts 
			set 
			    secondary_gateway_account_id = 're_clksq41c54igg019t1bbhownk' 
		   	where 
		   	    gateway_account_id = 're_clksq41c54igg019t1bbhownk'
			and 
		   	    id = 510
			and 
		   	    store_id = 246
			and
		   	    secondary_gateway_account_id is null
		";

		$sql_247 = "
			update 
			    gateway_subaccounts 
			set 
			    secondary_gateway_account_id = 're_clkwruyn3l08q019ttu8dv1ii' 
		   	where 
		   	    gateway_account_id = 're_clkwruyn3l08q019ttu8dv1ii'
			and 
		   	    id = 511
			and 
		   	    store_id = 247
			and
		   	    secondary_gateway_account_id is null
		";

		$this->db->query($sql_246);
		$this->db->query($sql_247);
	}

	public function down()	{
	}
};