<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table log_attributes_value
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'users_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'attribute_value_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'value' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'code' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'enabled' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,
				'default' => '1',

			),
			'visible' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,
				'default' => '1',

			),
			'attribute_parent_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'commission_charges' => array(
				'type' => 'INT',
				'constraint' => ('1'),
				'null' => TRUE,
				'default' => '0',

			),
			'default_reason' => array(
				'type' => 'INT',
				'constraint' => ('1'),
				'null' => TRUE,
				'default' => '0',

			),
			'active' => array(
				'type' => 'INT',
				'constraint' => ('1'),
				'null' => FALSE,
				'default' => '1',

			),
			'action' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'`date_insert` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("log_attributes_value", TRUE);
		$this->db->query('ALTER TABLE  `log_attributes_value` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table log_attributes_value ##
		$this->dbforge->drop_table("log_attributes_value", TRUE);

	}
};