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
            if (!$this->dbforge->index_exists('ix_errors_transformation_status_id', 'errors_transformation')) {
                $this->db->query('create index ix_errors_transformation_status_id on errors_transformation (status, id);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('ix_errors_transformation_status_id', 'errors_transformation')) {
                $this->db->query('DROP INDEX ix_errors_transformation_status_id ON errors_transformation;');
            }
        }
    };