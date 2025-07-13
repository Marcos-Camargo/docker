<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->column_exists('inactive_products_with_inactive_brands', 'catalogs')){
			## Create column catalogs
			$fields = array(
				'inactive_products_with_inactive_brands' => array(
					'type' => 'TINYINT',
					'constraint' => ('1'),
					'null' => FALSE,
					'default' => 0
				)
			);
			$this->dbforge->add_column("catalogs",$fields);
		}
	}

	public function down()	{
		### Drop column catalogs
		$this->dbforge->drop_column("catalogs", 'inactive_products_with_inactive_brands');

	}
};