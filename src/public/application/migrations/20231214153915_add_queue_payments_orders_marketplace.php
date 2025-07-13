<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table queue_payments_orders_marketplace
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => ('1')
            ),
			'order_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'numero_marketplace' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255')
			),
			'`date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("queue_payments_orders_marketplace", TRUE);
		$this->db->query('ALTER TABLE  `queue_payments_orders_marketplace` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table queue_payments_orders_marketplace ##
		$this->dbforge->drop_table("queue_payments_orders_marketplace", TRUE);

	}
};