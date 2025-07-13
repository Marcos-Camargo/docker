<?php defined('BASEPATH') or exit('No direct script access allowed');

return
    new
    /**
     * Class
     * @property CI_DB_query_builder $db
     */
    class extends CI_Migration {

        public function up()
        {
            if (!$this->checkIndex('ix_log_integration_store_id_status_type', 'log_integration')) {
                $this->db->query('create index ix_log_integration_store_id_status_type on log_integration (store_id, status, type);');
            }
        }

        public function down()
        {
            if ($this->checkIndex('ix_log_integration_store_id_status_type', 'log_integration')) {
                $this->db->query('DROP INDEX ix_log_integration_store_id_status_type ON log_integration;');
            }
        }

        protected function checkIndex(string $keyName, string $table): bool
        {
            $result = $this->db->query("SHOW INDEX FROM {$table} where Key_name = '{$keyName}'")->result_array();
            return !empty($result);
        }
    };