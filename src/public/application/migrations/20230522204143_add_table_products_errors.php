<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {

        ## Create Table orders_integration_history
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'product_id' => array(
                'type' => 'INT',
                'null' => FALSE
            ),
            'error_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'comment' => array(
                'type' => 'VARCHAR',
                'constraint' => ('100'),
                'null' => FALSE
            ),
            'int_to' => array(
                'type' => 'VARCHAR',
                'constraint' => ('25'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("products_errors", TRUE);
    }

    public function down()	{
        $this->dbforge->drop_table("products_errors", TRUE);
    }
};