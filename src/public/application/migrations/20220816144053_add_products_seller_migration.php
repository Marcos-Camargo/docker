<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->db->table_exists('products_seller_migration')) {
            ## Create Table products_seller_migration
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'product_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'sku_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'id_sku' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'seller_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'internal_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,

                ),
                'int_to' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('20'),
                    'null' => TRUE,

                ),
                'store_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,

                ),
                'date_disapproved' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,

                ),
            ));
            $this->dbforge->add_key("id", true);
            $this->dbforge->create_table("products_seller_migration", TRUE);
            $this->db->query('ALTER TABLE  `products_seller_migration` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `products_seller_migration` ADD CONSTRAINT `products_seller_migration_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

        }

	 }

	public function down()	{
		### Drop table products_seller_migration ##
		$this->dbforge->drop_table("products_seller_migration", TRUE);

	}
};