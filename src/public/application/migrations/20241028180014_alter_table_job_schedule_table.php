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

        if (!$this->dbforge->column_exists('server_batch_ip', 'job_schedule')) {
            $this->dbforge->add_column('job_schedule', $fieldUpdateRequestUri);
        }
    }

    public function down()	{
        $this->dbforge->drop_column('job_schedule', 'server_batch_ip');
    }
};