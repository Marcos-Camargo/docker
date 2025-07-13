<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_driver $db
 */

return new class extends CI_Migration
{

	public function up() {
        $fieldOrder = array(
            'multi_channel_fulfillment_order_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            )
        );

        if (!$this->dbforge->column_exists('multi_channel_fulfillment_order_id', 'orders')){
            $this->dbforge->add_column('orders', $fieldOrder);
        }
	}

	public function down()	{
		### Drop column orders.multi_channel_fulfillment_order_id ##
        $this->dbforge->drop_column("orders", 'multi_channel_fulfillment_order_id');
	}
};