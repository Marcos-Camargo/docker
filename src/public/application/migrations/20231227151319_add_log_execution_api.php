<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table log_execution_api
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'url' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'method' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'method_type' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'start_date' => array(
				'type' => 'TIMESTAMP',
				'null' => TRUE,

			),
			'end_date' => array(
				'type' => 'TIMESTAMP',
				'null' => TRUE,

			),
			'execution_time' => array(
				'type' => 'DECIMAL',
				'constraint' => ('15,0'),
				'null' => FALSE,

			),
			'integration' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'request_body' => array(
				'type' => 'TEXT',
				'null' => TRUE,

			),
			'response_code' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'date_created timestamp not null default current_timestamp'
		));

		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("log_execution_api", TRUE);
		$this->db->query('ALTER TABLE  `log_execution_api` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table log_execution_api ##
		$this->dbforge->drop_table("log_execution_api", TRUE);

	}
};