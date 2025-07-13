<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true
            ),
            'product_id' => array(
                'type' => 'INT',
                'null' => false
            ),
            'product_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ),
            'sku' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ),
            'category' => array(
                'type' => 'TEXT',
                'null' => false
            ),
            'total_attributes_not_filled' => array(
                'type' => 'INT',
                'null' => false
            )
        ));

        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("products_counting_attributes", true);
    }

    public function down()	{
        $this->dbforge->drop_table("products_counting_attributes", true);
    }
};