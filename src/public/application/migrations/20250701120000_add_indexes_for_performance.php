<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $this->db->query('CREATE INDEX idx_products_hasvariants ON products (has_variants)');
        $this->db->query('CREATE INDEX idx_campaign_v2_products ON campaign_v2_products (product_id, campaign_v2_id, removed, auto_removed)');
        $this->db->query('CREATE INDEX idx_prd_to_integration_prdid_variant_storeid ON prd_to_integration (prd_id, variant, store_id);');
        $this->db->query('CREATE INDEX idx_products_search ON products (status, store_id, id);');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_products_hasvariants ON products');
        $this->db->query('DROP INDEX idx_campaign_v2_products ON campaign_v2_products');
        $this->db->query('DROP INDEX idx_prd_to_integration_prdid_variant_storeid ON prd_to_integration');
        $this->db->query('DROP INDEX idx_products_search ON products');
    }
};