<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->db->table_exists('seller_migration_register')) {

            ## Create Table seller_migration_register
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'seller_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'store_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,

                ),
                'status' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,
                    'default' => '0',

                ),
                '`created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ',
                'uptaded_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,

                ),
                'import_start_date' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,

                ),
            ));
            $this->dbforge->add_key("id", true);
            $this->dbforge->create_table("seller_migration_register", TRUE);
            $this->db->query('ALTER TABLE  `seller_migration_register` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `seller_migration_register` ADD CONSTRAINT `seller_migration_register_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

        }

	 }

	public function down()	{
		### Drop table seller_migration_register ##
		$this->dbforge->drop_table("seller_migration_register", TRUE);

	}
};