<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query('DELETE FROM `vtex_payment_methods` WHERE id NOT IN (SELECT method_id FROM campaign_v2_payment_methods)');
        $this->db->query('UPDATE vtex_payment_methods SET active = 1');
	}

	public function down()	{
	}
};