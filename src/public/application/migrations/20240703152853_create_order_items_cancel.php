<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table order_items_cancel
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'order_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
				'null' => FALSE
			),
			'item_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
			),
			'qty' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE
			),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'
		));

		$this->dbforge->add_key("id",true);
        $this->dbforge->create_table("order_items_cancel", TRUE);

        $this->db->query('CREATE INDEX idx_order_items_cancel_order_id ON order_items_cancel (`order_id`);');
        $this->db->query('CREATE INDEX idx_order_items_cancel_order_id_item_id ON order_items_cancel (`order_id`,`item_id`);');
	}

	public function down()	{
		### Drop table order_items_cancel ##
        $this->db->query('DROP INDEX idx_order_items_cancel_order_id ON order_items_cancel');
        $this->db->query('DROP INDEX idx_order_items_cancel_order_id_item_id ON order_items_cancel');
		$this->dbforge->drop_table("order_items_cancel", TRUE);

	}
};