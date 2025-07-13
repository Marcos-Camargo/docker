<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{

		// Campo a ser criado.
		$field = array(
			'is_on_bucket' => array('type' => 'TINYINT', 'constraint' => ('1'), 'default' => 0)
		);

		// Cria na tabela products_catalog.
		if (!$this->dbforge->column_exists('is_on_bucket', 'products_catalog')) {
			$this->dbforge->add_column('products_catalog', $field);
		}

		$this->db->query("ALTER TABLE `products_catalog` MODIFY `is_on_bucket` TINYINT(1) DEFAULT 1");
	}

	public function down()
	{
		// Drop na coluna na products_catalog.
		if ($this->dbforge->column_exists('is_on_bucket', 'products_catalog')) {
			$this->dbforge->drop_column('products_catalog', 'is_on_bucket');
		}
	}
};
