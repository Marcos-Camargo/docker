<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table collections
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'mktp_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'long_description' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'path' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'active' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => TRUE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("collections", TRUE);
		$this->db->query('ALTER TABLE  `collections` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table collections ##
		$this->dbforge->drop_table("collections", TRUE);

	}
};