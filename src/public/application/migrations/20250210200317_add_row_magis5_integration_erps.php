<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$results = $this->db->where('description', 'Magis5')->get('integration_erps')->result_array();

		if (empty($results)) {
			$this->db->insert("integration_erps", array(
				'name'          => 'magis5',
				'description'   => 'Magis5',
				'type'          => 2,
				'hash'          => '559543be2c130903a658dd7b65ab430e9ac37703',
				'active'        => 1,
				'visible'       => 1,
				'support_link'  => '[]',
				'image'         => 'magis5.png'
			));
		} else {
			$this->db->where('id', $results[0]['id'])->update('integration_erps', array('image' => 'magis5.png', 'hash' => '559543be2c130903a658dd7b65ab430e9ac37703'));
		}
	}

	public function down()	{
	}
};