<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'freight_calculation_standard' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => '0',
                'after' => 'shipping_revenue'
            )
        );

        if (!$this->dbforge->column_exists('freight_calculation_standard', 'shipping_company')) {
            $this->dbforge->add_column('shipping_company', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('shipping_company', 'freight_calculation_standard');
    }
};