<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("
		UPDATE job_schedule js
		SET js.status = 2,
   			js.date_end = NOW()
		WHERE status = 6
  			AND date_start < DATE_SUB(NOW(), INTERVAL 20 MINUTE);
	");
	
	}

	public function down()	{
	}
};