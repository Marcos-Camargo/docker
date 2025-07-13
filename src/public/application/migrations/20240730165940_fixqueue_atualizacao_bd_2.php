<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("update job_schedule js set js.status = 0, js.date_end = null, js.start_alert = null where status=1 AND date_start > date_sub( now(), INTERVAL 0 hour);");
		$this->db->query("update job_schedule js set js.status = 0, js.date_end = null, js.start_alert = null where status <> 0 AND date_start > date_sub( now(), INTERVAL 0 hour);");
	
	}

	public function down()	{
	}
};