<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        // Create orders_invoice_items table
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'invoice_id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ],
            'order_item_id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ],
            'qty' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('orders_invoice_items');
    }

    public function down()
    {
        $this->dbforge->drop_table('orders_invoice_items');
    }
};
