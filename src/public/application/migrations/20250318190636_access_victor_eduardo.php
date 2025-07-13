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
				(0, 'eduardosiqueira@conectala.com.br', 'senha', 'eduardosiqueira@conectala.com.br', 'Eduardo', 'Siqueira', '99 999999999', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(),
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL), 

				(0, 'victorlima@conectala.com.br', 
				'senha', 'victorlima@conectala.com.br', 'Victor', 'Lima', '81 988541195', 
				0, 1, 1, 'NULL', NULL, NULL, NULL, 1, 0, current_timestamp(), 
				NULL, '2023-01-01 00:00:00', NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL)");



		$hasGroup = $this->db->query("SELECT id FROM `groups` WHERE group_name LIKE '%Time_DEV_Conectala%'")->row();
		if ($hasGroup) {
			//adiciona nos ambientes de prd
			$groupId = $hasGroup->id;
			$this->db->query("
				INSERT INTO user_group (user_id, group_id)
				SELECT DISTINCT users.id, ?
				FROM users
				JOIN `groups` ON `groups`.id = ?
				LEFT JOIN user_group ON users.id = user_group.user_id AND user_group.group_id = `groups`.id
				WHERE users.email IN ('eduardosiqueira@conectala.com.br', 'victorlima@conectala.com.br')
				AND user_group.user_id IS NULL", array($groupId, $groupId));

		} else {
			//adiciona nos ambientes de hmlg
			$hasGroup = $this->db->query("SELECT id FROM `groups` WHERE group_name LIKE '%TimeConectaLá%'")->row();
			if ($hasGroup) {
				$groupId = $hasGroup->id;
				$this->db->query("
					INSERT INTO user_group (user_id, group_id)
					SELECT DISTINCT users.id, ?
					FROM users
					JOIN `groups` ON `groups`.id = ?
					LEFT JOIN user_group ON users.id = user_group.user_id AND user_group.group_id = `groups`.id
					WHERE users.email IN ('eduardosiqueira@conectala.com.br', 'victorlima@conectala.com.br')
					AND user_group.user_id IS NULL", array($groupId, $groupId));
			}
		}
	}

	public function down()	{
	}
};