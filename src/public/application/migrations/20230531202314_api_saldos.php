<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if ($this->db->table_exists('getnet_saldos')){
			$this->dbforge->drop_table("getnet_saldos", TRUE);
		}

		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'subseller_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'data_saldo' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'valor_disponivel' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
			'`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("getnet_saldos", TRUE);
		$this->db->query('ALTER TABLE  `getnet_saldos` ENGINE = InnoDB');
		$this->db->query('CREATE INDEX getnet_saldos_store_id_IDX USING BTREE ON getnet_saldos (store_id);');
		$this->db->query('CREATE INDEX getnet_saldos_subseller_id_IDX USING BTREE ON getnet_saldos (subseller_id);');


	}

	public function down()	{

		if ($this->db->table_exists('getnet_saldos')){
			$this->dbforge->drop_table("getnet_saldos", TRUE);
		}

	}
};