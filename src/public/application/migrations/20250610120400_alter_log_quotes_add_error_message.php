<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('error_message', 'log_quotes')) {
            $this->db->query("ALTER TABLE `log_quotes` ADD COLUMN `error_message` TEXT NULL AFTER `response_slas`");
        }
        $hasIndex = $this->db->query("SHOW INDEX FROM `log_quotes` WHERE Key_name = 'idx_log_quotes_product_id'")->num_rows() > 0;
        if (!$hasIndex) {
            $this->db->query('ALTER TABLE `log_quotes` ADD INDEX idx_log_quotes_product_id (product_id)');
        }
    }

    public function down()
    {
        $hasIndex = $this->db->query("SHOW INDEX FROM `log_quotes` WHERE Key_name = 'idx_log_quotes_product_id'")->num_rows() > 0;
        if ($hasIndex) {
            $this->db->query('ALTER TABLE `log_quotes` DROP INDEX idx_log_quotes_product_id');
        }
        if ($this->db->field_exists('error_message', 'log_quotes')) {
            $this->db->query('ALTER TABLE `log_quotes` DROP COLUMN `error_message`');
        }
    }
};
