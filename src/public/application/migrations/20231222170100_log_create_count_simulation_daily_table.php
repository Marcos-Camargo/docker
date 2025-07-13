<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table log_count_simulation_daily
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'sellercenter' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => FALSE
            ),
            'int_to' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => FALSE
            ),
            'skumkt' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => FALSE
            ),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'integration' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'seller_integration' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE
            ),
            'date' => array(
                'type' => 'date',
                'null' => FALSE
            ),
            'count_request' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("log_count_simulation_daily", TRUE);

//        $this->db->query('CREATE INDEX ix_log_count_simulation_daily_01 ON log_count_simulation_daily (int_to,skumkt,store_id,date);');

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'SaveCountSimulationDaily')) {
            $this->db->insert('calendar_events', array(
                'title'         => "Salva a quantidade de consultas de disponobilidades feitas pelo marketplace durante o dia.",
                'event_type'    => '71',
                'start'         => '2023-12-22 23:30:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'SaveCountSimulationDaily',
                'module_method' => 'run',
                'params'        => 'null'
            ));
        }
	 }

	public function down()	{
        $this->dbforge->drop_table("log_count_simulation_daily", TRUE);
	}
};