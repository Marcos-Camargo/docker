<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$fields = [
            'start_vacation' => [
                'type' => 'DATETIME',
                'null' => TRUE,
                'comment' => 'Data e hora de início das férias'
            ],
            'end_vacation' => [
                'type' => 'DATETIME',
                'null' => TRUE,
                'comment' => 'Data e hora de término das férias'
            ],
        ];

		$this->dbforge->add_column('stores', $fields);
	}

	public function down()	{
		$this->dbforge->drop_column('stores', 'start_vacation');
        $this->dbforge->drop_column('stores', 'end_vacation');
		
	}
};