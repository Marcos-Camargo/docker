<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	private $table_name = "integrations_settings";
	public function up() {

		$this->db->query('ALTER TABLE orders CHANGE COLUMN multi_channel_fulfillment_order_id multi_channel_fulfillment_store_id INT(11) NULL;');
	 }

	public function down()	{
        $this->db->query('ALTER TABLE orders CHANGE COLUMN multi_channel_fulfillment_store_id multi_channel_fulfillment_order_id INT(11) NULL;');
	}
};