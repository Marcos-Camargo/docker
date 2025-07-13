<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("
			update job_schedule 
			set status = 7, 
				date_end = now() 
			where status=1 
			AND date_start < date_sub( now(), INTERVAL 1 hour) 
			AND (
				module_path = 'SellerCenter/Vtex/BrandsDownload' OR 
				module_path = 'CreateLogisticSummary' OR 
				module_path = 'SellerCenter/Vtex/CategoryV2' OR 
				module_path = 'UpdateMSSettings'
			);
	");
	
	}

	public function down()	{
	}
};