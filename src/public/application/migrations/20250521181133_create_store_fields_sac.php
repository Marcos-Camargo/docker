<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		$fields = [
			'responsible_sac_email' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
				'null'       => TRUE,
			],
			'responsible_sac_name' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
				'null'       => TRUE,
			],
			'responsible_sac_tell' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
				'null'       => TRUE,
			]
		];

		$this->dbforge->add_column('stores', $fields);
	}

	public function down()
	{
		$this->dbforge->drop_column('stores', 'responsible_sac_email');
		$this->dbforge->drop_column('stores', 'responsible_sac_name');
		$this->dbforge->drop_column('stores', 'responsible_sac_tell');
	}
};
