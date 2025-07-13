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
			'trade_policy_id' => array(
				'type' => 'INT',
				'constraint' => ('3'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'trade_policy_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'is_default' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
			'`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("vtex_trade_policies", TRUE);
		$this->db->query('ALTER TABLE  `vtex_trade_policies` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table vtex_payment_methods ##
		$this->dbforge->drop_table("vtex_trade_policies", TRUE);

	}
};