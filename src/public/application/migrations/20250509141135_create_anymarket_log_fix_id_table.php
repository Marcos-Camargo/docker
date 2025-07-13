<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->dbforge->drop_table('anymarket_log_fix_id', true);

        ## Create Table pickup_points
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'prd_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'sku' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'sku_pai' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE
            ),
            'id_any_integration' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE
            ),
            'id_any_sellercenter' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE
            ),
            'variant' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE
            ),
            'error' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("anymarket_log_fix_id", TRUE);
	}

	public function down()	{
        $this->dbforge->drop_table("anymarket_log_fix_id", TRUE);
	}
};
