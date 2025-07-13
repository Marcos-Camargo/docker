<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table gopoints_last_post
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
			'company_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'EAN' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => FALSE,

			),
			'prd_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'price' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => FALSE,

			),
			'list_price' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => TRUE,

			),
			'sku' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'data_ult_envio' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => FALSE,

			),
			'tipo_volume_codigo' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,
				'default' => '999',

			),
			'qty_total' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => TRUE,

			),
			'largura' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'altura' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'profundidade' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'peso_bruto' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'crossdocking' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'zipcode' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => TRUE,

			),
			'CNPJ' => array(
				'type' => 'VARCHAR',
				'constraint' => ('25'),
				'null' => TRUE,

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
			'skulocal' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'seller_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('32'),
				'null' => FALSE,

			),
			'variant' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("gopoints_last_post", TRUE);
	 }

	public function down()	{
		### Drop table gopoints_last_post ##
		$this->dbforge->drop_table("gopoints_last_post", TRUE);

	}
};