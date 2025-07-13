<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create row integrations_logistic to 'Tiny'
        if (!$this->db->where('name', 'tiny')->get('integrations_logistic')->row_object()) {
            $this->db->insert('integrations_logistic', array(
                'name'              => 'tiny',
                'description'       => 'Tiny',
                'use_sellercenter'  => 0,
                'use_seller'        => 1,
                'active'            => 1,
                'fields_form'       => '{}',
                'user_created'      => NULL,
                'user_updated'      => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'tiny')->delete('integrations_logistic');
        $this->db->where('integration', 'tiny')->delete('integration_logistic');
	}
};