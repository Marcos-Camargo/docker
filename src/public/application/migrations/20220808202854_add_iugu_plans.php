<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->db->table_exists('iugu_plans')){

            ## Create Table iugu_plans
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'plan_title' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('100'),
                    'null' => FALSE,

                ),
                'plan_type' => array(
                    'type' => 'ENUM("cash","financed")',
                    'null' => FALSE,
                    'default' => 'cash',

                ),
                'plan_value' => array(
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'default' => '0',

                ),
                'plan_installments' => array(
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'default' => '0',

                ),
                'installment_value' => array(
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'default' => '0',

                ),
                'plan_status' => array(
                    'type' => 'TINYINT',
                    'constraint' => ('1'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'default' => '1',

                ),
                'user_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,

                ),
                'iugu_plan_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => TRUE,

                ),
                '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
                '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            ));
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table("iugu_plans", TRUE);
            $this->db->query('ALTER TABLE  `iugu_plans` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `iugu_plans` ADD CONSTRAINT `FK_iugu_plans_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        }
	 }

	public function down()	{
		### Drop table iugu_plans ##
		$this->dbforge->drop_table("iugu_plans", TRUE);

	}
};