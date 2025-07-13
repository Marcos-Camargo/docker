<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->db->table_exists('repasse_temp_fiscal')){

			## Create Table repasse_temp_fiscal
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
					'null' => FALSE,

				),
				'conciliacao_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'store_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'name' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'valor_conectala' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'valor_seller' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'responsavel' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'status_repasse' => array(
					'type' => 'VARBINARY',
					'constraint' => ('2'),
					'null' => FALSE,

				),
				'refund' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("repasse_temp_fiscal", TRUE);
			$this->db->query('ALTER TABLE  `repasse_temp_fiscal` ENGINE = InnoDB');
		}
	 }

	public function down()	{
		### Drop table repasse_temp_fiscal ##
		$this->dbforge->drop_table("repasse_temp_fiscal", TRUE);

	}
};