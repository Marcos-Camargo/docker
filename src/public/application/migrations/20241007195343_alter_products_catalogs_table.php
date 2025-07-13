<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldsUpdate = array(
            'maximum_discount_catalog' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10, 2'),
                'null' => true,
                'after' => 'product_id'
            )
        );

        foreach ($fieldsUpdate as $column => $fieldUpdate) {
            if (!$this->dbforge->column_exists($column, 'products_catalog_associated')) {
                $this->dbforge->add_column('products_catalog_associated', array($column => $fieldUpdate));
            }
        }
	
	}

	public function down()	{
        $this->dbforge->drop_column('products_catalog_associated', 'maximum_discount_catalog');
	}
};