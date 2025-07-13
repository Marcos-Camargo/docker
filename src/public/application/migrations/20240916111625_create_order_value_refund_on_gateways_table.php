<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        ## Create Table order_value_refund_on_gateways
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
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'value' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null' => FALSE
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => FALSE
            ),
            'is_product_return' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => 0
            ),
            'response_error' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            '`refunded_at` timestamp NULL ',
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("order_value_refund_on_gateways", TRUE);

	}

	public function down()	{
        $this->dbforge->drop_table("order_value_refund_on_gateways", TRUE);
	}
};
