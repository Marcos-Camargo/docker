<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table webhook_notification_queue
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'BIGINT',
				'constraint' => ('20'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'company_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'integration_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'origin' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'topic' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'scope_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'data' => array(
				'type' => 'LONGBLOB',
				'null' => TRUE,

			),
			'status' => array(
				'type' => 'TINYINT',
				'constraint' => ('2'),
				'null' => FALSE,
				'default' => '0',

			),
            '`created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` datetime NULL DEFAULT CURRENT_TIMESTAMP '
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("webhook_notification_queue", TRUE);
		$this->db->query('ALTER TABLE  `webhook_notification_queue` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table webhook_notification_queue ##
		$this->dbforge->drop_table("webhook_notification_queue", TRUE);

	}
};