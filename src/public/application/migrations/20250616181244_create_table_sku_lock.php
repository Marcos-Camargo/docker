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
			'prd_id' => [
				'type' => 'INT',
				'null' => FALSE
			],
			'sku_mkt' => [
				'type' => 'VARCHAR',
				'constraint' => '255',
				'null' => TRUE
			],
			'marketplace' => [
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => FALSE
			],
			'external_id' => [
				'type' => 'INT'
			],
			'note' => [
				'type' => 'VARCHAR',
				'constraint' => '255'
			],
			'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP '
		]);

		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->add_key('prd_id');
		$this->dbforge->add_key('sku_mkt');

		$this->dbforge->create_table('sku_locks');

		$this->db->query("ALTER TABLE sku_locks ADD CONSTRAINT unique_lock UNIQUE(marketplace,external_id);");
	}

	public function down()
	{
		$this->dbforge->drop_table('sku_locks');
	}
};
