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
                'auto_increment' => FALSE
            ),
            'order_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'type' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null' => FALSE
            ),
            'request' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'response' => array(
                'type' => 'TEXT',
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("orders_integration_history", TRUE);

        $this->db->query('ALTER TABLE `orders_integration_history` ADD CONSTRAINT `FK_orders_integration_history_orders_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)');
        $this->db->query('ALTER TABLE `orders_integration_history` ADD INDEX `index_orders_integration_history_order_id_type` (`order_id`, `type`);');
	}

	public function down()	{
        $this->dbforge->drop_table("orders_integration_history", TRUE);
	}
};
