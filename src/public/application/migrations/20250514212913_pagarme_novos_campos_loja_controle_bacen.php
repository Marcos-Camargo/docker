<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		
		if ($this->db->table_exists('stores')) {
            $field_exists = $this->db->query("
                SHOW COLUMNS FROM stores LIKE 'rule_pagarme_bacen'
            ")->num_rows() > 0;

            if (!$field_exists) {
                $this->db->query("
                    ALTER TABLE stores
                    ADD COLUMN rule_pagarme_bacen int(1) DEFAULT 0 NOT NULL 
                ");
            }
        }

	}

	public function down()	{
	}
};