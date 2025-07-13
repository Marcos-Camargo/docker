<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->insert('integration_erps',[
            'name'                  => 'mevo',
            'description'           => 'Mevo',
            'type'                  => 1,
            'hash'                  => 'b6644ede6217ccd015ea693ea20f7540010c084b',
            'active'                => 0,
            'visible'               => 0,
            'support_link'          => null,
            'configuration_form'    => null,
            'configuration'         => null,
            'image'                 => 'mevo.png',
        ]);

        $this->db->insert('integrations_logistic',[
            'name'              => 'mevo',
            'description'       => 'Mevo',
            'use_sellercenter'  => 0,
            'use_seller'        => 1,
            'active'            => 1,
            'only_store'        => 1,
            'fields_form'       => '{}',
        ]);
	}

	public function down()	{
        $this->db->delete('integration_erps',['name' => 'mevo'], 1);
        $this->db->delete('integrations_logistic',['name' => 'mevo'], 1);
	}
};