<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->index_exists('idx_nota_fiscal_servico_url_by_store_number_lote', 'nota_fiscal_servico_url')) {
            $this->db->query("CREATE INDEX idx_nota_fiscal_servico_url_by_store_number_lote ON nota_fiscal_servico_url (store_id, invoice_number, lote)");
        }
	}

	public function down()	{
        if ($this->dbforge->index_exists('idx_nota_fiscal_servico_url_by_store_number_lote', 'nota_fiscal_servico_url')) {
            $this->db->query('DROP INDEX idx_nota_fiscal_servico_url_by_store_number_lote ON nota_fiscal_servico_url');
        }
	}
};