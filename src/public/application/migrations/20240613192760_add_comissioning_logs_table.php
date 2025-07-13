<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        ## Create Table commissioning_logs
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'commissioning_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'unsigned' => TRUE
            ),
            'model' => array(
                'type' => 'VARCHAR',
                'constraint' => '255'
            ),
            'method' => array(
                'type' => 'VARCHAR',
                'constraint' => '255'
            ),
            'model_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => TRUE,
                'default' => NULL
            ),
            'data' => array(
                'type' => 'TEXT'
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => TRUE
            ),
            '`created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ',
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('commissioning_logs', TRUE);
    }

    public function down() {
        ## Drop table commissioning_logs ##
        $this->dbforge->drop_table('commissioning_logs', TRUE);
    }
};
