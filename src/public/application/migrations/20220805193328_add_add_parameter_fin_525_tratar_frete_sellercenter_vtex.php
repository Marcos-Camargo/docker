<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {              
        $this->db->query("
        INSERT INTO settings (name, value, status, user_id)
        SELECT * FROM (SELECT 'frete_100_canal_seller_centers_vtex' AS name, 'Libera a trataiva de frete 100% nos sellercenters' AS value, 2 AS status, 1 AS user_id) AS temp
        WHERE NOT EXISTS (
            SELECT name FROM settings WHERE name = 'frete_100_canal_seller_centers_vtex'
        ) LIMIT 1;
        ");
    }

	public function down()	{		
        $this->db->query('DELETE FROM settings WHERE name like "frete_100_canal_seller_centers_vtex";');		
	}
};