<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->query('ALTER TABLE `commissioning_orders_items` MODIFY COLUMN `commissioning_id` int(10) UNSIGNED NULL AFTER `item_id`;');
    }

    public function down()	{
    }
};
