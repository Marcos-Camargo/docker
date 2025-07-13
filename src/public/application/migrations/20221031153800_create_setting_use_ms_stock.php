<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'use_ms_stock')){
            $this->db->insert('settings', array(
                'name'      => 'use_ms_stock',
                'value'     => 'Quando ativo, será direcionado todos os serviços para o microsserviço de estoque.',
                'status'    => 2,
                'user_id'   => 1
            ));
        }
    }

    public function down()	{
        $this->db->where('name', 'use_ms_stock')->delete('use_ms_stock');
    }
};