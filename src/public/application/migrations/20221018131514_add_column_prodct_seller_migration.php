
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
			'category_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('45'),
				'null' => TRUE,
			),
			'brand_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('45'),
				'null' => TRUE,
			),
			'brand_name' => array(
				'type' => 'TEXT',
				'null' => TRUE,
			),		

        );

        if (!$this->dbforge->column_exists($fieldNew, 'products_seller_migration'))
        {
            $this->dbforge->add_column('products_seller_migration', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('products_seller_migration', 'user_id');
	}
};
