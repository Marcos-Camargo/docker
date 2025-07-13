<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("CREATE INDEX idx_seller_index_history_store_date ON seller_index_history (store_id, date)");
		$this->db->query("CREATE INDEX idx_products_store_id_status_qty ON products (store_id, status, qty)");
		$this->db->query("CREATE INDEX idx_prd_to_integration_prd_store ON prd_to_integration (prd_id, store_id)");
	}

	public function down()	{
	}
};