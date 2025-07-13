<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table change_seller_histories
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'order_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'old_store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'new_store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'old_company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'new_company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'updated_data' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            '`date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("change_seller_histories", TRUE);
	}

	public function down()	{
        $this->dbforge->drop_table("change_seller_histories", TRUE);
	}
};
