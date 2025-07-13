<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldsUpdate = array(
            'price_max' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10, 2'),
                'null' => true,
                'after' => 'date_update'
            ),
            'price_min' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10, 2'),
                'null' => true,
                'after' => 'date_update'
            )
        );

        foreach ($fieldsUpdate as $column => $fieldUpdate) {
            if (!$this->dbforge->column_exists($column, 'catalogs')) {
                $this->dbforge->add_column('catalogs', array($column => $fieldUpdate));
            }
        }

        if ($this->dbforge->column_exists('associate_skus_between_catalogs', 'catalogs')) {
            $this->dbforge->drop_column('catalogs', 'associate_skus_between_catalogs');
        }
	
	}

	public function down()	{
        $this->dbforge->drop_column('price_max', 'catalogs');
        $this->dbforge->drop_column('price_min', 'catalogs');
	}
};