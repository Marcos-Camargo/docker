<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'legal_panel_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE,
                'after' => 'total_amount_canceled_mkt'
            )
        );

        if (!$this->dbforge->column_exists('legal_panel_id', 'order_items_cancel')) {
            $this->dbforge->add_column('order_items_cancel', $fieldUpdate);
        }
	
	}

	public function down()	{
        $this->dbforge->drop_column('order_items_cancel', 'legal_panel_id');
	}
};