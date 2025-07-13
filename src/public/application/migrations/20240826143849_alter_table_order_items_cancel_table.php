<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'total_amount_canceled_mkt' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null' => TRUE,
                'after' => 'user_id'
            )
        );

        if (!$this->dbforge->column_exists('total_amount_canceled_mkt', 'order_items_cancel')) {
            $this->dbforge->add_column('order_items_cancel', $fieldUpdate);
        }
	
	}

	public function down()	{
        $this->dbforge->drop_column('order_items_cancel', 'total_amount_canceled_mkt');
	}
};