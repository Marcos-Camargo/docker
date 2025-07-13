<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("
		update job_schedule js set js.status = 2, js.date_end = now() where status=6 AND date_start < '2025-02-18 14:35:00';
	");
	
	}

	public function down()	{
	}
};