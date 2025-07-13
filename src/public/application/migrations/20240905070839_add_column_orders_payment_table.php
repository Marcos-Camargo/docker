<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $fieldUpdateNsu = array(
            'nsu' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => TRUE,
                'default' => NULL
            ),
        );
        $fieldUpdateDatewayTid = array(
            'gateway_tid' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => TRUE,
                'default' => NULL
            ),
        );

        if (!$this->dbforge->column_exists('nsu', 'orders_payment')) {
            $this->dbforge->add_column('orders_payment', $fieldUpdateNsu);
        }

        if (!$this->dbforge->column_exists('gateway_tid', 'orders_payment')) {
            $this->dbforge->add_column('orders_payment', $fieldUpdateDatewayTid);
        }
    }

    public function down()
    {
        if ($this->dbforge->column_exists('nsu', 'orders_payment')) {
            $this->dbforge->drop_column('orders_payment', 'nsu');
        }
        if ($this->dbforge->column_exists('gateway_tid', 'orders_payment')) {
            $this->dbforge->drop_column('orders_payment', 'gateway_tid');
        }
    }
};
