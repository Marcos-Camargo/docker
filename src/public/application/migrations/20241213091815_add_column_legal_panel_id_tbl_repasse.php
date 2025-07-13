<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->column_exists('legal_panel_id', 'repasse')){

			## Create column freights.volume
			$fields = array(
				'legal_panel_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,
					'default' => NULL
				)
			);
			$this->dbforge->add_column("repasse",$fields);
		}

		if (!$this->dbforge->column_exists('legal_panel_id', 'repasse_temp')){

			## Create column freights.volume
			$fields = array(
				'legal_panel_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,
					'default' => NULL
				)
			);
			$this->dbforge->add_column("repasse_temp",$fields);
		}
	}

	public function down()	{
		### Drop column freights.volume ##
		$this->dbforge->drop_column("repasse", 'legal_panel_id');
		$this->dbforge->drop_column("repasse_temp", 'legal_panel_id');

	}
};