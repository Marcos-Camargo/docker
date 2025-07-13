<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table orders_integration_history
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
                'null' => FALSE
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'ordem' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'prd_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'variant' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            'original_link' => array(
                'type' => 'TEXT',
                'null' => FALSE
            ),
            'pathProd' => array(
                'type' => 'VARCHAR',
                'constraint' => ('100'),
                'null' => FALSE
            ),
            'pathVariant' => array(
                'type' => 'VARCHAR',
                'constraint' => ('100'),
                'null' => TRUE
            ),
            'status' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'error' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("prd_image", TRUE);

        $this->db->query('ALTER TABLE `prd_image` ADD INDEX `ix_prd_variant_status` (`prd_id`, `variant`, `status`);');
	}

	public function down()	{
        $this->dbforge->drop_table("prd_image", TRUE);
	}
};
