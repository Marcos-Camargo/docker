<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        $this->db->query("INSERT INTO `integrations_logistic`(`name`, `description`, `use_sellercenter`, `use_seller`, `only_store`, `fields_form`) VALUES ('magalu', 'Magalu', 0, 1, 1, '{}')");
    }

    public function down()	{
        $this->db->query("DELETE FROM integrations_logistic WHERE `name` = 'magalu';");
    }
};