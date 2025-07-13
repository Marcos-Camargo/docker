<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{

		// Campo a ser criado.
		$field = array(
			'is_on_bucket' => array('type' => 'TINYINT', 'constraint' => ('1'), 'default' => 0)
		);

		// Cria na tabela products.
		if (!$this->dbforge->column_exists('is_on_bucket', 'products')) {
			$this->dbforge->add_column('products', $field);
		}
	}

	public function down()
	{
		// Drop na coluna na products.
		if ($this->dbforge->column_exists('is_on_bucket', 'products'))
			$this->dbforge->drop_column("products", 'is_on_bucket');
	}
};
