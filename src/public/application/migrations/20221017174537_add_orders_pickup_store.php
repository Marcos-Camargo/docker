<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table orders_pickup_store
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'order_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'marketplace_order_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('45'),
				'null' => FALSE,

			),
			'client_document' => array(
				'type' => 'VARCHAR',
				'constraint' => ('45'),
				'null' => FALSE,

			),
			'client_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('45'),
				'null' => FALSE,

			),
			'store_pickup_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('45'),
				'null' => FALSE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("orders_pickup_store", TRUE);
		$this->db->query('ALTER TABLE  `orders_pickup_store` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table orders_pickup_store ##
		$this->dbforge->drop_table("orders_pickup_store", TRUE);

	}
};