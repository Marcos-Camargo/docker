<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {

		$fieldNew = array(
			'minimum_stock' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,
			),
			'ref_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => TRUE,
			),
//			'skuformat' => array(
//				'type' => 'VARCHAR',
//				'constraint' => ('50'),
//				'null' => TRUE,
//			),
			'reserve_stock' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,
			),
			'hasAuction' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'null' => TRUE,
			),
			'update_images_specifications' => array(
				'type' => 'TINYINT',
				'constraint' => ('1'),
				'default' => 1,

			),
		);

		if(!$this->dbforge->column_exists($fieldNew, 'integrations_settings'))
		{
			$this->dbforge->add_column('integrations_settings', $fieldNew);
		}

	 }

	public function down()	{
		$this->dbforge->drop_column('integrations_settings', 'minimum_stock');
		$this->dbforge->drop_column('integrations_settings', 'ref_id');
		$this->dbforge->drop_column('integrations_settings', 'skuformat');
		$this->dbforge->drop_column('integrations_settings', 'reserve_stock');
		$this->dbforge->drop_column('integrations_settings', 'hasAuction');
	}
};