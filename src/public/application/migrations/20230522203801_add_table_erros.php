<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => ('60'),
                'null' => FALSE
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => '0',
            ),
            'msg' => array(
                'type' => 'VARCHAR',
                'constraint' => ('100'),
                'null' => FALSE
            ),
            'icon' => array(
                'type' => 'VARCHAR',
                'constraint' => ('100'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("errors", TRUE);
    }

    public function down()	{
        $this->dbforge->drop_table("errors", TRUE);
    }
};
