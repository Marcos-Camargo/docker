<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
            'user_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => TRUE,
                'default' => NULL,
            )
        );

        if (!$this->dbforge->column_exists('user_id', 'products_seller_migration'))
        {
            $this->dbforge->add_column('products_seller_migration', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('products_seller_migration', 'user_id');
	}
};