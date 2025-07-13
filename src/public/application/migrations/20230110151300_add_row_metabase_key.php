<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if ($this->db->where('name', 'metabase_key')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "metabase_key",
                'value' => 'ae6d1865a5a5f98308d1fab73891d7372b55f12627d2e9bb1611517040c62a13',
                'status' => 1,
                'user_id' => 1
            ));
        }
    }

    public function down()	{
        $this->db->where('name', 'metabase_key')->delete('settings');
    }
};