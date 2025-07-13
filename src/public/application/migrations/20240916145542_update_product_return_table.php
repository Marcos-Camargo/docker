<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $fieldProductValueReturned = array(
            'product_value_returned' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null' => TRUE,
                'after' => 'returned_at'
            )
        );
        $fieldShippingValueReturned = array(
            'shipping_value_returned' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null' => TRUE,
                'after' => 'returned_at'
            )
        );

        if (!$this->dbforge->column_exists('product_value_returned', 'product_return')) {
            $this->dbforge->add_column('product_return', $fieldProductValueReturned);
        }

        if (!$this->dbforge->column_exists('shipping_value_returned', 'product_return')) {
            $this->dbforge->add_column('product_return', $fieldShippingValueReturned);
        }
    }

    public function down()	{
        $this->dbforge->drop_column('product_return', 'product_value_returned');
        $this->dbforge->drop_column('product_return', 'shipping_value_returned');
    }
};
