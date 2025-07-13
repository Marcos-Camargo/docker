<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'vertem_product_webhook'))
        {
            $this->db->insert('settings', array(
                'name'      => "vertem_product_webhook",
                'value'     => '',
                'status'    => 2
            ));
        }
    }

    public function down()
    {
        $this->db->where('name', 'vertem_product_webhook')->delete('settings');
    }
};