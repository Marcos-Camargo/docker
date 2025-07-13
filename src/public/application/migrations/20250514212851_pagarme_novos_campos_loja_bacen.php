<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if ($this->db->table_exists('stores')) {
            $field_exists = $this->db->query("
                SHOW COLUMNS FROM stores LIKE 'responsible_monthly_income'
            ")->num_rows() > 0;

            if (!$field_exists) {
                $this->db->query("
                    ALTER TABLE stores
                    ADD COLUMN responsible_monthly_income varchar(255) NULL COMMENT '' AFTER responsible_position
                ");
            }
        }

		if ($this->db->table_exists('shopkeeper_form')) {
            $field_exists = $this->db->query("
                SHOW COLUMNS FROM shopkeeper_form LIKE 'responsible_monthly_income'
            ")->num_rows() > 0;

            if (!$field_exists) {
                $this->db->query("
                    ALTER TABLE shopkeeper_form
                    ADD COLUMN responsible_monthly_income varchar(255) NULL COMMENT '' AFTER responsible_position
                ");
            }
        }
		
	}

	public function down()	{
	}

};