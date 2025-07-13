<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->index_exists('idx_log_integration_date_updated', 'log_integration')) {
            $this->db->query("CREATE INDEX idx_log_integration_date_updated ON log_integration (date_updated);");
        }
        if (!$this->dbforge->index_exists('idx_log_products_date_update', 'log_products')) {
            $this->db->query("CREATE INDEX idx_log_products_date_update ON log_products (date_update);");
        }
	}

	public function down()	{
        if ($this->dbforge->index_exists('idx_log_integration_date_updated', 'log_integration')) {
            $this->db->query('DROP INDEX idx_log_integration_date_updated ON log_integration');
        }
        if ($this->dbforge->index_exists('idx_log_products_date_update', 'log_products')) {
            $this->db->query('DROP INDEX idx_log_products_date_update ON log_products');
        }
	}
};