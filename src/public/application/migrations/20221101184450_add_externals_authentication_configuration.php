<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if(!$this->db->table_exists('externals_authentication_configuration')) {

            ## Create Table integration_erps
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'external_authentication_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                ),
                'name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'value' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => true,

                ),
                
                'user_created' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,

                ),
                'user_updated' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,

                ),
                '`date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
                '`date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'
            ));
            $this->dbforge->add_key("id", true);
            $this->dbforge->create_table("externals_authentication_configuration", TRUE);
            $this->db->query('ALTER TABLE `externals_authentication_configuration` ADD CONSTRAINT `externals_authentication_configuration_external_auth_id` FOREIGN KEY (`external_authentication_id`) REFERENCES `externals_authentication` (`id`)');
            $this->db->query('ALTER TABLE `externals_authentication_configuration` ADD CONSTRAINT `externals_authentication_configuration_user_created_foreign` FOREIGN KEY (`user_created`) REFERENCES `users` (`id`)');
            $this->db->query('ALTER TABLE `externals_authentication_configuration` ADD CONSTRAINT `externals_authentication_configuration_user_updated_foreign` FOREIGN KEY (`user_updated`) REFERENCES `users` (`id`)');
            $this->db->query('ALTER TABLE `externals_authentication_configuration` ENGINE = InnoDB');

            $sql = "CREATE INDEX index_by_auth_id_name ON externals_authentication_configuration(external_authentication_id, name)";
            $this->db->query($sql);
        }

	}

	public function down()	{
		### Drop table integration_erps ##
		$this->dbforge->drop_table("externals_authentication_configuration", TRUE);

	}
};