<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'identifying_technical_specification'))
        {
            $this->db->insert('settings', array(
                'name'                  => "identifying_technical_specification",
                'value'                 => '',
                'description'           => 'Especificação Técnica Identificadora',
                'status'                => 2,
                'setting_category_id'   => 7,
                'user_id'               => 1,
                'friendly_name'         => 'Especificação Técnica Identificadora'
            ));
        }
    }

    public function down()
    {
        if ($this->dbforge->register_exists('settings', 'name', 'identifying_technical_specification')) {
            $this->db->delete('settings', array('name' => 'identifying_technical_specification'));
        }
    }
};