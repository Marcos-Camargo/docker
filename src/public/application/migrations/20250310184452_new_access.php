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
				(0, 'rogersoares@conectala.com.br', 'senha', 'rogersoares@conectala.com.br', 'Roger', 'Soares', '31 986052763', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(),
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL), 

				(0, 'jacquelinebonifacio@conectala.com.br', 
				'senha', 'jacquelinebonifacio@conectala.com.br', 'Jacqueline', 'Bonifacio', '11 981224319', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL), 

				(0, 'joaocruz@conectala.com.br', 
				'senha', 'joaocruz@conectala.com.br', 'Joao', 'Cruz', '48 991478374', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL)");


		$this->db->query('UPDATE users set password = \'$2y$10$o1zF.07E4Hhs4OTSOMSa9OueNqq432KkdfI9wKK3aHnn.1PSl43SW\' where email in 
				(\'rogersoares@conectala.com.br\', \'jacquelinebonifacio@conectala.com.br\', \'joaocruz@conectala.com.br\')');



		$this->db->query("
		DELETE FROM user_group 
		WHERE user_id = (SELECT id FROM users WHERE email = 'gabrielbarboza@conectala.com.br')");

		$this->db->query("INSERT INTO user_group (user_id, group_id) 
						select distinct users.id, groups.id FROM users, `groups`
						where users.email in ('gabrielbarboza@conectala.com.br')  and `groups`.group_name like '%Time_CS_Conectala%'");

		//Time_CS_Conectala
		$this->db->query("INSERT INTO user_group (user_id, group_id) 
						select distinct users.id, groups.id FROM users, `groups`
						where users.email in ('jacquelinebonifacio@conectala.com.br')  and `groups`.group_name like '%Time_CS_Conectala%'");



		$hasGroup = $this->db->query("SELECT id FROM `groups` WHERE group_name LIKE '%Time_DEV_Conectala%'")->row();
		if ($hasGroup) {
			//adiciona nos ambientes de prd
			$groupId = $hasGroup->id;
			$this->db->query("
				UPDATE user_group ug
				JOIN users u ON ug.user_id = u.id
				SET ug.group_id = ?
				WHERE u.email IN ('gustavokuhn@conectala.com.br', 'thaynaraxavier@conectala.com.br')
			", array($groupId));
			$this->db->query("
				INSERT INTO user_group (user_id, group_id)
				SELECT DISTINCT users.id, ?
				FROM users
				JOIN `groups` ON `groups`.id = ?
				LEFT JOIN user_group ON users.id = user_group.user_id AND user_group.group_id = `groups`.id
				WHERE users.email IN ('rogersoares@conectala.com.br', 'joaocruz@conectala.com.br')
				AND user_group.user_id IS NULL", array($groupId, $groupId));

		} else {
			//adiciona nos ambientes de hmlg
			$hasGroup = $this->db->query("SELECT id FROM `groups` WHERE group_name LIKE '%TimeConectaLá%'")->row();

			if ($hasGroup) {
				$groupId = $hasGroup->id;
				$this->db->query("
				UPDATE user_group ug
				JOIN users u ON ug.user_id = u.id
				SET ug.group_id = ?
				WHERE u.email IN ('gustavokuhn@conectala.com.br', 'thaynaraxavier@conectala.com.br')
			", array($groupId));

				$this->db->query("
					INSERT INTO user_group (user_id, group_id)
					SELECT DISTINCT users.id, ?
					FROM users
					JOIN `groups` ON `groups`.id = ?
					LEFT JOIN user_group ON users.id = user_group.user_id AND user_group.group_id = `groups`.id
					WHERE users.email IN ('rogersoares@conectala.com.br', 'joaocruz@conectala.com.br')
					AND user_group.user_id IS NULL", array($groupId, $groupId));
			}
		}
	}

	public function down()	{
	}
};