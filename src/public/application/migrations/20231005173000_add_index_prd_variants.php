<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->index_exists('ix_prd_variants_prd_id_sku', 'prd_variants')){
            ## Create index ix_prd_variants_prd_id_sku ##
            $this->db->query('CREATE INDEX ix_prd_variants_prd_id_sku ON prd_variants (prd_id, sku);');
        }
    }

	public function down()	{
		### Drop index ix_prd_variants_prd_id_sku ##
        $this->db->query('DROP INDEX ix_prd_variants_prd_id_sku ON prd_variants;');

	}
};