<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        ## Create Table integrations_webhook
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'store_id' => array(
                'type' => 'INT',
                'null' => FALSE
            ),
            'id_supplier' => array(
                'type' => 'INT',
                'null' => true
            ),
            'url' => array(
                'type' => 'VARCHAR',
                'constraint' => 250,
                'null' => FALSE
            ),
            'type_webhook' => array(
                'type' => 'VARCHAR',
				'constraint' => 250,
                'null' => FALSE
            ),'is_supplier' => array(
				'type' => 'TINYINT',
				'constraint' => 1,
				'default' => 0 
			),
			'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("integrations_webhook", TRUE);
    }

    public function down() {
        $this->dbforge->drop_table("integrations_webhook", TRUE);
    }
};
