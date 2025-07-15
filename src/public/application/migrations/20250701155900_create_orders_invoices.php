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
                    'unsigned' => TRUE,
                    'null' => FALSE,
                ],
                'invoice_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => FALSE,
                ],
                'invoice_date' => [
                    'type' => 'DATETIME',
                    'null' => FALSE,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('orders_invoices', TRUE);
            $this->db->query('ALTER TABLE `orders_invoices` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `orders_invoices` ADD CONSTRAINT `orders_invoices_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
            $this->db->query('ALTER TABLE `orders_invoices` ADD KEY `orders_invoices_order_id` (`order_id`)');
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('orders_invoices', TRUE);
    }
};
