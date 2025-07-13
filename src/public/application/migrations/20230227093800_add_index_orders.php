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
            if (!$this->dbforge->index_exists('ix_orders_01', 'orders')) {
                $this->db->query('create index ix_orders_01 on orders (company_id, store_id, paid_status, numero_marketplace) algorithm = inplace lock = none;');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('ix_orders_01', 'orders')) {
                $this->db->query('DROP INDEX ix_orders_01 ON orders;');
            }
        }
    };