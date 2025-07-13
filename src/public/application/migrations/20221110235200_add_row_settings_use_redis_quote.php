<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'enable_redis_quote')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "enable_redis_quote",
                'value' => 'Quando habilitado, será utilizado redis na cotação.',
                'status' => 2,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'enable_redis_quote')->delete('settings');
	}
};