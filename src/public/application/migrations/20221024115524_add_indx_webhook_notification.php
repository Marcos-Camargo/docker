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
            if (!$this->dbforge->index_exists('by_store_id', 'webhook_notification_queue')) {
                $this->db->query('CREATE INDEX by_store_id ON webhook_notification_queue (`store_id`);');
            }
            if (!$this->dbforge->index_exists('by_comp_store_int_id', 'webhook_notification_queue')) {
                $this->db->query('CREATE INDEX by_comp_store_int_id ON webhook_notification_queue (`company_id`,`store_id`,`integration_id`);');
            }
            if (!$this->dbforge->index_exists('by_store_origin_topic_status', 'webhook_notification_queue')) {
                $this->db->query('CREATE INDEX by_store_origin_topic_status ON webhook_notification_queue (`store_id`,`topic`,`origin`,`status`);');
            }
            if (!$this->dbforge->index_exists('by_store_origin_topic_status_date', 'webhook_notification_queue')) {
                $this->db->query('CREATE INDEX by_store_origin_topic_status_date ON webhook_notification_queue (`store_id`,`origin`,`topic`,`status`,`updated_at`);');
            }
        }

        public function down()
        {
            if ($this->dbforge->index_exists('by_store_id', 'webhook_notification_queue')) {
                $this->db->query('DROP INDEX by_store_id ON webhook_notification_queue;');
            }
            if ($this->dbforge->index_exists('by_comp_store_int_id', 'webhook_notification_queue')) {
                $this->db->query('DROP INDEX by_comp_store_int_id ON webhook_notification_queue;');
            }
            if ($this->dbforge->index_exists('by_store_origin_topic_status', 'webhook_notification_queue')) {
                $this->db->query('DROP INDEX by_store_origin_topic_status ON webhook_notification_queue;');
            }
            if ($this->dbforge->index_exists('by_store_origin_topic_status_date', 'webhook_notification_queue')) {
                $this->db->query('DROP INDEX by_store_origin_topic_status_date ON webhook_notification_queue;');
            }
        }
    };