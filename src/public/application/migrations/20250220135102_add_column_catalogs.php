<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->column_exists('integrate_products_that_exist_in_other_catalogs', 'catalogs')){
			## Create column catalogs
			$fields = array(
				'integrate_products_that_exist_in_other_catalogs' => array(
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
		$this->dbforge->drop_column("catalogs", 'integrate_products_that_exist_in_other_catalogs');

	}
};