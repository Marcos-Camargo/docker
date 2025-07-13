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
            if (!$this->dbforge->index_exists('ix_prd_id_int_to_variant_prd_to_integration', 'prd_to_integration')) {
                $this->db->query('create index ix_prd_id_int_to_variant_prd_to_integration on prd_to_integration (prd_id, int_to, variant);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('ix_prd_id_int_to_variant_prd_to_integration', 'prd_to_integration')) {
                $this->db->query('DROP INDEX ix_prd_id_int_to_variant_prd_to_integration ON prd_to_integration;');
            }
        }
    };