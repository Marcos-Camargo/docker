<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'timeout_quote_ms')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "timeout_quote_ms",
                'value' => 1500,
                'status' => 1,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'timeout_quote_ms')->delete('settings');
	}
};