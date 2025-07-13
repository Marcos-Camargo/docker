<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {

		$userestag1 = $this->db->query("
			INSERT users
				(id, username, password, email, firstname, 
				lastname, phone, gender, company_id, parent_id, previous_passwords, 
				password_agidesk, token_agidesk, last_login_date, active, store_id, date_create, provider_id, 
				last_change_password, bank, agency, account_type, account, associate_type, token_agidesk_conectala, 
				password_agidesk_conectala, legal_administrator, cpf, external_authentication_id)
			VALUES
				(0, 'gustavokuhn@conectala.com.br', 'senha', 'gustavokuhn@conectala.com.br', 'Gustavo', 'Kuhn', '48 991786358', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL), 

				(0, 'thaynaraxavier@conectala.com.br', 
				'senha', 'thaynaraxavier@conectala.com.br', 'Thaynara', 'Xavier', '48 988401505', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL), 

				(0, 'carlosdubaj@conectala.com.br', 
				'senha', 'carlosdubaj@conectala.com.br', 'Carlos', 'Dubaj', '48 991700250', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL),

				(0, 'gabrielbarboza@conectala.com.br', 
				'senha', 'gabrielbarboza@conectala.com.br', 'Gabriel', 'Barboza', '48 996044778', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL)");


		$this->db->query('UPDATE users set password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\' where email in 
				(\'gustavokuhn@conectala.com.br\', \'thaynaraxavier@conectala.com.br\', \'carlosdubaj@conectala.com.br\', \'gabrielbarboza@conectala.com.br\')');

		$hasGroup = $this->db->query("SELECT * FROM `groups` WHERE group_name = 'Time_DEV_Conectala'")->row();

		if ($hasGroup) {
			//adiciona nos ambientes de prd
			return $this->db->query("INSERT INTO user_group (user_id, group_id) 
						select distinct users.id, groups.id FROM users, `groups`
						where users.email in ('gustavokuhn@conectala.com.br', 'thaynaraxavier@conectala.com.br', 
											'carlosdubaj@conectala.com.br', 'gabrielbarboza@conectala.com.br')  and `groups`.group_name like '%Time_DEV_Conectala%'");
		} else {
			//adiciona nos ambientes de hmlg
			return $this->db->query("INSERT INTO user_group (user_id, group_id) 
						select distinct users.id, groups.id FROM users, `groups`
						where users.email in ('gustavokuhn@conectala.com.br', 'thaynaraxavier@conectala.com.br', 
											'carlosdubaj@conectala.com.br', 'gabrielbarboza@conectala.com.br')  and `groups`.group_name like '%TimeConectaLá%'");
		}
	}

	public function down()	{
	}
};