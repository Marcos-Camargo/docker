<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $fieldUpdateRequestUri = array(
            'request_uri' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE,
                'default' => NULL,
                'after' => 'request_method'
            )
        );

        if (!$this->dbforge->column_exists('request_uri', 'orders_integration_history')) {
            $this->dbforge->add_column('orders_integration_history', $fieldUpdateRequestUri);
        }
    }

    public function down()	{
        $this->dbforge->drop_column('orders_integration_history', 'request_uri');
    }
};
