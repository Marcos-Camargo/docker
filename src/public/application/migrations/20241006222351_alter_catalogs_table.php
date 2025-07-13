<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->dbforge->drop_column('catalogs', 'associate_skus_between_catalogs');
	
	}

	public function down()	{
        $fieldsUpdate = array(
            'associate_skus_between_catalogs' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE
            )
        );

        foreach ($fieldsUpdate as $column => $fieldUpdate) {
            if (!$this->dbforge->column_exists($column, 'catalogs')) {
                $this->dbforge->add_column('catalogs', array($column => $fieldUpdate));
            }
        }
	}
};