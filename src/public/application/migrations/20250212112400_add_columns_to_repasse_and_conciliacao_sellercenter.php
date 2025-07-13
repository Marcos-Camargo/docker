<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->column_exists('created_at', 'repasse')) {
            $this->db->query("
                ALTER TABLE `repasse`
                ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT current_timestamp() AFTER `legal_panel_id`;
            ");
        }

        if (!$this->dbforge->column_exists('updated_at', 'repasse')) {
            $this->db->query("
                ALTER TABLE `repasse`
                ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;
            ");
        }

        if (!$this->dbforge->column_exists('created_at', 'conciliacao_sellercenter')) {
            $this->db->query("
                ALTER TABLE `conciliacao_sellercenter`
	            ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT current_timestamp() AFTER `legal_panel_id`;
            ");
        }

        if (!$this->dbforge->column_exists('updated_at', 'conciliacao_sellercenter')) {
            $this->db->query("
                ALTER TABLE `conciliacao_sellercenter`
                ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;
            ");
        }
    }

    public function down() {
        $this->dbforge->drop_column('created_at', 'repasse');
        $this->dbforge->drop_column('updated_at', 'repasse');
        $this->dbforge->drop_column('created_at', 'conciliacao_sellercenter');
        $this->dbforge->drop_column('updated_at', 'conciliacao_sellercenter');
    }
};
