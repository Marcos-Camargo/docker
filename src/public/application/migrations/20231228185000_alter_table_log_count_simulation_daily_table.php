<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $fieldUpdate = array(
            'store_integration' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE,
                'default' => NULL,
                'after' => 'integration'
            )
        );

        if (!$this->dbforge->column_exists('store_integration', 'log_count_simulation_daily')) {
            $this->dbforge->add_column('log_count_simulation_daily', $fieldUpdate);
        }


        $this->db->query('ALTER TABLE `log_count_simulation_daily` CHANGE COLUMN `integration` `logistic_integration` VARCHAR(256) NULL DEFAULT NULL ;');
    }

	public function down()	{
        $this->dbforge->drop_column('log_count_simulation_daily', 'store_integration');
        $this->db->query('ALTER TABLE `log_count_simulation_daily` CHANGE COLUMN `logistic_integration` `integration` VARCHAR(256) NULL DEFAULT NULL ;');
	}
};