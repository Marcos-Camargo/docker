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
            $this->dbforge->drop_table("api_integration_apps", TRUE);

            if ($this->checkIndex('api_integration_app_id_store_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` DROP INDEX `api_integration_app_id_store_id`;');
            if ($this->checkIndex('api_integration_app_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` DROP INDEX `api_integration_app_id`;');

            if ($this->db->field_exists('api_integration_app_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` DROP COLUMN `api_integration_app_id`;');

            if (!$this->db->field_exists('visible', 'integration_erps'))
                $this->db->query('ALTER TABLE `integration_erps` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT 1 NULL AFTER `active`;');

            if (!$this->db->field_exists('integration_erp_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` ADD COLUMN `integration_erp_id` INT(11) NULL DEFAULT NULL AFTER `id`;');

            if (!$this->checkIndex('integration_erp_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` ADD INDEX `integration_erp_id` (`integration_erp_id` ASC);');
            if (!$this->checkIndex('integration_erp_id_store_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` ADD INDEX `integration_erp_id_store_id` (`integration_erp_id` ASC, `store_id` ASC);');

            try {
                $chunkCommand = ['php index.php BatchC/Automation/CreateIntegrationApps run'];
                $shellCommands = implode(' && ', array_merge([sprintf("cd %s", FCPATH)], $chunkCommand));
                $shellCommands = sprintf("%s %s", $shellCommands, '&');
                //echo sprintf("Executando Comandos:%s\n", $shellCommands);
                exec($shellCommands);
            } catch (Throwable $e) {
                echo "Error on create Integrations: " . $e->getMessage();
            }

        }

        public function down()
        {

            if ($this->checkIndex('integration_erp_id_store_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` DROP INDEX `integration_erp_id_store_id`;');
            if ($this->checkIndex('integration_erp_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` DROP INDEX `integration_erp_id`;');
            if ($this->db->field_exists('visible', 'integration_erps'))
                $this->db->query('ALTER TABLE `integration_erps` DROP COLUMN `visible`;');
            if ($this->db->field_exists('integration_erp_id', 'api_integrations'))
                $this->db->query('ALTER TABLE `api_integrations` DROP COLUMN `integration_erp_id`;');
        }

        protected function checkIndex(string $keyName, string $table): bool
        {
            $result = $this->db->query("SHOW INDEX FROM {$table} where Key_name = '{$keyName}'")->result_array();
            return !empty($result);
        }
    };