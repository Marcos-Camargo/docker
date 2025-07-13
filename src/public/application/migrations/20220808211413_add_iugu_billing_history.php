<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->db->table_exists('iugu_billing_history')) {

            ## Create Table iugu_billing_history
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
                    'null' => TRUE,

                ),
                'plan_id' => array(
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => true,
                ),
                'invoice_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => FALSE,

                ),
                'subscription_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => FALSE,

                ),
                'amount' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => TRUE,
                    'null' => FALSE,

                ),
                'installment' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => TRUE,
                    'null' => TRUE,

                ),
                'installments' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => TRUE,
                    'null' => TRUE,

                ),
                'bill_date' => array(
                    'type' => 'TIMESTAMP',
                    'null' => TRUE,

                ),
                'paid_at' => array(
                    'type' => 'TIMESTAMP',
                    'null' => TRUE,

                ),
                'payment_method' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => TRUE,

                ),
                'payer_cpf_cnpj' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => TRUE,

                ),
                'pix_end_to_end_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('250'),
                    'null' => TRUE,

                ),
                'status' => array(
                    'type' => 'ENUM("success","fail")',
                    'null' => FALSE,
                    'default' => 'fail',

                ),
                'iugu_status' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => TRUE,

                ),
                'paid_cents' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => TRUE,
                    'null' => TRUE,

                ),
                'event' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => TRUE,

                ),
                '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
                '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            ));
            $this->dbforge->add_key("id", true);
            $this->dbforge->create_table("iugu_billing_history", TRUE);
            $this->db->query('ALTER TABLE  `iugu_billing_history` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `iugu_billing_history` ADD CONSTRAINT `FK_iugu_billing_history_plan_id` FOREIGN KEY (`plan_id`) REFERENCES `iugu_plans` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
            $this->db->query('ALTER TABLE `iugu_billing_history` ADD CONSTRAINT `FK_iugu_billing_history_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

        }

	 }

	public function down()	{
		### Drop table iugu_billing_history ##
		$this->dbforge->drop_table("iugu_billing_history", TRUE);

	}
};