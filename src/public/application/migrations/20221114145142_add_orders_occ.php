<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table orders_occ
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'json' => array(
				'type' => 'TEXT',
				'null' => TRUE,

			),
			'date_created' => array(
				'type' => 'DATETIME',
				'null' => TRUE,

			),
			'date_updated' => array(
				'type' => 'DATETIME',
				'null' => TRUE,

			),
			'status' => array(
				'type' => 'TINYINT',
				'constraint' => ('2'),
				'null' => TRUE,
				'default' => '0',

			),
			'order_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('60'),
				'null' => FALSE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("orders_occ", TRUE);
		$this->db->query('ALTER TABLE  `orders_occ` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table orders_occ ##
		$this->dbforge->drop_table("orders_occ", TRUE);

	}
};