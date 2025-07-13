<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		## Create Table shipping_pricing_rules
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'table_shipping_ids' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),
			'shipping_companies' => array(
				'type' => 'TEXT',
				'null' => FALSE,
			),
			'mkt_channels_ids' => array(
				'type' => 'TEXT',
				'null' => FALSE,
			),
			'mkt_channels' => array(
				'type' => 'TEXT',
				'null' => FALSE,
			),
			'price_range' => array(
				'type' => 'TEXT',
				'null' => FALSE,
			),
			'active' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,
			),
			'`date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'`date_enabled` timestamp NOT NULL DEFAULT "0000-00-00 00:00:00"',
			'`date_disabled` timestamp NOT NULL DEFAULT "0000-00-00 00:00:00"',
			'`date_updated` timestamp NOT NULL DEFAULT "0000-00-00 00:00:00"'
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("shipping_pricing_rules", TRUE);
		$this->db->query('ALTER TABLE `shipping_pricing_rules` ENGINE = InnoDB');
	}

	public function down()	{
		### Drop table shipping_pricing_rules ##
		$this->dbforge->drop_table("shipping_pricing_rules", TRUE);
	}
};
