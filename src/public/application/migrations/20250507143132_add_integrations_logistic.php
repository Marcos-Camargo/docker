<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'tms_infracommerce')->get('integrations_logistic')->num_rows() === 0) {
            $integration_erp = $this->db->get_where('integration_erps', array('name' => 'tms_infracommerce'))->row_array();
            $this->db->insert('integrations_logistic', array(
                'name' => "tms_infracommerce",
                'description' => 'TMS Infracommerce',
                'use_sellercenter' => 0,
                'use_seller' => 0,
                'active' => 1,
                'only_store' => 0,
                'fields_form' => '{"endpoint":{"name":"application_own_logistic_endpoint","type":"text"},"api_key":{"name":"application_token","type":"password"},"platform":{"name":"application_platform","type":"text"}}',
                'external_integration_id' => $integration_erp['id']
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'tms_infracommerce')->delete('integrations_logistic');
	}
};