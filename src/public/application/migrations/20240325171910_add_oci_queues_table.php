<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        if (!$this->db->table_exists('oci_queues')){
            // Define table fields
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE
                ),
                'oci_queue_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => FALSE
                ),
                'display_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => TRUE
                ),
                'url' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => TRUE
                ),
                '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
                '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            ));

            // Define primary key
            $this->dbforge->add_key('id', TRUE);

            // Create table
            $this->dbforge->create_table('oci_queues');
        }

    }

    public function down()
    {
        $this->dbforge->drop_table("oci_queues", TRUE);
    }
};