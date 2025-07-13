<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table magalupay_extrato
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
				'null' => TRUE,

			),
			'numero_marketplace' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'status' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'amount' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'captured_at' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'created_at' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'public_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'reference_key' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'metadata' => array(
				'type' => 'VARCHAR',
				'constraint' => ('1000'),
				'null' => TRUE,

			),
			'payment_methods' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'chave_md5' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'`date_create` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_update` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp'
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("magalupay_extrato", TRUE);
		$this->db->query('ALTER TABLE  `magalupay_extrato` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table magalupay_extrato ##
		$this->dbforge->drop_table("magalupay_extrato", TRUE);

	}
};