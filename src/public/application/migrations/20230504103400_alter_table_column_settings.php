<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->query('ALTER TABLE `settings` CHANGE COLUMN `value` `value` TEXT NULL DEFAULT NULL ;');
    }

    public function down()
    {

    }
};