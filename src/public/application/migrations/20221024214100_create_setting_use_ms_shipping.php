<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'use_ms_shipping')){
            $this->db->insert('settings', array(
                'name'      => 'use_ms_shipping',
                'value'     => 'Quando ativo, será direcionado todos os serviços para o microsserviço shipping.',
                'status'    => 2,
                'user_id'   => 1
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'microservice_api_url')->delete('use_ms_shipping');
	}
};
