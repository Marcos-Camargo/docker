<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		$fields = [
			'aggregate_id' => [
				'type' => 'INT',
				'unsigned' => TRUE,
			],
			'inscricao_municipal' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE
			],
			'website_url' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE
			]
		];

		$this->dbforge->add_column('stores', $fields);
	}

	public function down()
	{
		$this->dbforge->drop_column('stores', 'aggregate_id');
		$this->dbforge->drop_column('stores', 'inscricao_municipal');
		$this->dbforge->drop_column('stores', 'website_url');
	}
};
