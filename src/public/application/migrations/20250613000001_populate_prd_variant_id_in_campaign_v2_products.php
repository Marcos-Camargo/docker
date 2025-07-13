<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        // Start a transaction to ensure data integrity
        $this->db->trans_start();

        // Get all records from campaign_v2_products
        $this->db->select('campaign_v2_products.*');
        $this->db->where('products.has_variants !=', '');
        $this->db->join('products', 'products.id = campaign_v2_products.product_id');
        $query = $this->db->get('campaign_v2_products');
        $products = $query->result_array();

        // For each product, find matching variants and create new records
        foreach ($products as $product) {

            // Find all variants for this product
            $this->db->where('prd_id', $product['product_id']);
            $variants_query = $this->db->get('prd_variants');
            $variants = $variants_query->result_array();

            // If variants found, create new records
            if (!empty($variants)) {
                foreach ($variants as $variant) {
                    // Create a copy of the original product record
                    $new_record = $product;

                    // Remove the id to create a new record
                    unset($new_record['id']);

                    // Set the variant id
                    $new_record['prd_variant_id'] = $variant['id'];

                    // Update created_at and updated_at to current date/time
                    $new_record['created_at'] = date('Y-m-d H:i:s');
                    $new_record['updated_at'] = date('Y-m-d H:i:s');

                    // Insert the new record
                    $this->db->insert('campaign_v2_products', $new_record);
                }

                // Delete the original record after all variants have been inserted
                $this->db->where('id', $product['id']);
                $this->db->delete('campaign_v2_products');
            }
        }

        // Complete the transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            // Transaction failed, log the error
            log_message('error', 'Migration 20250613000001: Failed to populate prd_variant_id in campaign_v2_products');
        }
    }

    public function down() {
    }
};
