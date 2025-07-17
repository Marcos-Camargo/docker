<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE `log_quotes` ADD COLUMN `error_message` TEXT NULL');
        $this->db->query('ALTER TABLE `log_quotes` ADD INDEX idx_log_quotes_product_id (product_id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `log_quotes` DROP INDEX idx_log_quotes_product_id');
        $this->db->query('ALTER TABLE `log_quotes` DROP COLUMN `error_message`');
    }
};
