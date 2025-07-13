<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'store_id' => [
                'type' => 'INT',
                'null' => FALSE
            ],
            'marketplace' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => FALSE
            ],
            'order_to_delivered_config_id' => [
                'type' => 'INT',
                'null' => FALSE
            ],
            'date_create' => [
                'type' => 'DATETIME',
                'null' => FALSE
            ]
        ]);
        
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key(['store_id', 'marketplace']);

        $this->dbforge->create_table('order_to_delivered_tracking');
    }

    public function down() {
        $this->dbforge->drop_table('order_to_delivered_tracking');

    }
};