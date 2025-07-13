
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
   public function up()
    {
        $field_int_to = array(
            'int_to' => array(
                'type' => 'VARCHAR',
                'constraint' => ('20'),
                'null' => TRUE,
            )
        );

        $field_store_id = array(
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE,
            )
        );

        if (!$this->dbforge->column_exists('int_to', 'products_seller_migration'))
        {
            $this->dbforge->add_column('products_seller_migration', $field_int_to);
        }

        if (!$this->dbforge->column_exists('store_id', 'products_seller_migration'))
        {
            $this->dbforge->add_column('products_seller_migration', $field_store_id);
        }
	}

	public function down() {}
};
