<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $results = $this->db->where('description', 'Commerce Plus')->get('integration_erps')->result_array();

        if (empty($results)) {
            $this->db->insert("integration_erps", array(
                'name'          => 'commerce_plus',
                'description'   => 'Commerce Plus',
                'type'          => 2,
                'hash'          => '56ac6795f4913ee0bd57dbcba2949e1e1b044df6',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'commerceplus.png'
            ));
        } else {
            $this->db->where('id', $results[0]['id'])->update('integration_erps', array('name' => 'commerce_plus', 'hash' => '56ac6795f4913ee0bd57dbcba2949e1e1b044df6'));
        }
	 }

	public function down()	{
        $this->db->query("DELETE FROM description WHERE `description` = 'Commerce Plus';");
	}
};