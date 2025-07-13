<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate_variant = array(
            'variant' => array(
                'type' => 'int',
                'null' => true,
                'after' => 'product_id'
            )
        );

        $fieldUpdate_returned_shipping = array(
            'returned_shipping' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null' => true,
                'after' => 'variant'
            )
        );

        if (!$this->dbforge->column_exists('variant', 'product_return')) {
            $this->dbforge->add_column('product_return', $fieldUpdate_variant);
        }

        if (!$this->dbforge->column_exists('returned_shipping', 'product_return')) {
            $this->dbforge->add_column('product_return', $fieldUpdate_returned_shipping);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('product_return', 'variant');
        $this->dbforge->drop_column('product_return', 'returned_shipping');
    }
};