<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $newField = array(
            'sales_model' => array(
                'type'          => 'VARCHAR',
                'constraint'    => ('64'),
                'null'          => TRUE,
            )
        );

        if (!$this->dbforge->column_exists('sales_model', 'orders')) {
            $this->dbforge->add_column('orders', $newField);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('orders', 'sales_model');
    }
};