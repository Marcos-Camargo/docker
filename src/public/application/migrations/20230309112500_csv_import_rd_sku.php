<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		## Create Table csv_import_rd_sku
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'path' => array(
				'type' => 'VARCHAR',
                'constraint' => ('255'),
				'null' => FALSE,
			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),
			'checked' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,
				'default' => 0
			),
			'name_original' => array(
				'type' => 'VARCHAR',
                'constraint' => ('255'),
				'null' => FALSE,
			),
			'sent_email' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,
				'default' => 0
			),
			'valid' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,
				'default' => 1
			),
			'email' => array(
				'type' => 'VARCHAR',
                'constraint' => ('255'),
				'null' => TRUE,
			),
			'`date_create` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
			
		));
		$this->dbforge->add_key("id", true);
		$this->dbforge->create_table("csv_import_rd_sku", TRUE);
		$this->db->query('ALTER TABLE `csv_import_rd_sku` ENGINE = InnoDB');
	}

	public function down()	{
		### Drop table shipping_pricing_history ##
		$this->dbforge->drop_table("csv_import_rd_sku", TRUE);
	}
};
