<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        if (!$this->dbforge->index_exists('idx_log_integration_unique_date_updated', 'log_integration_unique')) {
            $this->db->query("CREATE INDEX idx_log_integration_unique_date_updated ON log_integration_unique (date_updated);");
        }
    }

    public function down()    {
        if ($this->dbforge->index_exists('idx_log_integration_unique_date_updated', 'log_integration_unique')) {
            $this->db->query('DROP INDEX idx_log_integration_unique_date_updated ON log_integration_unique');
        }
    }
};