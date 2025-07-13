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
            $this->db->query('ALTER TABLE `log_integration_unique` CHANGE `unique_id` `unique_id` VARCHAR(255) NULL;');						
        }
    };