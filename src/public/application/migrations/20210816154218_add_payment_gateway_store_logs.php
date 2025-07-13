<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table payment_gateway_store_logs
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => FALSE,

			),
			'payment_gateway_id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => FALSE,

			),
			'status' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => FALSE,

			),
			'description' => array(
				'type' => 'TEXT',
				'null' => FALSE,

			),
			'`date_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
			'`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("payment_gateway_store_logs", TRUE);
		$this->db->query('ALTER TABLE  `payment_gateway_store_logs` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table payment_gateway_store_logs ##
		$this->dbforge->drop_table("payment_gateway_store_logs", TRUE);

	}

};