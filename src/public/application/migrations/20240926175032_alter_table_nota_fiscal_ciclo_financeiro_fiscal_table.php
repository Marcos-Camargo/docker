<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'processed_at' => array(
                'type' => 'timestamp',
                'null' => TRUE
            )
        );

        if (!$this->dbforge->column_exists('processed_at', 'nota_fiscal_ciclo_financeiro_fiscal')) {
            $this->dbforge->add_column('nota_fiscal_ciclo_financeiro_fiscal', $fieldUpdate);
        }
	
	}

	public function down()	{
        $this->dbforge->drop_column('nota_fiscal_ciclo_financeiro_fiscal', 'processed_at');
	}
};