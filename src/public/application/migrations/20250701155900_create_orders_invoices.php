<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {

        if (!$this->db->table_exists('orders_invoices')) {
            $this->dbforge->add_field([

                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ],
                'order_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => FALSE,
                ],
                'invoice_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => FALSE,
                    'default' => 0,

                ],
                'invoice_date' => [
                    'type' => 'DATETIME',
                    'null' => FALSE,
                ],
                'invoice_number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => TRUE,
                ],
                'invoice_key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => TRUE,
                ],
                'invoice_xml' => [
                    'type' => 'TEXT',
                    'null' => TRUE,
                ],
                'invoice_pdf' => [
                    'type' => 'TEXT',
                    'null' => TRUE,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('order_id');
            $this->dbforge->create_table('orders_invoices', TRUE);
            $this->db->query('ALTER TABLE `orders_invoices` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `orders_invoices` ADD CONSTRAINT `orders_invoices_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }

    public function down()
    {

        $this->dbforge->drop_table('orders_invoices', TRUE);

    }
};
