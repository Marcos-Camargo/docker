<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query('UPDATE users set password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\' where email in 
				(\'eduardosiqueira@conectala.com.br\', \'victorlima@conectala.com.br\')');
	}

	public function down()	{
	}
};