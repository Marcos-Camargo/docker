<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table products_collections
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'collection_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'mktp_collection_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'date' => array(
				'type' => 'DATETIME',
				'null' => TRUE,

			),
			'user' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'product_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("products_collections", TRUE);
		$this->db->query('ALTER TABLE  `products_collections` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table products_collections ##
		$this->dbforge->drop_table("products_collections", TRUE);

	}
};