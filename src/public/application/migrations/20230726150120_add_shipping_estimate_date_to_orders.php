<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdates = array(
            'shipping_estimate_date' => array(
                'type' => 'datetime',
                'null' => true,
                'default' => null
            )
        );

        if (!$this->dbforge->column_exists('shipping_estimate_date', 'orders')) {
            $this->dbforge->add_column('orders', $fieldUpdates);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('orders', 'shipping_estimate_date');
    }
};