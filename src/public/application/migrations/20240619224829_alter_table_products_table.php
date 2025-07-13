<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fields = array(
            'is_variation_grouped' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null'  => FALSE,
                'default' => 0
            )
        );

        $this->dbforge->add_column('products', $fields);
	}

	public function down()	{
        $this->dbforge->drop_column('products', 'is_variation_grouped');
    }
};