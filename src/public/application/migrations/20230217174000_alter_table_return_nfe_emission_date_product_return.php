<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'return_nfe_emission_date' => array(
                'type' => 'datetime',
                'null' => true,
                'after' => 'devolution_invoice_number'
            )
        );
        if (!$this->dbforge->column_exists('return_nfe_emission_date', 'product_return')) {
            $this->dbforge->add_column('product_return', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('product_return', 'return_nfe_emission_date');
    }
};