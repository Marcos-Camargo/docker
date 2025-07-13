<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->db->table_exists('legal_panel_fiscal')){
				
			## Create Table legal_panel_fiscal
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'notification_type' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'notification_title' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'orders_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'store_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,

				),
				'notification_id' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'status' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'description' => array(
					'type' => 'TEXT',
					'null' => TRUE,

				),
				'balance_paid' => array(
					'type' => 'DECIMAL',
					'constraint' => ('11,2'),
					'null' => FALSE,

				),
				'balance_debit' => array(
					'type' => 'DECIMAL',
					'constraint' => ('11,2'),
					'null' => FALSE,

				),
				'attachment' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'creation_date' => array(
					'type' => 'DATETIME',
					'null' => FALSE,

				),
				'`update_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
				'accountable_opening' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'accountable_update' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
				'conciliacao_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,

				),
				'lote' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => TRUE,

				),
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("legal_panel_fiscal", TRUE);
			$this->db->query('ALTER TABLE  `legal_panel_fiscal` ENGINE = InnoDB');
		}
	 }

	public function down()	{
		### Drop table legal_panel_fiscal ##
		$this->dbforge->drop_table("legal_panel_fiscal", TRUE);

	}
};