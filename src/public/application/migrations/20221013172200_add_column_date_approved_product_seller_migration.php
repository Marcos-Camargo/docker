<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
            'date_approved' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'default' => NULL,
            )
        );

        if (!$this->dbforge->column_exists('date_approved', 'products_seller_migration'))
        {
            $this->dbforge->add_column('products_seller_migration', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('products_seller_migration', 'date_approved');
	}
};