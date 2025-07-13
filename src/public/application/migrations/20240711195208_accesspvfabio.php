<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if($this->db->where('email', 'fabioribeiro@conectala.com.br')->get('users')->num_rows() === 0){ //verifica se tem no users, se entrar aqui insere
			$userestag1 = $this->db->query("
        INSERT users
        	(id, username, password, email, firstname, 
            lastname, phone, gender, company_id, parent_id, previous_passwords, 
         	password_agidesk, token_agidesk, last_login_date, active, store_id, date_create, provider_id, 
          	last_change_password, bank, agency, account_type, account, associate_type, token_agidesk_conectala, 
         	password_agidesk_conectala, legal_administrator, cpf, external_authentication_id)
         VALUES(0, 'fabioribeiro@conectala.com.br', 'senha', 'fabioribeiro@conectala.com.br', 'Fabio', 'Ribeiro', '41 997354774', 
         	0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
         	NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL)");

			 $this->db->query('UPDATE users set password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\' where email in 
			 (\'fabioribeiro@conectala.com.br\')');

			$userestag = $this->db->query("INSERT INTO user_group (user_id, group_id) 
			select distinct users.id, groups.id FROM users, `groups`
			where users.email in ('fabioribeiro@conectala.com.br')  
			and `groups`.group_name like '%Time_DEV_Conectala%'");

		}
		else{
			echo "usuario ja existe na tabela users";

		}

		if($this->db->where('email', 'paulosilva@conectala.com.br')->get('users')->num_rows() === 0){ //verifica se tem no users, se entrar aqui insere
			$userestag1 = $this->db->query("
        INSERT users
        	(id, username, password, email, firstname, 
            lastname, phone, gender, company_id, parent_id, previous_passwords, 
         	password_agidesk, token_agidesk, last_login_date, active, store_id, date_create, provider_id, 
          	last_change_password, bank, agency, account_type, account, associate_type, token_agidesk_conectala, 
         	password_agidesk_conectala, legal_administrator, cpf, external_authentication_id)
         VALUES(0, 'paulosilva@conectala.com.br', 'senha', 'paulosilva@conectala.com.br', 'Paulo', 'Victor', '91 989528078', 
         	0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
         	NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL)");

			 $this->db->query('UPDATE users set password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\' where email in 
			 (\'paulosilva@conectala.com.br\')');

			$userestag = $this->db->query("INSERT INTO user_group (user_id, group_id) 
			select distinct users.id, groups.id FROM users, `groups`
			where users.email in ('paulosilva@conectala.com.br')  
			and `groups`.group_name like '%Time_DEV_Conectala%'");

		}
		else{
			echo "usuario ja existe na tabela users";

		}

	}

	public function down()	{
	}
};