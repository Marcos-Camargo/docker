<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {              
        $this->db->query("
        INSERT INTO settings (name, value, status, user_id)
        SELECT * FROM (SELECT 'show_new_columns_fin_46_temp' AS name, 'Libera novas colunas temporarias de extrato da Tarefa FIN-46' AS value, 2 AS status, 1 AS user_id) AS temp
        WHERE NOT EXISTS (
            SELECT name FROM settings WHERE name = 'show_new_columns_fin_46_temp'
        ) LIMIT 1;
        ");
    }

	public function down()	{		
        $this->db->query('DELETE FROM settings WHERE name like "show_new_columns_fin_46_temp";');		
	}
};