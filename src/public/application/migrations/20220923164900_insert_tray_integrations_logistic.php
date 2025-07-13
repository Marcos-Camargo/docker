<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create row integrations_logistic to 'tray'
        if (!$this->db->where('name', 'tray')->get('integrations_logistic')->row_object()) {
            $this->db->insert('integrations_logistic', array(
                'name'              => 'tray',
                'description'       => 'Tray',
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
        $this->db->where('name', 'tray')->delete('integrations_logistic');
        $this->db->where('integration', 'tray')->delete('integration_logistic');
	}
};