<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->where('blocked_cross_docking', 1)->update('categories', array('force_update' => 1));
	}

	public function down()	{}
};