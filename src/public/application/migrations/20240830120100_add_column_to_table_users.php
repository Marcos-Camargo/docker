<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $fieldUpdate = array(
            'make_user_agent' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ),
        );

        if (!$this->dbforge->column_exists('make_user_agent', 'users')) {
            $this->dbforge->add_column('users', $fieldUpdate);
        }
    }

    public function down()
    {
        if ($this->dbforge->column_exists('make_user_agent', 'users')) {
            $this->dbforge->drop_column('users', 'make_user_agent');
        }
    }
};
