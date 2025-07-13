<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	private $table_name = "orders_item";
	public function up() {

		$fields = array(
            'fulfillment_product_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            )
        );
		
		if(!$this->dbforge->column_exists('fulfillment_product_id', 'orders_item'))
		{
			$this->dbforge->add_column($this->table_name, $fields);
		}
	 }

	public function down()	{
        $this->dbforge->drop_column("orders_item", 'fulfillment_product_id');
    }
};