<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'request_data' => array(
                'type' => 'text',
                'null' => true,
                'after' => 'result_message'
            )
        );
        if (!$this->dbforge->column_exists('request_data', 'gateway_transfers')) {
            $this->dbforge->add_column('gateway_transfers', $fieldUpdate);
        }

        $fieldUpdate = array(
            'response_data' => array(
                'type' => 'text',
                'null' => true,
                'after' => 'request_data'
            )
        );
        if (!$this->dbforge->column_exists('response_data', 'gateway_transfers')) {
            $this->dbforge->add_column('gateway_transfers', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('gateway_transfers', 'request_data');
        $this->dbforge->drop_column('gateway_transfers', 'response_data');
    }
};