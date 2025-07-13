<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->db->table_exists('nota_fiscal_ciclo_financeiro_fiscal')){
				
			## Create Table nota_fiscal_ciclo_financeiro_fiscal
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'nota_fiscal_servico_url_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'conciliacao_fiscal_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'lote_fiscal' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'conciliacao_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'lote' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'company_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'`date_create` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("nota_fiscal_ciclo_financeiro_fiscal", TRUE);
			$this->db->query('ALTER TABLE  `nota_fiscal_ciclo_financeiro_fiscal` ENGINE = InnoDB');
		}
	 }

	public function down()	{
		### Drop table nota_fiscal_ciclo_financeiro_fiscal ##
		$this->dbforge->drop_table("nota_fiscal_ciclo_financeiro_fiscal", TRUE);

	}
};