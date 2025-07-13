<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$results = $this->db->where('description', 'Lexos')->get('integration_erps')->result_array();
		if (empty($results)) {
			$this->db->insert("integration_erps", array(
				'name'          => 'lexos',
				'description'   => 'Lexos',
				'type'          => 2,
				'hash'          => 'eeca8a7a9c41eb3b249c996994fe6a985c1060c4',
				'active'        => 1,
				'visible'       => 1,
				'support_link'  => '[]',
				'image'         => 'lexos.png'
			));
		} else {
			$this->db->where('id', $results[0]['id'])->update('integration_erps', array('image' => 'lexos.png', 'hash' => 'eeca8a7a9c41eb3b249c996994fe6a985c1060c4'));
		}
	}

	public function down()	{
	}
};