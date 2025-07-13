<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'is_pickup_in_point' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => '0'
            )
        );

        if (!$this->dbforge->column_exists('is_pickup_in_point', 'orders')) {
            $this->dbforge->add_column('orders', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('orders', 'is_pickup_in_point');
    }
};