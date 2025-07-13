<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if ($this->db->get_where('users', ['email' => 'jacquelinebonifacio@conectala.com.br'])->row()) {
            $this->db->update('users', 	
			['active' => 1,], 
			['external_authentication_id' => null,], 
			['email' => 'jacquelinebonifacio@conectala.com.br']);
			
            $this->db->query('UPDATE users SET password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\'
                WHERE email = \'jacquelinebonifacio@conectala.com.br\'');
		}		
		else{
			$this->db->query("
				INSERT INTO users
				(id, username, password, email, firstname, lastname, phone, gender, company_id, parent_id,
				 previous_passwords, password_agidesk, token_agidesk, last_login_date, active, store_id,
				 date_create, provider_id, last_change_password, bank, agency, account_type, account,
				 associate_type, token_agidesk_conectala, password_agidesk_conectala, legal_administrator, cpf,
				 external_authentication_id)
				VALUES
				(0, 'jacquelinebonifacio@conectala.com.br', 'senha', 'jacquelinebonifacio@conectala.com.br', 'Jacqueline', 'Bonifacio',
				 '00 000000000', 0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), NULL,
				 '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL)
			");
	
			$this->db->query('UPDATE users set password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\' where email in 
			 (\'jacquelinebonifacio@conectala.com.br\')');
	
			$this->db->query("INSERT INTO user_group (user_id, group_id) 
			select distinct users.id, groups.id FROM users, `groups`
			where users.email in ('jacquelinebonifacio@conectala.com.br')  
			and `groups`.group_name like '%Time_CS_Conectala%'");

		}
	}

	public function down()	{
		$this->db->query("
			DELETE ug FROM user_group ug
			JOIN users u ON u.id = ug.user_id
			JOIN `groups` g ON g.id = ug.group_id
			WHERE u.email = 'jacquelinebonifacio@conectala.com.br'
			AND g.group_name LIKE '%Time_CS_Conectala%'
		");

		$this->db->delete('users', ['email' => 'jacquelinebonifacio@conectala.com.br']);
	}
};