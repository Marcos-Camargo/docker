<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->index_exists('unique_prd_name', 'prd_variants')) {
            $this->db->query('create index unique_prd_name on prd_variants (prd_id, name);');
        }
        if (!$this->dbforge->index_exists('unique_prd_sku', 'prd_variants')) {
            $this->db->query('create index unique_prd_sku on prd_variants (prd_id, sku);');
        }
    }

    public function down()
    {
        if ($this->dbforge->index_exists('unique_prd_name', 'prd_variants')) {
            $this->db->query('DROP INDEX unique_prd_name ON prd_variants;');
        }
        if ($this->dbforge->index_exists('unique_prd_sku', 'prd_variants')) {
            $this->db->query('DROP INDEX unique_prd_sku ON prd_variants;');
        }
    }
};
