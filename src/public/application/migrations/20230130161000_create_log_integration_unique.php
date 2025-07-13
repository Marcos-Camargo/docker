<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table log_integration_unique
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'title' => array(
                'type' => 'VARCHAR',
                'constraint' => ('512'),
                'null' => FALSE
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => FALSE
            ),
            'type' => array(
                'type' => 'CHAR',
                'constraint' => ('1'),
                'null' => FALSE
            ),
            'status' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'job' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'unique_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            '`date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("log_integration_unique", TRUE);

        $this->db->query('ALTER TABLE `log_integration_unique` ADD INDEX `ix_log_integration_store_id_status_type` (`store_id`, `status`, `type`);');
        $this->db->query('ALTER TABLE `log_integration_unique` ADD INDEX `ix_log_integration_table_status_store_id` (`status`, `store_id`);');
        $this->db->query('ALTER TABLE `log_integration_unique` ADD INDEX `ix_log_integration_store_id_type` (`store_id`, `status`, `type`);');
        $this->db->query('ALTER TABLE `log_integration_unique` ADD INDEX `ix_log_integration_01` (`store_id`, `company_id`, `job`, `unique_id`);');
        $this->db->query('ALTER TABLE `log_integration_unique` ADD INDEX `filter_edit_order` (`store_id`, `unique_id`, `job`, `date_updated`);');
        $this->db->query('ALTER TABLE `log_integration_unique` ADD INDEX `store_id_company_id_index` (`store_id`, `company_id`);');
        $this->db->query('ALTER TABLE `log_integration_unique` ADD INDEX `index_by_store_id_company_id_title` (`store_id`, `company_id`,`title`);');
	}

	public function down()	{
        $this->dbforge->drop_table("log_integration_unique", TRUE);
	}
};
