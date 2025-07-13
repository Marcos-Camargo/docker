<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
			'int_to' => array(
				'type' => 'VARCHAR',
				'constraint' => ('128'),
				'null' => FALSE,
			),
            'category_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
            'category_marketplace_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
            'attribute_marketplace_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
            'attribute_seller_value' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => FALSE,
            ),
			'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
			'`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("seller_attributes_marketplace", TRUE);

        if (!$this->dbforge->index_exists('ix_store_id', 'seller_attributes_marketplace')) {
            $this->db->query('ALTER TABLE `seller_attributes_marketplace` ADD INDEX `ix_store_id` (`store_id`);');
        }
        if (!$this->dbforge->index_exists('ix_store_id_category_id_int_to', 'seller_attributes_marketplace')) {
            $this->db->query('ALTER TABLE `seller_attributes_marketplace` ADD INDEX `ix_store_id_category_id_int_to` (`store_id`, `category_id`, `int_to`);');
        }

	}

	public function down()	{
        if ($this->dbforge->index_exists('ix_store_id', 'seller_attributes_marketplace')) {
            $this->db->query('ALTER TABLE `seller_attributes_marketplace` DROP INDEX `ix_store_id`;');
        }

        if ($this->dbforge->index_exists('ix_store_id_category_id_int_to', 'seller_attributes_marketplace')) {
            $this->db->query('ALTER TABLE `seller_attributes_marketplace` DROP INDEX `ix_store_id_category_id_int_to`;');
        }

        if ($this->db->table_exists('seller_attributes_marketplace')){
            $this->dbforge->drop_table("seller_attributes_marketplace", TRUE);
        }
	}
};