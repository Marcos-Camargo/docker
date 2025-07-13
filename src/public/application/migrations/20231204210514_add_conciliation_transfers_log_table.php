<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE `conciliation_transfer_logs`  (
          `id` int(10) NOT NULL AUTO_INCREMENT,
          `data` longtext NULL,
          `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP(),
          PRIMARY KEY (`id`)
        );");
    }

    public function down()
    {
    }
};