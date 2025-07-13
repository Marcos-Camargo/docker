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
            if (!$this->dbforge->index_exists('ix_user_group_user_id', 'user_group')) {
                $this->db->query('CREATE INDEX ix_user_group_user_id ON user_group (`user_id`);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('ix_user_group_user_id', 'user_group')) {
                $this->db->query('DROP INDEX ix_user_group_user_id ON user_group;');
            }
        }
    };