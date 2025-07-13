<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'tms_infracommerce')->get('integration_erps')->num_rows() === 0) {
            $this->db->insert('integration_erps', array(
                'name' => "tms_infracommerce",
                'description' => 'TMS Infracommerce',
                'type' => 3,
                'hash' => 'e2cfa53c84a02a60f8d2ad76b71548b69b0c693f',
                'active' => 0,
                'visible' => 1,
                'support_link' => '[]',
                'image' => 'tms_infracommerce.png',
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'tms_infracommerce')->delete('integration_erps');
	}
};