<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldsUpdate = array(
            'int_to' => array(
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

	public function down()	{
        $this->dbforge->drop_column('catalogs', 'int_to');
	}
};