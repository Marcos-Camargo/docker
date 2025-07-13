<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
            'end_date' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'default' => NULL,
                'after' => 'import_start_date'
            )
        );

        if (!$this->dbforge->column_exists('end_date', 'seller_migration_register'))
        {
            $this->dbforge->add_column('seller_migration_register', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('seller_migration_register', 'end_date');
	}
};