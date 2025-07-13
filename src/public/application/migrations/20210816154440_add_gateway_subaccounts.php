<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table gateway_subaccounts
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => FALSE,

			),
			'gateway_id' => array(
				'type' => 'VARCHAR',
				'constraint' => 50,
				'null' => FALSE,

			),
			'gateway_name' => array(
				'type' => 'VARCHAR',
				'constraint' => 50,
				'null' => FALSE,

			),
			'`date_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
			'`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
			'bank_account_id' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("gateway_subaccounts", TRUE);
		$this->db->query('ALTER TABLE  `gateway_subaccounts` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table gateway_subaccounts ##
		$this->dbforge->drop_table("gateway_subaccounts", TRUE);

	}

};