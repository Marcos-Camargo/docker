<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		## Create Table integration_logistic_api_parameters
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'integration_logistic_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),
			'type' => array(  // json or headers
				'type' => 'VARCHAR',
                'constraint' => ('10'),
				'null' => false,
                'comment' => 'json or headers'
			),
			'key' => array(
				'type' => 'VARCHAR',
                'constraint' => ('256'),
				'null' => false,
			),
			'value' => array(
				'type' => 'VARCHAR',
                'constraint' => ('256'),
				'null' => false,
			),			
			'`date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'`date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("integration_logistic_api_parameters", TRUE);
		$this->db->query('ALTER TABLE `integration_logistic_api_parameters` ENGINE = InnoDB');
		$this->db->query('ALTER TABLE `integration_logistic_api_parameters` ADD INDEX `ix_integration_logistic_id` (`integration_logistic_id`);');
     
	}

	public function down()	{
		### Drop table shipping_pricing_rules ##
		$this->dbforge->drop_table("integration_logistic_api_parameters", TRUE);
	}
};
