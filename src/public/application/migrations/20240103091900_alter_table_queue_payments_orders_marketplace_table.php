<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fields = array(
            'attempts' => array(
                'type' => 'INT',
                'default' => 0
            )
        );
        $this->dbforge->add_column('queue_payments_orders_marketplace', $fields);
	}

	public function down()	{
        $this->dbforge->drop_column('queue_payments_orders_marketplace', 'attempts');
    }
};