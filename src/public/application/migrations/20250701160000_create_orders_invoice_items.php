<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('orders_invoice_items')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ],
                'invoice_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => FALSE,
                ],
                'order_item_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => FALSE,
                ],
                'qty_invoiced' => [
                    'type' => 'INT',
                    'null' => FALSE,
                    'default' => 0,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('orders_invoice_items', TRUE);
            $this->db->query('ALTER TABLE `orders_invoice_items` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `orders_invoice_items` ADD CONSTRAINT `orders_invoice_items_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `orders_invoices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
            $this->db->query('ALTER TABLE `orders_invoice_items` ADD CONSTRAINT `orders_invoice_items_order_item_id` FOREIGN KEY (`order_item_id`) REFERENCES `orders_item`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
            $this->db->query('ALTER TABLE `orders_invoice_items` ADD KEY `orders_invoice_items_invoice_id` (`invoice_id`)');
            $this->db->query('ALTER TABLE `orders_invoice_items` ADD KEY `orders_invoice_items_order_item_id` (`order_item_id`)');
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('orders_invoice_items', TRUE);
    }
};
