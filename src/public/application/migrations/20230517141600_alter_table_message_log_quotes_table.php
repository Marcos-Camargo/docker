<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'error_message' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE,
                'default' => NULL,
                'after' => 'response_slas'
            )
        );

        if (!$this->dbforge->column_exists('error_message', 'log_quotes')) {
            $this->dbforge->add_column('log_quotes', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('log_quotes', 'error_message');
    }
};