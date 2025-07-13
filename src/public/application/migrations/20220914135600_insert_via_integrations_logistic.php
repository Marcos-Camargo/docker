<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create row integrations_logistic to 'viavarejo_b2b'
        if (!$this->db->where('name', 'viavarejo_b2b')->get('integrations_logistic')->row_object()) {
            $this->db->insert('integrations_logistic', array(
                'name'              => 'viavarejo_b2b',
                'description'       => 'Via Varejo',
                'use_sellercenter'  => 0,
                'use_seller'        => 1,
                'active'            => 1,
                'only_store'        => 1,
                'fields_form'       => '{}',
                'user_created'      => NULL,
                'user_updated'      => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'viavarejo_b2b')->delete('integrations_logistic');
        $this->db->where('integration', 'viavarejo_b2b')->delete('integration_logistic');
	}
};