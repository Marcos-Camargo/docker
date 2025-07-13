<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $results = $this->db->where('description', 'Vtrina')->get('integration_erps')->result_array();

        if (empty($results)) {
            $this->db->insert("integration_erps", array(
                'name'          => 'vtrina',
                'description'   => 'Vtrina',
                'type'          => 2,
                'hash'          => '5b56afa8caf760b9006645bbcf70407983da6dd9',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'vtrina.png'
            ));
        } else {
            $this->db->where('id', $results[0]['id'])->update('integration_erps', array('name' => 'vtrina', 'hash' => '5b56afa8caf760b9006645bbcf70407983da6dd9'));
        }
	 }

	public function down()	{
        $this->db->query("DELETE FROM description WHERE `description` = 'Vtrina';");
	}
};