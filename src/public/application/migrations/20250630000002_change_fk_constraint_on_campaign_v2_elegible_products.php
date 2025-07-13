<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        // First, drop the existing foreign key constraint with CASCADE behavior
        $this->db->query('ALTER TABLE campaign_v2_elegible_products DROP FOREIGN KEY campaign_v2_elegible_products_prd_variant_id_fk');
        
        // Then add a new foreign key constraint with RESTRICT behavior
        $this->db->query('ALTER TABLE `campaign_v2_elegible_products` ADD CONSTRAINT `campaign_v2_elegible_products_prd_variant_id_fk` FOREIGN KEY (`prd_variant_id`) REFERENCES `prd_variants` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down() {
        // Drop the RESTRICT foreign key constraint
        $this->db->query('ALTER TABLE campaign_v2_elegible_products DROP FOREIGN KEY campaign_v2_elegible_products_prd_variant_id_fk');
        
        // Re-add the original CASCADE foreign key constraint
        $this->db->query('ALTER TABLE `campaign_v2_elegible_products` ADD CONSTRAINT `campaign_v2_elegible_products_prd_variant_id_fk` FOREIGN KEY (`prd_variant_id`) REFERENCES `prd_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }
};