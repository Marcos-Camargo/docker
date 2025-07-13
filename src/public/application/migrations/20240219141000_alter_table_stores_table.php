<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fields = array(
            'additional_operational_deadline' => array(
                'type' => 'INT',
                'null'  => false,
                'default' => 0
            )
        );
        if (!$this->dbforge->column_exists('additional_operational_deadline', 'stores')) {
            $this->dbforge->add_column('stores', $fields);
        }
	}

	public function down()	{
        $this->dbforge->drop_column('stores', 'additional_operational_deadline');
    }
};