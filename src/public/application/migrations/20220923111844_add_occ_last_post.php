<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table occ_last_post
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'int_to' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'prd_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'variant' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'company_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'EAN' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => FALSE,

			),
			'price' => array(
				'type' => 'DECIMAL',
				'constraint' => ('15,2'),
				'null' => FALSE,

			),
			'list_price' => array(
				'type' => 'DECIMAL',
				'constraint' => ('15,2'),
				'null' => FALSE,

			),
			'qty' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'qty_total' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'sku' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'skumkt' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'skulocal' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'date_last_sent' => array(
				'type' => 'TIMESTAMP',
				'null' => TRUE,

			),
			'tipo_volume_codigo' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,
				'default' => '999',

			),
			'width' => array(
				'type' => 'DECIMAL',
				'constraint' => ('10,3'),
				'null' => FALSE,

			),
			'height' => array(
				'type' => 'DECIMAL',
				'constraint' => ('10,3'),
				'null' => FALSE,

			),
			'length' => array(
				'type' => 'DECIMAL',
				'constraint' => ('10,3'),
				'null' => FALSE,

			),
			'gross_weight' => array(
				'type' => 'DECIMAL',
				'constraint' => ('10,3'),
				'null' => FALSE,

			),
			'crossdocking' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'zipcode' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => FALSE,

			),
			'CNPJ' => array(
				'type' => 'VARCHAR',
				'constraint' => ('25'),
				'null' => FALSE,

			),
			'freight_seller' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => FALSE,

			),
			'freight_seller_end_point' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'freight_seller_type' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("occ_last_post", TRUE);
		$this->db->query('ALTER TABLE  `occ_last_post` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table occ_last_post ##
		$this->dbforge->drop_table("occ_last_post", TRUE);

	}
};