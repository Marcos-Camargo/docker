<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query(" DELETE FROM users where email in ('milenabarros@conectala.com.br', 'daniellycosta@conectala.com.br', 'geisylalima@conectala.com.br');");
		
		$userestag1 = $this->db->query("
        INSERT users
        	(id, username, password, email, firstname, 
            lastname, phone, gender, company_id, parent_id, previous_passwords, 
         	password_agidesk, token_agidesk, last_login_date, active, store_id, date_create, provider_id, 
          	last_change_password, bank, agency, account_type, account, associate_type, token_agidesk_conectala, 
         	password_agidesk_conectala, legal_administrator, cpf, external_authentication_id)
         VALUES(0, 'milenabarros@conectala.com.br', 'senha', 'milenabarros@conectala.com.br', 'Milena', 'Moraes', '81 998123940', 
         	0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
         	NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL), (0, 'daniellycosta@conectala.com.br', 
			'senha', 'daniellycosta@conectala.com.br', 'Danielly', 'Costa', '83 993687797', 
         	0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
         	NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL), (0, 'geisylalima@conectala.com.br', 
			'senha', 'geisylalima@conectala.com.br', 'Geisyla', 'Lima', '33 988799753', 
         	0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
         	NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL)");

			
		$this->db->query('UPDATE users set password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\' where email in 
			 (\'milenabarros@conectala.com.br\', \'daniellycosta@conectala.com.br\', \'geisylalima@conectala.com.br\')');

		$userestag = $this->db->query("INSERT INTO user_group (user_id, group_id) 
		select distinct users.id, groups.id FROM users, `groups`
		where users.email in ('milenabarros@conectala.com.br', 'daniellycosta@conectala.com.br', 'geisylalima@conectala.com.br')  and `groups`.group_name like '%Time_DEV_Conectala%'");

	}

	public function down()	{
	}
};