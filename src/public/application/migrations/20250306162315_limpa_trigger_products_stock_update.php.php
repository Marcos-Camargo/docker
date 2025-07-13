<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $this->db->query("DROP TRIGGER `ProductsStockUpdatedAt`;");
    }

    public function down() {}
};
