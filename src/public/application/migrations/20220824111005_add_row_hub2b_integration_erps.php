<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if (!$this->db->field_exists('visible', 'integration_erps')) {
            $this->db->query('ALTER TABLE `integration_erps` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT 1 NULL AFTER `active`;');
        }

        $this->db->query("INSERT INTO `integration_erps` (`id`, `name`, `description`, `type`, `hash`, `active`, `visible`, `support_link`, `image`) VALUES (NULL, 'hub2b', 'Hub2b', '1', 'eacf50d4a2db2aa992fd703c06fbc76802475c64', '0', '0', '[]', 'hub2b.jpg');");
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

    public function down()	{
        $this->db->query("DELETE FROM integration_erps WHERE `name` = 'hub2b';");
    }
};