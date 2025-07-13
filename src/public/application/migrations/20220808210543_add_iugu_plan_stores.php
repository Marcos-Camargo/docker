<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->db->table_exists('iugu_plan_stores')){

            ## Create Table iugu_plan_stores
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'plan_id' => array(
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                ),
                'store_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => FALSE,
                    'null' => FALSE,
                ),
                'active' => array(
                    'type' => 'TINYINT',
                    'constraint' => ('3'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'default' => '2',

                ),
                'date_plan_start' => array(
                    'type' => 'DATE',
                    'null' => FALSE,

                ),
                'subscription_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => TRUE,

                ),
                '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
                '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            ));
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table("iugu_plan_stores", TRUE);
            $this->db->query('ALTER TABLE  `iugu_plan_stores` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `iugu_plan_stores` ADD CONSTRAINT `FK_iugu_plan_stores_plan_id` FOREIGN KEY (`plan_id`) REFERENCES `iugu_plans` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
            $this->db->query('ALTER TABLE `iugu_plan_stores` ADD CONSTRAINT `FK_iugu_plan_stores_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

        }
	 }

	public function down()	{
		### Drop table iugu_plan_stores ##
		$this->dbforge->drop_table("iugu_plan_stores", TRUE);

	}
};