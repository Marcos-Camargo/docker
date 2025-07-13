<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        if ($this->dbforge->column_exists('is_default', 'vtex_trade_policies')){
            $this->db->query('ALTER TABLE vtex_trade_policies DROP COLUMN `is_default`');
        }

    }

    public function down()
    {
    }
};