<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $this->dbforge->modify_column('log_integration_order_marketplace', [
            'received' => [
                'type' => 'LONGTEXT',
                'null' => TRUE,
            ]
        ]);
    }

    public function down()
    {
        $this->dbforge->modify_column('log_integration_order_marketplace', [
            'received' => [
                'type' => 'TEXT',
                'null' => TRUE,
            ]
        ]);
    }
};
