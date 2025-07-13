<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        if ($this->dbforge->column_exists('active', 'legal_panel')){
            $this->db->query('ALTER TABLE legal_panel DROP COLUMN `active`');
        }

    }

    public function down()
    {
    }
};