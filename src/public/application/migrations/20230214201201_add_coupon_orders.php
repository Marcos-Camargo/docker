<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'coupon' => array(
                'type' => 'text',
                'null' => true,
            )
        );
        if (!$this->dbforge->column_exists('coupon', 'orders')) {
            $this->dbforge->add_column('orders', $fieldUpdate);
        }

    }

    public function down()
    {
        $this->dbforge->drop_column('orders', 'coupon');
    }
};