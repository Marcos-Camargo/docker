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
            if (!$this->dbforge->index_exists('index_by_customer_id_store_id_date', 'orders')) {
                $this->db->query('create index index_by_customer_id_store_id_date on orders (customer_id, id, store_id, date_time);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('index_by_customer_id_store_id_date', 'orders')) {
                $this->db->query('DROP INDEX index_by_customer_id_store_id_date ON orders;');
            }
        }
    };