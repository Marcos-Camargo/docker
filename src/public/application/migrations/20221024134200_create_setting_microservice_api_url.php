<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'microservice_api_url')){
            $this->db->insert('settings', array(
                'name'      => 'microservice_api_url',
                'value'     => 'https://ms.conectala.com.br',
                'status'    => 1,
                'user_id'   => 1
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'microservice_api_url')->delete('settings');
	}
};
