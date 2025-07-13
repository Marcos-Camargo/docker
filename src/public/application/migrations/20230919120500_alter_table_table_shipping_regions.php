<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdates = array(
            'migrated_data' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => FALSE
            ),
            'migrated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'default' => NULL
            )
        );

        if (!$this->dbforge->column_exists('migrated_data', 'table_shipping_regions')) {
            $this->dbforge->add_column('table_shipping_regions', $fieldUpdates);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('table_shipping_regions', 'migrated_data');
    }
};