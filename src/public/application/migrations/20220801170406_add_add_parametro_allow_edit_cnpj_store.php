<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->register_exists('settings', 'name', 'allow_cnpj_store_update')){
            $this->db->query("INSERT INTO settings (name, value, status, user_id) VALUES ('allow_cnpj_store_update', 'Libera a edição do CNPJ da loja', 2, 1);");
        }
	}

	public function down()	{		
        $this->db->query('DELETE FROM settings WHERE name like "allow_cnpj_store_update";');		
	}
};