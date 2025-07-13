<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table orders_commision_charges
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'observation' => array(
				'type' => 'VARCHAR',
				'constraint' => ('2000'),
				'null' => FALSE,

			),
			'file' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'users_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'`date_create` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ',
		)); 
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("orders_commision_charges", TRUE);
		$this->db->query('ALTER TABLE  `orders_commision_charges` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table orders_commision_charges ##
		$this->dbforge->drop_table("orders_commision_charges", TRUE);

	}
};