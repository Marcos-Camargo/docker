<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$sourceGroupPatterns = [
			'%Time_DEV_Conectala%',
			'%3B - Time DEV Conecta Lá%',
			'%TimeConectaLá%'
		];

		$destinationGroup = null;
		$destinationPatterns = [
			'%Time_Admin_Conectala%',
			'%Admin_ConectaLa%',
			'%3- Administrador ConectaLa%'
		];

		$additionalEmails = [
			'agathateixeira@conectala.com.br',
			'higoralves@conectala.com.br',
			'pedrobraga@conectala.com.br',
			'gabrielbarboza@conectala.com.br',
			'brunacaldieri@conectala.com.br',
			'brunacosta@conectala.com.br',
			'vandressascheron@conectala.com.br'
		];

		foreach ($destinationPatterns as $pattern) {
			$group = $this->db->query("SELECT id FROM `groups` WHERE group_name LIKE ?", [$pattern])->row();
			if ($group) {
				$destinationGroup = $group;
				break;
			}
		}

		if (!$destinationGroup) {
			echo "Grupo de destino não encontrado.";
			return;
		}

		$destinationGroupId = $destinationGroup->id;

		foreach ($sourceGroupPatterns as $pattern) {
			$query = $this->db->query("
				SELECT DISTINCT ug.user_id
				FROM user_group ug
				JOIN `groups` g ON g.id = ug.group_id
				WHERE g.group_name LIKE ?
			", [$pattern]);

			foreach ($query->result() as $row) {
				$userId = $row->user_id;

				$alreadyInGroup = $this->db->query("
					SELECT 1 FROM user_group 
					WHERE user_id = ? AND group_id = ?
				", [$userId, $destinationGroupId])->num_rows();

				if (!$alreadyInGroup) {
					$this->db->insert('user_group', [
						'user_id' => $userId,
						'group_id' => $destinationGroupId
					]);
				}
			}
		}

		foreach ($additionalEmails as $email) {
			$user = $this->db->get_where('users', ['email' => $email])->row();
			if ($user) {
				$userId = $user->id;

				$alreadyInGroup = $this->db->query("
					SELECT 1 FROM user_group 
					WHERE user_id = ? AND group_id = ?
				", [$userId, $destinationGroupId])->num_rows();

				if (!$alreadyInGroup) {
					$this->db->insert('user_group', [
						'user_id' => $userId,
						'group_id' => $destinationGroupId
					]);
				}
			}
		}
	}

	public function down() {
		$sourceGroupPatterns = [
			'%Time_DEV_Conectala%',
			'%3B - Time DEV Conecta Lá%',
			'%TimeConectaLá%'
		];

		$destinationGroup = null;
		$destinationPatterns = [
			'%Time_Admin_Conectala%',
			'%Admin_ConectaLa%',
			'%3- Administrador ConectaLa%'
		];

		// E-mails adicionais informados
		$additionalEmails = [
			'agathateixeira@conectala.com.br',
			'higoralves@conectala.com.br',
			'pedrobraga@conectala.com.br',
			'gabrielbarboza@conectala.com.br',
			'brunacaldieri@conectala.com.br',
			'brunacosta@conectala.com.br',
			'vandressascheron@conectala.com.br'
		];

		foreach ($destinationPatterns as $pattern) {
			$group = $this->db->query("SELECT id FROM `groups` WHERE group_name LIKE ?", [$pattern])->row();
			if ($group) {
				$destinationGroup = $group;
				break;
			}
		}

		if (!$destinationGroup) {
			echo "Grupo de destino não encontrado.";
			return;
		}

		$destinationGroupId = $destinationGroup->id;

		foreach ($sourceGroupPatterns as $pattern) {
			$query = $this->db->query("
				SELECT DISTINCT ug.user_id
				FROM user_group ug
				JOIN `groups` g ON g.id = ug.group_id
				WHERE g.group_name LIKE ?
			", [$pattern]);

			foreach ($query->result() as $row) {
				$this->db->delete('user_group', [
					'user_id' => $row->user_id,
					'group_id' => $destinationGroupId
				]);
			}
		}

		foreach ($additionalEmails as $email) {
			$user = $this->db->get_where('users', ['email' => $email])->row();
			if ($user) {
				$this->db->delete('user_group', [
					'user_id' => $user->id,
					'group_id' => $destinationGroupId
				]);
			}
		}
	}
};