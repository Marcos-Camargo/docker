<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (ENVIRONMENT == 'development') {
            $this->db->insert('settings', array(
                'name' => "fix_calendar_oep_1812",
                'value' => '-',
                'setting_category_id' => 3,
                'status' => 1,
                'description' => '-',
                'friendly_name' => '-'
            ));
        }
    }

	public function down()	{}
};