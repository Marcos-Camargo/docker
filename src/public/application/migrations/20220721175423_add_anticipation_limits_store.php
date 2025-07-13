<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->db->table_exists('anticipation_limits_store')){

            ## Create Table anticipation_limits_store
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
                    'null' => FALSE,

                ),
                'payment_date' => array(
                    'type' => 'DATE',
                    'null' => TRUE,

                ),
                'maximum_amount' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'maximum_anticipation_fee' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'maximum_fee' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'minimum_amount' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'minimum_anticipation_fee' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'minimum_fee' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
                '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            ));
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table("anticipation_limits_store", TRUE);
            $this->db->query('ALTER TABLE  `anticipation_limits_store` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `anticipation_limits_store` ADD CONSTRAINT `anticipation_limits_store_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        }

	 }

	public function down()	{
		### Drop table anticipation_limits_store ##
		$this->dbforge->drop_table("anticipation_limits_store", TRUE);

	}
};