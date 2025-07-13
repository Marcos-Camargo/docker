<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table commissioning_orders_items
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'order_id' => array(
				'type' => 'INT',
				'null' => FALSE,
                'unsigned' => TRUE,

			),
			'item_id' => array(
				'type' => 'INT',
				'null' => FALSE,

			),
			'commissioning_id' => array(
				'type' => 'INT',
				'null' => FALSE,
                'unsigned' => TRUE,

			),
			'comission' => array(
				'type' => 'DECIMAL',
				'constraint' => ('5,2'),
				'null' => FALSE,

			),
            'total_comission' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null' => FALSE,

            ),
			'product_price' => array(
				'type' => 'DECIMAL',
				'constraint' => ('10,2'),
				'null' => FALSE,

			),
			'product_quantity' => array(
				'type' => 'INT',
				'null' => FALSE,

			),
			'total_product_price' => array(
				'type' => 'DECIMAL',
				'constraint' => ('10,2'),
				'null' => FALSE,

			),
            '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("commissioning_orders_items", TRUE);
		$this->db->query('ALTER TABLE  `commissioning_orders_items` ENGINE = InnoDB');

        $this->db->query('ALTER TABLE `commissioning_orders_items` ADD CONSTRAINT `commissioning_orders_items_commissioning_id` FOREIGN KEY (`commissioning_id`) REFERENCES `commissionings` (`id`)');
        $this->db->query('ALTER TABLE `commissioning_orders_items` ADD CONSTRAINT `commissioning_orders_items_item_id` FOREIGN KEY (`item_id`) REFERENCES `orders_item` (`id`)');
        $this->db->query('ALTER TABLE `commissioning_orders_items` ADD INDEX `idx_commissioning_orders_items_order_id` (`order_id`);');
	 }

	public function down()	{
		### Drop table commissioning_orders_items ##
		$this->dbforge->drop_table("commissioning_orders_items", TRUE);

	}
};