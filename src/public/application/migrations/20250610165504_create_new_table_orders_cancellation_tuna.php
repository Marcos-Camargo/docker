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
            'order_id' => [
                'type' => 'INT',
                'null' => FALSE
            ],
			'sku' => [
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,
			],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => 0
			],

            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('orders_cancellation_tuna');
	}

	public function down()	{
        $this->dbforge->drop_table('orders_cancellation_tuna');
	}
};