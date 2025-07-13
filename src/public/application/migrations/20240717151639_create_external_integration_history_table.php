<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table external_integration_history
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'register_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'external_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('512'),
                'null' => TRUE
            ),
            'type' => array(
                'type' => 'VARCHAR',
                'constraint' => ('64'),
                'null' => FALSE
            ),
            'method' => array(
                'type' => 'VARCHAR',
                'constraint' => ('64'),
                'null' => FALSE
            ),
            'uri' => array(
                'type' => 'VARCHAR',
                'constraint' => ('128'),
                'null' => TRUE
            ),
            'request' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'response' => array(
                'type' => 'TEXT',
                'null' => FALSE
            ),
            'response_webhook' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'status_webhook' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => TRUE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("external_integration_history", TRUE);

        $this->db->query('ALTER TABLE `external_integration_history` ADD INDEX `index_external_integration_history_external_id` (`external_id`);');
        $this->db->query('ALTER TABLE `external_integration_history` ADD INDEX `index_external_integration_history_type_method` (`type`, `method`);');
        $this->db->query('ALTER TABLE `external_integration_history` ADD INDEX `index_external_integration_history_register_id_type_method` (`register_id`, `type`, `method`);');
	}

	public function down()	{
        $this->db->query('ALTER TABLE `external_integration_history` DROP INDEX `index_external_integration_history_external_id`;');
        $this->db->query('ALTER TABLE `external_integration_history` DROP INDEX `index_external_integration_history_type_method`;');
        $this->db->query('ALTER TABLE `external_integration_history` DROP INDEX `index_external_integration_history_register_id_type_method`;');
        $this->dbforge->drop_table("external_integration_history", TRUE);
	}
};
