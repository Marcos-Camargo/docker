<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if (!$this->dbforge->column_exists('copied', 'anymarket_log_fix_id')){
            ## Create column anymarket_log_fix_id
            $fields = array(
                'copied' => array(
                    'type' => 'INT',
                    'constraint' => ('1'),
                    'null' => false,
                    'default' => 0,
                    'after' => 'store_id'
                )
            );
            $this->dbforge->add_column("anymarket_log_fix_id", $fields);
        }

        if (!$this->dbforge->column_exists('existing', 'anymarket_log_fix_id')){
            ## Create column anymarket_log_fix_id
            $fields = array(
                'existing' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,
                    'after' => 'store_id'
                )
            );
            $this->dbforge->add_column("anymarket_log_fix_id", $fields);
        }

        if (!$this->dbforge->column_exists('copied_error', 'anymarket_log_fix_id')){
            ## Create column anymarket_log_fix_id
            $fields = array(
                'copied_error' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                    'after' => 'store_id'
                )
            );
            $this->dbforge->add_column("anymarket_log_fix_id", $fields);
        }
    }

    public function down()	{
        ### Drop column anymarket_log_fix_id
        $this->dbforge->drop_column("anymarket_log_fix_id", 'existing');
        $this->dbforge->drop_column("anymarket_log_fix_id", 'copied');
        $this->dbforge->drop_column("anymarket_log_fix_id", 'copied_error');
    }
};