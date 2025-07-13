<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->column_exists('volume', 'freights')){

            ## Create column freights.volume
            $fields = array(
                'volume' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                    'default' => 1
                )
            );
            $this->dbforge->add_column("freights",$fields);

        }
	 }

	public function down()	{
		### Drop column freights.volume ##
		$this->dbforge->drop_column("freights", 'volume');

	}
};