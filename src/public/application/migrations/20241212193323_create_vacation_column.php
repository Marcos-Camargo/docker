<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$fields = [
            'is_vacation' => [
                'type' => 'INT',
                'default' => 0,
            ],
        ];
        $this->dbforge->add_column('stores', $fields);
	}

	public function down()	{
        $this->dbforge->drop_column('stores', 'is_vacation');
        
	}
};