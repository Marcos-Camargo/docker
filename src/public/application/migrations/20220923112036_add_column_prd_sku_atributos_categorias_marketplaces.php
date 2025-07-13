<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		## Create column atributos_categorias_marketplaces.prd_sku
        $fieldUpdate = array(
            'prd_sku' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => TRUE,
                'default' => '1',
                'after' => 'tooltip'
            )
        );
        if (!$this->dbforge->column_exists('prd_sku', 'atributos_categorias_marketplaces')) {
            $this->dbforge->add_column('atributos_categorias_marketplaces', $fieldUpdate);
        }
	 }

	public function down()	{
        $this->dbforge->drop_column('atributos_categorias_marketplaces', 'prd_sku');
	}
};