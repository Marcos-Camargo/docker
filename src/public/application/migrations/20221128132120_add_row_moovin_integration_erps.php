<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $results = $this->db->where('description', 'Moovin')->get('integration_erps')->result_array();

        if (empty($results)) {
            $this->db->insert("integration_erps", array(
                'name'          => 'moovin',
                'description'   => 'Moovin',
                'type'          => 2,
                'hash'          => '424e9bd832a6f97cfcdd6f6c207b69445d82ce43',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'moovin.png'
            ));
        } else {
            $this->db->where('id', $results[0]['id'])->update('integration_erps', array('name' => 'moovin', 'hash' => '424e9bd832a6f97cfcdd6f6c207b69445d82ce43'));
        }
	 }

	public function down()	{
        $this->db->query("DELETE FROM description WHERE `description` = 'Moovin';");
	}
};