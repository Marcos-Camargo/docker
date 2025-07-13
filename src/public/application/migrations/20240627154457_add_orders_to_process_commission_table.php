<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table orders_to_process_commission
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'status' => array(
				'type' => 'TINYINT',
				'null' => FALSE,
                'default' => 0

			),
			'order_id' => array(
				'type' => 'INT',
				'null' => FALSE,

			),
            '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("orders_to_process_commission", TRUE);
		$this->db->query('ALTER TABLE  `orders_to_process_commission` ENGINE = InnoDB');
    }

	public function down()	{
		### Drop table orders_to_process_commission ##
		$this->dbforge->drop_table("orders_to_process_commission", TRUE);
	}
};