<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $modify_fields = array(
            'url' => array(
                'type' => 'VARCHAR',
                'constraint' => ('500'),
                'null'  => TRUE
            )
        );
        $create_fields = array(
            'invoice_emission_date' => array(
                'type' => 'DATE',
                'null'  => TRUE
            ),
            'invoice_number' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null'  => TRUE
            ),
            'invoice_amount_total' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null'  => TRUE
            ),
            'invoice_amount_irrf' => array(
                'type' => 'DECIMAL',
                'constraint' => ('10,2'),
                'null'  => TRUE
            ),
            'param_mkt_ciclo_fiscal_id' => array(
                'type' => 'INT',
                'null'  => TRUE
            ),
        );

        $this->dbforge->modify_column('nota_fiscal_servico_url_temp', $modify_fields);
        $this->dbforge->add_column('nota_fiscal_servico_url_temp', $create_fields);
	}

	public function down()	{
        $modify_fields = array(
            'url' => array(
                'type' => 'VARCHAR',
                'constraint' => ('500'),
                'null'  => FALSE
            )
        );
        $this->dbforge->modify_column('nota_fiscal_servico_url_temp', $modify_fields);
        $this->dbforge->drop_column('nota_fiscal_servico_url_temp', 'invoice_emission_date');
        $this->dbforge->drop_column('nota_fiscal_servico_url_temp', 'invoice_number');
        $this->dbforge->drop_column('nota_fiscal_servico_url_temp', 'invoice_amount_total');
        $this->dbforge->drop_column('nota_fiscal_servico_url_temp', 'invoice_amount_irrf');
        $this->dbforge->drop_column('nota_fiscal_servico_url_temp', 'param_mkt_ciclo_fiscal_id');
    }
};