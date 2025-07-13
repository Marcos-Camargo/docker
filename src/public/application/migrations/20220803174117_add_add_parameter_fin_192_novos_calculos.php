<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {              
        $this->db->query("
        INSERT INTO settings (name, value, status, user_id)
        SELECT * FROM (SELECT 'fin_192_novos_calculos' AS name, 'Libera novas colunas da conciliação e extrato da Tarefa FIN-192' AS value, 2 AS status, 1 AS user_id) AS temp
        WHERE NOT EXISTS (
            SELECT name FROM settings WHERE name = 'fin_192_novos_calculos'
        ) LIMIT 1;
        ");
    }

	public function down()	{		
        $this->db->query('DELETE FROM settings WHERE name like "fin_192_novos_calculos";');		
	}
};