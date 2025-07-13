<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		 if (!$this->db->field_exists('forced_to_delivery', 'orders')) {
            $this->dbforge->add_column('orders', [
                'forced_to_delivery' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => FALSE
                ]
            ]);
        }
	}

	public function down()	{
		if ($this->db->field_exists('forced_to_delivery', 'orders')) {
            $this->dbforge->drop_column('orders', 'forced_to_delivery');
        }
	}
};