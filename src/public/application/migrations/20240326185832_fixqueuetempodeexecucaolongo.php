<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("
		update job_schedule js set js.status = 7, js.date_end = now() WHERE status=1 AND now() > start_alert;
	");
	
	}

	public function down()	{
	}
};