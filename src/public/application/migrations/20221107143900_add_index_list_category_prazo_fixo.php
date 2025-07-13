<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        
        
        $this->db->query("create index index_by_active_blocked_cross_docking on categories (active,blocked_cross_docking) algorithm=inplace lock=none;");
	 }

	public function down()	{

        $this->db->query("drop index index_by_active_blocked_cross_docking on categories");

	}
};