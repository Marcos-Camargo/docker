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
            if (!$this->dbforge->index_exists('ix_integrations_logistic_name', 'integrations_logistic')) {
                $this->db->query('CREATE INDEX ix_integrations_logistic_name ON integrations_logistic (`name`);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('ix_integrations_logistic_name', 'integrations_logistic')) {
                $this->db->query('DROP INDEX ix_integrations_logistic_name ON integrations_logistic;');
            }
        }
    };