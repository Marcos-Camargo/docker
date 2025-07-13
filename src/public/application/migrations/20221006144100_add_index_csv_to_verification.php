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
            //$this->db->query('create index ix_csv_to_verification_checked_module_store_form_data on csv_to_verification (`checked`, `module`, `store_id`, `form_data`(128));');
            //$this->db->query('create index ix_csv_to_verification_checked_module_store on csv_to_verification (`checked`, `module`, `store_id`);');
        }

        public function down()
        {
            //if ($this->dbforge->index_exists('ix_csv_to_verification_checked_module_store_form_data', 'csv_to_verification')) {
                //$this->db->query('DROP INDEX ix_csv_to_verification_checked_module_store_form_data ON csv_to_verification;');
            //}
            //if ($this->dbforge->index_exists('ix_csv_to_verification_checked_module_store', 'csv_to_verification')) {
                //$this->db->query('DROP INDEX ix_csv_to_verification_checked_module_store ON csv_to_verification;');
            //}
        }
    };