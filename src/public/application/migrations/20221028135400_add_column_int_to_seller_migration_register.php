<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
            'int_to' => array(
                'type' => 'TEXT',
                'default' => NULL,
            ),
            'finish_date' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'default' => NULL,
            )
        );

        if (!$this->dbforge->column_exists('finish_date', 'seller_migration_register'))
        {
            $this->dbforge->add_column('seller_migration_register', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('seller_migration_register', 'int_to');
	}
};