<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if(!$this->db->table_exists('externals_authentication')) {

            ## Create Table integration_erps
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'type' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'active' => array(
                    'type' => 'TINYINT',
                    'constraint' => ('1'),
                    'null' => FALSE,

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
            $this->dbforge->create_table("externals_authentication", TRUE);
            $this->db->query('ALTER TABLE `externals_authentication` ADD CONSTRAINT `externals_authentication_user_created_foreign` FOREIGN KEY (`user_created`) REFERENCES `users` (`id`)');
            $this->db->query('ALTER TABLE `externals_authentication` ADD CONSTRAINT `externals_authentication_user_updated_foreign` FOREIGN KEY (`user_updated`) REFERENCES `users` (`id`)');
            $this->db->query('ALTER TABLE `externals_authentication` ENGINE = InnoDB');

            $sql = "CREATE INDEX index_by_name ON externals_authentication(name)";
            $this->db->query($sql);
        }

	}

	public function down()	{
		### Drop table integration_erps ##
		$this->dbforge->drop_table("externals_authentication", TRUE);

	}
};