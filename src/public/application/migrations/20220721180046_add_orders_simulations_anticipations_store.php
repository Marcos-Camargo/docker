<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table orders_simulations_anticipations_store
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('10'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'order_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,

            ),
            'simulations_anticipations_store_id' => array(
                'type' => 'INT',
                'constraint' => ('10'),
                'unsigned' => TRUE,
                'null' => FALSE,

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
            '`created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` datetime NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
		$this->dbforge->add_key("id",true);

        if ($this->db->query("SHOW TABLES LIKE 'orders_simulations_anticipations_store'")->num_rows() == 0) {
            $this->dbforge->create_table("orders_simulations_anticipations_store", TRUE);
            $this->db->query('ALTER TABLE `orders_simulations_anticipations_store` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `orders_simulations_anticipations_store` ADD CONSTRAINT `orders_simulations_anticipations_store_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
            $this->db->query('ALTER TABLE `orders_simulations_anticipations_store` ADD CONSTRAINT `orders_simulations_anticipations_store_ibfk_2` FOREIGN KEY (`simulations_anticipations_store_id`) REFERENCES `simulations_anticipations_store` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        }
	 }

	public function down()	{
		### Drop table orders_simulations_anticipations_store ##
		$this->dbforge->drop_table("orders_simulations_anticipations_store", TRUE);

	}
};