<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->index_exists('idx_anymarket_temp_product_anymarketId_integration_id', 'anymarket_temp_product')) {
            $this->db->query("create index idx_anymarket_temp_product_anymarketId_integration_id on anymarket_temp_product (anymarketId, integration_id);");
        }
        if (!$this->dbforge->index_exists('idx_anymarket_temp_product_integration_id_anymarketId', 'anymarket_temp_product')) {
            $this->db->query("create index idx_anymarket_temp_product_integration_id_anymarketId on anymarket_temp_product (integration_id,anymarketId);");
        }
        if (!$this->dbforge->index_exists('idx_anymarket_temp_product_id_sku_product_integration_id', 'anymarket_temp_product')) {
            $this->db->query("create index idx_anymarket_temp_product_id_sku_product_integration_id on anymarket_temp_product (id_sku_product,integration_id);");
        }
	 }

	public function down()	{
        $this->db->query("DROP INDEX idx_anymarket_temp_product_anymarketId_integration_id on anymarket_temp_product;");
        $this->db->query("DROP INDEX idx_anymarket_temp_product_integration_id_anymarketId on anymarket_temp_product;");
        $this->db->query("DROP INDEX idx_anymarket_temp_product_id_sku_product_integration_id on anymarket_temp_product;");
	}

};