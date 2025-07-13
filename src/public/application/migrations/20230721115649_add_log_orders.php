<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table log_orders
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'order_json' => array(
				'type' => 'TEXT',
				'null' => FALSE,

			),
			'id_marketplace' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'`date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("log_orders", TRUE);
		$this->db->query('ALTER TABLE  `log_orders` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table log_orders ##
		$this->dbforge->drop_table("log_orders", TRUE);

	}
};