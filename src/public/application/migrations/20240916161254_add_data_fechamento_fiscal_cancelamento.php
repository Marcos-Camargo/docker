<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'data_fechamento_fiscal_cancelamento' => array(
                'type' => 'datetime',
                'null' => TRUE
            )
        );

        if (!$this->dbforge->column_exists('data_fechamento_fiscal_cancelamento', 'orders_payment_date')) {
            $this->dbforge->add_column('orders_payment_date', $fieldUpdate);
        }
	
	}

	public function down()	{
        $this->dbforge->drop_column('orders_payment_date', 'data_fechamento_fiscal_cancelamento');
	}
};