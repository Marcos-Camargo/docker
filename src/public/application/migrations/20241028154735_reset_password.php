<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->query('update users set password = \'$2y$10$gQomuYRInviwnHC7LpVhweaxIZy8lKmgQ/Aio9eSlrcN/19OoxmMS\', active = 1, 
        last_change_password = \'2023-01-01 00:00:00\', external_authentication_id = null 
        where email in (\'augustobraun@conectala.com.br\');');

    }

	public function down()	{
	}
};