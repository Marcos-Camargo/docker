<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $fieldUpdate = array(
            'agidesk_agent_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => NULL,
            ),
        );

        if (!$this->dbforge->column_exists('agidesk_agent_id', 'users')) {
            $this->dbforge->add_column('users', $fieldUpdate);
        }
    }

    public function down()
    {
        if ($this->dbforge->column_exists('agidesk_agent_id', 'users')) {
            $this->dbforge->drop_column('users', 'agidesk_agent_id');
        }
    }
};
