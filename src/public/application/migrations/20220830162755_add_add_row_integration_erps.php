<?php defined('BASEPATH') or exit('No direct script access allowed');

return new
/**
 * @property CI_DB_query_builder $db
 */
class extends CI_Migration {

    public function up()
    {
        if (!$this->db->field_exists('configuration_form', 'integration_erps'))
            $this->db->query("ALTER TABLE `integration_erps` ADD COLUMN `configuration_form` TEXT AFTER `support_link`;");

        if (!$this->db->field_exists('configuration', 'integration_erps'))
            $this->db->query("ALTER TABLE `integration_erps` ADD COLUMN `configuration` TEXT AFTER `configuration_form`;");

        $this->db->query("INSERT INTO `integration_erps` (`id`, `name`, `description`, `type`, `hash`, `active`, `visible`, `support_link`, `configuration_form`, `configuration`, `image`, `user_created`, `date_created`, `date_updated`) VALUES ('', 'ideris', 'Ideris', '1', '5125be55f123c619694b62bd397df6462114490f', '0', '1', '[]', NULL, '{}', 'ideris.png', NULL, 'CURRENT_TIMESTAMP()', 'CURRENT_TIMESTAMP()');");

        $this->db->query("UPDATE `integration_erps` SET `configuration_form` = '{\"api_url\":{\"name\":\"api_url\",\"label\":\"URL da API\",\"type\":\"text\"},\"application_id\":{\"name\":\"application_id\",\"label\":\"Id da Aplicação\",\"type\":\"text\"}}' WHERE (`name` = 'anymarket');");
        $this->db->query("UPDATE `integration_erps` SET `configuration_form` = '{\"app_consumer_key\":{\"name\":\"app_consumer_key\",\"label\":\"Chave pública do aplicativo (consumer_key)\",\"type\":\"text\"},\"app_consumer_secret\":{\"name\":\"app_consumer_secret\",\"label\":\"Chave secreta do aplicativo (consumer_secret)\",\"type\":\"text\"}}' WHERE (`name` = 'tray');");
    }

    public function down()
    {
        $this->db->query("DELETE FROM integration_erps WHERE `name` = 'ideris';");

        if ($this->db->field_exists('configuration_form', 'integration_erps')) {
            $this->db->query("UPDATE `integration_erps` SET `configuration_form` = NULL WHERE (`name` = 'anymarket');");
            $this->db->query("UPDATE `integration_erps` SET `configuration_form` = NULL WHERE (`name` = 'tray');");
            $this->db->query("ALTER TABLE `integration_erps` DROP COLUMN `configuration_form`;");
        }
        if ($this->db->field_exists('configuration', 'integration_erps')){
            $this->db->query("UPDATE `integration_erps` SET `configuration` = NULL WHERE (`name` = 'anymarket');");
            $this->db->query("UPDATE `integration_erps` SET `configuration` = NULL WHERE (`name` = 'tray');");
            $this->db->query("ALTER TABLE `integration_erps` DROP COLUMN `configuration`;");
        }

    }
};