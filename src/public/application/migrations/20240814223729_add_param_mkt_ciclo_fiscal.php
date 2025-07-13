<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table param_mkt_ciclo_fiscal
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'integ_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'data_inicio' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'data_fim' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'data_ciclo_fiscal' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'data_usada' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,
				'default' => 'Data Entrega',

			),
			'data_alteracao' => array(
				'type' => 'TIMESTAMP',
				'null' => TRUE,

			),
			'ativo' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'default' => '1',

			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'`date_insert` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("param_mkt_ciclo_fiscal", TRUE);
		$this->db->query('ALTER TABLE  `param_mkt_ciclo_fiscal` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table param_mkt_ciclo_fiscal ##
		$this->dbforge->drop_table("param_mkt_ciclo_fiscal", TRUE);

	}
};