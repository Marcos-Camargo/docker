<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->db->table_exists('simulations_anticipations_store')){

            ## Create Table simulations_anticipations_store
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
                'anticipation_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('50'),
                    'null' => TRUE,

                ),
                'amount' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'anticipation_fee' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'fee' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('10,2'),
                    'null' => TRUE,

                ),
                'payment_date' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,

                ),
                'anticipation_status' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('10'),
                    'null' => TRUE,
                    'default' => 'pending',

                ),
                'timeframe' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('5'),
                    'null' => TRUE,

                ),
                'type' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('10'),
                    'null' => TRUE,

                ),
                'user_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,

                ),
                '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
                '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            ));
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table("simulations_anticipations_store", TRUE);
            $this->db->query('ALTER TABLE  `simulations_anticipations_store` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `simulations_anticipations_store` ADD CONSTRAINT `simulations_anticipations_store_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

        }

	 }

	public function down()	{
		### Drop table simulations_anticipations_store ##
		$this->dbforge->drop_table("simulations_anticipations_store", TRUE);

	}
};