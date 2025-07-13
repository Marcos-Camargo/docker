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
            if (!$this->dbforge->index_exists('by_store_topic_status', 'webhook_notification_queue')) {
                $this->db->query('CREATE INDEX by_store_topic_status ON webhook_notification_queue (`store_id`,`topic`, `status`);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('by_store_topic_status', 'webhook_notification_queue')) {
                $this->db->query('DROP INDEX by_store_topic_status ON webhook_notification_queue;');
            }
        }
    };