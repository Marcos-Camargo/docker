<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => TRUE,
                'auto_increment' => TRUE
            ],
            'store_reputation' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'cancellation_evaluation' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'shipping_delay_assessment' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'delivery_delay_assessment' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'average' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null'       => FALSE,
                'default'    => 0
            ],
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('query_seller_index');
    }

    public function down()	{
        $this->dbforge->drop_table('query_seller_index');
    }
};