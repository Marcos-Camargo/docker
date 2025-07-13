<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'conciliacao_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'default' => NULL
            )
        );

        if (!$this->dbforge->column_exists('conciliacao_id', 'legal_panel')) {
            $this->dbforge->add_column('legal_panel', $fieldUpdate);
        }
    }

	public function down()	{
        $this->dbforge->drop_column('legal_panel', 'conciliacao_id');
	}
};