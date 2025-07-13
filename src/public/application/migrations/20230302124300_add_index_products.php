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
            if (!$this->dbforge->index_exists('ix_products_03', 'products')) {
                $this->db->query('create index ix_products_03 on products (`store_id`,`status`);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('ix_products_03', 'products')) {
                $this->db->query('DROP INDEX ix_products_03 ON products;');
            }
        }
    };