<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'time_exp_redis_s')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "time_exp_redis_s",
                'value' => 30,
                'status' => 1,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'time_exp_redis_s')->delete('settings');
	}
};