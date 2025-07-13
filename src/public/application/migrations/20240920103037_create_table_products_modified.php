<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table products_modified
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'sku' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,
			),	
			'prd_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),			
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),
			'data_ultModificacao' => array(
				'type' => 'DATETIME',
				'null' => TRUE,
			),
			'date_create timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP '
		));
		$this->dbforge->add_key("id", true);
		$this->dbforge->create_table("products_modified", TRUE);		
	}

	public function down() {
		### Drop table products_modified ##
		$this->dbforge->drop_table("products_modified", TRUE);
	}
};
