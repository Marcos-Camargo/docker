<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		## Create Table shipping_pricing_history
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'shipping_pricing_rules_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),
			'shipping_pricing_range' => array(
				'type' => 'TEXT',
				'null' => FALSE,
			),
			'shipping_company_ids' => array(
				'type' => 'TEXT',
				'null' => FALSE,
			),
			'action_name' => array(
                'type' => 'VARCHAR',
                'constraint' => ('30'),
                'null' => TRUE,
			),
			'`log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP'
		));
		$this->dbforge->add_key("id", true);
		$this->dbforge->create_table("shipping_pricing_history", TRUE);
		$this->db->query('ALTER TABLE `shipping_pricing_history` ENGINE = InnoDB');
	}

	public function down()	{
		### Drop table shipping_pricing_history ##
		$this->dbforge->drop_table("shipping_pricing_history", TRUE);
	}
};
