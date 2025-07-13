<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->db->table_exists('conciliacao_fiscal')){

			## Create Table conciliacao_fiscal
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'lote' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'`data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
				'status' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,
					'default' => '\'Conciliação Aprovada\'',

				),
				'ativo' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,
					'default' => '1',

				),
				'integ_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'param_mkt_ciclo_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'ano_mes' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'status_repasse' => array(
					'type' => 'TINYINT',
					'constraint' => ('4'),
					'null' => FALSE,
					'default' => '21',

				),
				'current_installment' => array(
					'type' => 'TINYINT',
					'constraint' => ('4'),
					'null' => FALSE,
					'default' => '1',

				),
				'total_installments' => array(
					'type' => 'TINYINT',
					'constraint' => ('4'),
					'null' => FALSE,
					'default' => '1',

				),
				'users_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,

				),
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("conciliacao_fiscal", TRUE);
			$this->db->query('ALTER TABLE  `conciliacao_fiscal` ENGINE = InnoDB');
		}
	 }

	public function down()	{
		### Drop table conciliacao_fiscal ##
		$this->dbforge->drop_table("conciliacao_fiscal", TRUE);

	}
};