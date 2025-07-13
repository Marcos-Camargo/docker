<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'inventory_utilization' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null' => TRUE,
                'default' => NULL
            )
        );

        if (!$this->dbforge->column_exists('inventory_utilization', 'stores')) {
            $this->dbforge->add_column('stores', $fieldUpdate);
        }
    }

	public function down()	{
        $this->dbforge->drop_column('stores', 'inventory_utilization');
	}
};