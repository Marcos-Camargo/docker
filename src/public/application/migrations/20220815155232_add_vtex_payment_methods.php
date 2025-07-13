<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table vtex_payment_methods
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('10'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'int_to' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'method_id' => array(
				'type' => 'INT',
				'constraint' => ('3'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'method_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('70'),
				'null' => FALSE,

			),
			'method_description' => array(
				'type' => 'VARCHAR',
				'constraint' => ('250'),
				'null' => FALSE,

			),
			'active' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'`date_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
			'`date_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("vtex_payment_methods", TRUE);
		$this->db->query('ALTER TABLE  `vtex_payment_methods` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table vtex_payment_methods ##
		$this->dbforge->drop_table("vtex_payment_methods", TRUE);

	}
};