<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if(!$this->db->table_exists('integration_erps')) {

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
                    'type' => 'TINYINT',
                    'constraint' => ('1'),
                    'null' => FALSE,

                ),
                'hash' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('512'),
                    'null' => FALSE,

                ),
                'active' => array(
                    'type' => 'TINYINT',
                    'constraint' => ('1'),
                    'null' => FALSE,

                ),
                'support_link' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,

                ),
                'image' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('32'),
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
                '`date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            ));
            $this->dbforge->add_key("id", true);
            $this->dbforge->create_table("integration_erps", TRUE);
            $this->db->query('ALTER TABLE `integration_erps` ADD CONSTRAINT `integration_erps_user_created_foreign` FOREIGN KEY (`user_created`) REFERENCES `users` (`id`)');
            $this->db->query('ALTER TABLE `integration_erps` ADD CONSTRAINT `integration_erps_user_updated_foreign` FOREIGN KEY (`user_updated`) REFERENCES `users` (`id`)');
            $this->db->query('ALTER TABLE `integration_erps` ENGINE = InnoDB');
            $this->db->query("INSERT INTO integration_erps (name,`type`,hash,active,support_link,image) VALUES
        ('Precode',2,'46D19378-E142-7A2C-06D5-04CFC143EBEF',1,NULL,'precode.png'),
        ('Aton',2,'598A8984-C674-38D7-184E-ACE20C66766F',1,NULL,'aton.png'),
        ('Hubsell',2,'9C14C249-CDC8-BC60-22F9-0D09430E4BFF',1,NULL,'hubsell.png');");

        }

	}

	public function down()	{
		### Drop table integration_erps ##
		$this->dbforge->drop_table("integration_erps", TRUE);

	}
};