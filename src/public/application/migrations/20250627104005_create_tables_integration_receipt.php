<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		$this->dbforge->add_field([
			'id' => [
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			],
			'ticket' => [
				'type' => 'VARCHAR',
				'constraint' => '255',
				'null' =>	FALSE
			],
			'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP '
		]);

		$this->dbforge->add_key('id', true);
		$this->dbforge->create_table('integration_tickets');
		$this->db->query("ALTER TABLE integration_tickets ADD CONSTRAINT unique_lock UNIQUE(ticket);");

		$this->dbforge->add_field([
			'ticket_id' => [
				'type' => 'INT',
				'null' => FALSE
			],
			'prd_id' => [
				'type' => 'INT',
				'null' => FALSE
			],
			'sku_mkt' => [
				'type' => 'VARCHAR',
				'constraint' => '255',
				'null' => FALSE
			],
			'queue_id' => [
				'type' => 'INT',
				'null' => FALSE
			],
			'status' => [
				'type' => 'VARCHAR',
				'constraint' => 32,
				'null' => TRUE
			],
			'finished' => [
				'type' => 'TINYINT',
				'constraint' => 1,
				'null' => FALSE,
				'default' => 0
			]
		]);

		$this->dbforge->create_table('integration_ticket_history');
		$this->db->query("CREATE INDEX idx_receipt_prd_queue ON integration_ticket_history(ticket_id, prd_id, sku_mkt, queue_id);");
		$this->db->query("ALTER TABLE integration_ticket_history ADD CONSTRAINT unique_lock UNIQUE(sku_mkt);");
	}

	public function down()
	{
		$this->dbforge->drop_table('integration_tickets');
		$this->dbforge->drop_table('integration_ticket_history');
	}
};
