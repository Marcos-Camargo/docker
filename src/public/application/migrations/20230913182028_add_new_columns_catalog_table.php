<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        if (!$this->dbforge->column_exists('attribute_id', 'catalogs')){
            $fields = array(
                'attribute_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,
                    'default' => null
                )
            );
            $this->dbforge->add_column("catalogs", $fields);
        }
        if (!$this->dbforge->column_exists('attribute_value', 'catalogs')){
            $fields = array(
                'attribute_value' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,
                    'default' => null
                )
            );
            $this->dbforge->add_column("catalogs", $fields);
        }

    }

    public function down()	{
        $this->dbforge->drop_column("catalogs", 'attribute_id');
        $this->dbforge->drop_column("catalogs", 'attribute_value');
    }
};