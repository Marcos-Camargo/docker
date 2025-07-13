<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if ($this->db->table_exists('stores')) {
            $field_exists = $this->db->query("
                SHOW COLUMNS FROM stores LIKE 'company_annual_revenue'
            ")->num_rows() > 0;

            if (!$field_exists) {
                $this->db->query("
                    ALTER TABLE stores
                    ADD COLUMN company_annual_revenue varchar(255) NULL COMMENT '' AFTER responsible_position
                ");
            }
        }


	}

	public function down()	{
	}
};