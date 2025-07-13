<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $fieldUpdateRequestUri = array(
            'server_batch_ip' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null' => TRUE
            )
        );

        if (!$this->dbforge->column_exists('server_batch_ip', 'queues_control')) {
            $this->dbforge->add_column('queues_control', $fieldUpdateRequestUri);
        }
    }

    public function down()	{
        $this->dbforge->drop_column('queues_control', 'server_batch_ip');
    }
};