<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        ## Create Table commissionings
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => '255'
            ),
            'type' => array(
                'type' => 'VARCHAR',
                'constraint' => '255'
            ),
            'int_to' => array(
                'type' => 'VARCHAR',
                'constraint' => '20'
            ),
            'start_date' => array(
                'type' => 'DATETIME'
            ),
            'end_date' => array(
                'type' => 'DATETIME'
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('commissionings', TRUE);
    }

    public function down() {
        ## Drop table commissionings ##
        $this->dbforge->drop_table('commissionings', TRUE);
    }
};
