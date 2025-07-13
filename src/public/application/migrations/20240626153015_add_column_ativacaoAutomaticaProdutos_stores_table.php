<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'ativacaoAutomaticaProdutos' => array(
                'type' => 'INT',
                'constraint' => 10,
                'null' => TRUE,
                'default' => NULL
            )
        );

        if (!$this->dbforge->column_exists('ativacaoAutomaticaProdutos', 'stores')) {
            $this->dbforge->add_column('stores', $fieldUpdate);
        }
    }

	public function down()	{
        $this->dbforge->drop_column('stores', 'ativacaoAutomaticaProdutos');
	}
};