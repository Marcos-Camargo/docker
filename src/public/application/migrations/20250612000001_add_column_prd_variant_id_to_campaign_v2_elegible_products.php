<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        // Define the column to be added
        $fieldUpdate = array(
            'prd_variant_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'after' => 'product_id'
            )
        );

        // Add the column if it doesn't exist
        if (!$this->dbforge->column_exists('prd_variant_id', 'campaign_v2_elegible_products')) {
            $this->dbforge->add_column('campaign_v2_elegible_products', $fieldUpdate);
        }

        // Add foreign key constraint with cascade
        $this->db->query('ALTER TABLE `campaign_v2_elegible_products` ADD CONSTRAINT `campaign_v2_elegible_products_prd_variant_id_fk` FOREIGN KEY (`prd_variant_id`) REFERENCES `prd_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down() {
        // Drop the foreign key constraint first
        $this->db->query('ALTER TABLE campaign_v2_elegible_products DROP FOREIGN KEY campaign_v2_elegible_products_prd_variant_id_fk');
        
        // Then drop the column
        $this->dbforge->drop_column('campaign_v2_elegible_products', 'prd_variant_id');
    }
};