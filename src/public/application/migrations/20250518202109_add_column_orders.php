<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if (!$this->dbforge->column_exists('order_mkt_multiseller', 'orders')){
            ## Create column orders
            $fields = array(
                'order_mkt_multiseller' => array(
                    'type' => 'varchar',
                    'constraint' => ('64'),
                    'null' => TRUE
                )
            );
            $this->dbforge->add_column("orders",$fields);
        }
    }

    public function down()	{
        ### Drop column orders
        $this->dbforge->drop_column("orders", 'order_mkt_multiseller');

    }
};