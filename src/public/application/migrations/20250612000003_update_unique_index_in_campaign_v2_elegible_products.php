<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        // Start a transaction to ensure data integrity
        $this->db->trans_start();

        // First, we need to find the name of the existing unique index
        $query = $this->db->query("SHOW INDEXES FROM campaign_v2_elegible_products WHERE Column_name IN ('campaign_v2_id', 'product_id') AND Non_unique = 0");
        $indexes = $query->result_array();
        
        // Drop the existing unique index
        // We're looking for an index that includes both campaign_v2_id and product_id columns
        $index_name = null;
        $previous_key_name = null;
        $columns_in_index = [];
        
        foreach ($indexes as $index) {
            if ($previous_key_name !== $index['Key_name']) {
                // We're starting a new index
                if (!empty($columns_in_index) && count($columns_in_index) == 2 && 
                    in_array('campaign_v2_id', $columns_in_index) && 
                    in_array('product_id', $columns_in_index)) {
                    // We found our index
                    $index_name = $previous_key_name;
                    break;
                }
                
                // Reset for the new index
                $columns_in_index = [];
                $previous_key_name = $index['Key_name'];
            }
            
            $columns_in_index[] = $index['Column_name'];
        }
        
        // Check the last index if we haven't found one yet
        if ($index_name === null && !empty($columns_in_index) && count($columns_in_index) == 2 && 
            in_array('campaign_v2_id', $columns_in_index) && 
            in_array('product_id', $columns_in_index)) {
            $index_name = $previous_key_name;
        }
        
        // If we found the index, drop it
        if ($index_name !== null) {
            $this->db->query("ALTER TABLE campaign_v2_elegible_products DROP INDEX `{$index_name}`");
        } else {
            // Log a warning if we couldn't find the index
            log_message('warning', 'Migration 20250614000001: Could not find unique index on campaign_v2_id and product_id');
        }
        
        // Create the new unique index with three columns
        $this->db->query("ALTER TABLE campaign_v2_elegible_products ADD UNIQUE INDEX campaign_v2_elegible_products_unique_idx (campaign_v2_id, product_id, prd_variant_id)");
        
        // Complete the transaction
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            // Transaction failed, log the error
            log_message('error', 'Migration 20250614000001: Failed to update unique index in campaign_v2_elegible_products');
        }
    }

    public function down() {
        // Start a transaction to ensure data integrity
        $this->db->trans_start();
        
        // Drop the new unique index
        $this->db->query("ALTER TABLE campaign_v2_elegible_products DROP INDEX campaign_v2_elegible_products_unique_idx");
        
        // Recreate the original unique index with two columns
        $this->db->query("ALTER TABLE campaign_v2_elegible_products ADD UNIQUE INDEX campaign_v2_elegible_products_original_idx (campaign_v2_id, product_id)");
        
        // Complete the transaction
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            // Transaction failed, log the error
            log_message('error', 'Migration 20250614000001: Failed to revert unique index changes in campaign_v2_elegible_products');
        }
    }
};