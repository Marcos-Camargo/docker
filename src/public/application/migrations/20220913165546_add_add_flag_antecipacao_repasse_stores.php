<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
    if (!$this->dbforge->column_exists('flag_antecipacao_repasse', 'stores')){      
      $fields = array(
          'flag_antecipacao_repasse' => array(
            'type' => 'VARCHAR',
            'constraint' => ('1'),
            'null' => FALSE,
            'default' => 'N'
          )
      );
      $this->dbforge->add_column("stores", $fields);
    }
  }

	public function down()	{		
		$this->dbforge->drop_column("stores", 'flag_antecipacao_repasse');
	}
};