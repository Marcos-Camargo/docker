<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table control_sync_skuseller_skumkt
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
				'null' => FALSE
			),
			'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
			),
			'skuseller' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE
			),
			'skumkt' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
				'null' => FALSE
			),
			'int_to' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
				'null' => FALSE
			),
            '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'
		));

		$this->dbforge->add_key("id",true);
        $this->dbforge->create_table("control_sync_skuseller_skumkt", TRUE);

        $this->db->query('CREATE INDEX idx_control_sync_skuseller_skumkt_store_skuseller_intto ON control_sync_skuseller_skumkt (`store_id`,`skuseller`,`int_to`);');
	}

	public function down()	{
		### Drop table control_sync_skuseller_skumkt ##
        $this->db->query('DROP INDEX idx_control_sync_skuseller_skumkt_store_skuseller_intto ON control_sync_skuseller_skumkt');
		$this->dbforge->drop_table("control_sync_skuseller_skumkt", TRUE);

	}
};