<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'limit_product_rows_csv')) {
            $this->db->insert('settings', array(
                'name'      => "limit_product_rows_csv",
                'value'     => 1000,
                'status'    => 1,
                'user_id'   => 1
            ));
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'limit_product_rows_csv';");
    }
};