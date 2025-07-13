<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("update job_schedule js set js.status = 2, js.date_end = now() where date_start >= '2024-07-30 00:00:00' and date_start <= '2024-07-30 06:00:00' and status = 1;");
	
	}

	public function down()	{
	}
};