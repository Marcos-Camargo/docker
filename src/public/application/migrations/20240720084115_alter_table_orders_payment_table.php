<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fields = array(
            'payment_method_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null'  => TRUE
            )
        );

        $this->dbforge->add_column('orders_payment', $fields);
	}

	public function down()	{
        $this->dbforge->drop_column('orders_payment', 'payment_method_id');
    }
};