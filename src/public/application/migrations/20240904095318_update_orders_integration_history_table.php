<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $fieldUpdateResponseCode = array(
            'response_code' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'default' => NULL,
                'after' => 'response'
            )
        );
        $fieldUpdateRequestMethod = array(
            'request_method' => array(
                'type' => 'VARCHAR',
                'constraint' => ('5'),
                'null' => TRUE,
                'default' => NULL,
                'after' => 'request'
            )
        );

        if (!$this->dbforge->column_exists('response_code', 'orders_integration_history')) {
            $this->dbforge->add_column('orders_integration_history', $fieldUpdateResponseCode);
        }

        if (!$this->dbforge->column_exists('request_method', 'orders_integration_history')) {
            $this->dbforge->add_column('orders_integration_history', $fieldUpdateRequestMethod);
        }
    }

    public function down()	{
        $this->dbforge->drop_column('orders_integration_history', 'response_code');
        $this->dbforge->drop_column('orders_integration_history', 'request_method');
    }
};
