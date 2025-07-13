<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table integrations_settings
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'tradesPolicies' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'adlink' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => FALSE,

			),
			'auto_approve' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,

			),
			'update_product_specifications' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,

			),
			'update_sku_specifications' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,

			),
			'update_sku_vtex' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,

			),
			'update_product_vtex' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,

			),
			'integration_id' => array(
				'type' => 'INT',
				'null' => FALSE,

			),
			'status' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("integrations_settings", TRUE);
		$this->db->query('ALTER TABLE  `integrations_settings` ENGINE = InnoDB');
		$this->db->query('ALTER TABLE `integrations_settings` ADD CONSTRAINT `integrations_settings_FK` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
	 }

	public function down()	{
		### Drop table integrations_settings ##
		$this->dbforge->drop_table("integrations_settings", TRUE);

	}
};