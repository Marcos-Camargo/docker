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
            if (!$this->checkIndex('ix_log_integration_table_status_store_id', 'log_integration')) {
                $this->db->query('create index ix_log_integration_table_status_store_id on log_integration (status, store_id);');
            }
        }

        public function down()
        {
            if ($this->checkIndex('ix_log_integration_table_status_store_id', 'log_integration')) {
                $this->db->query('DROP INDEX ix_log_integration_01 ON log_integration;');
            }
        }

        protected function checkIndex(string $keyName, string $table): bool
        {
            $result = $this->db->query("SHOW INDEX FROM {$table} where Key_name = '{$keyName}'")->result_array();
            return !empty($result);
        }
    };