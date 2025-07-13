<?php defined('BASEPATH') OR exit('No direct script access allowed');
return new class extends CI_Migration
{
    public function up() {
        $fieldNew = array(
            'group_description' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE,
                'after' => 'permission'
            )
        );
        if (!$this->dbforge->column_exists('group_description', 'groups')){
            $this->dbforge->add_column('groups', $fieldNew);
        }
    }
    public function down()	{
        $this->dbforge->drop_column("groups", 'group_description');
    }
};