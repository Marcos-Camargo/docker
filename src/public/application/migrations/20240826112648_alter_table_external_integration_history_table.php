<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {

        $fieldUpdate = array(
            'register_id' => [
                'type' => 'VARCHAR',
                'constraint' => ('128'),
                'null' => FALSE
            ]
        );

        $this->dbforge->modify_column('external_integration_history', $fieldUpdate);
	}

	public function down()	{
        $fieldUpdate = array(
            'register_id' => [
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ]
        );

        $this->dbforge->modify_column('external_integration_history', $fieldUpdate);
	}
};