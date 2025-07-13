<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'sales_channel' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'default' => NULL
            )
        );

        if (!$this->dbforge->column_exists('sales_channel', 'orders')) {
            $this->dbforge->add_column('orders', $fieldUpdate);
        }
    }

	public function down()	{
        $this->dbforge->drop_column('orders', 'sales_channel');
	}
};