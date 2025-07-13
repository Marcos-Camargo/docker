<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("update job_schedule js set js.status = 2, js.date_end = now() where status=1 AND date_start < date_sub( now(), INTERVAL 7 hour) and (module_path = 'SellerCenter/Vtex/VtexOrders' or module_path = 'SellerCenter/Vtex/VtexOrdersStatus');");
	
	}

	public function down()	{
	}
};