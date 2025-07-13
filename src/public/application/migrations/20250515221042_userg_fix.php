<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$emails = [
			'higoralves@conectala.com.br',
			'pedrobraga@conectala.com.br',
			'gabrielbarboza@conectala.com.br',
			'brunacaldieri@conectala.com.br',
			'brunacosta@conectala.com.br',
			'vandressascheron@conectala.com.br',
			'eduardosiqueira@conectala.com.br',
			'carlossantos@conectala.com.br',
			'victorlima@conectala.com.br',
			'gustavofeijo@conectala.com.br',
			'gustavokuhn@conectala.com.br',
			'joaocruz@conectala.com.br',
			'marcoscamargo@conectala.com.br',
			'milenabarros@conectala.com.br',
			'rogersoares@conectala.com.br',
			'thaynaraxavier@conectala.com.br',
			'arthurbastos@conectala.com.br',
			'augustobraun@conectala.com.br',
			'dilneispancerski@conectala.com.br',
			'fabioribeiro@conectala.com.br'
		];

		$destGroupId = null;
		$group = $this->db
			->select('id')
			->from('groups')
			->like('group_name', 'Time_Admin_Conectala','both')
			->or_like('group_name', 'Admin_ConectaLa','both')
			->or_like('group_name', '3- Administrador ConectaLa','both')
			->get()
			->row();

		$destGroupId = $group ? $group->id : null;

		if (!$destGroupId) {
			echo "Grupo de destino não encontrado. Abortando.\n";
			return;
		}

		foreach ($emails as $email) {
			$user = $this->db->get_where('users', ['email' => $email])->row();
			if (!$user) continue;
			$userId = $user->id;

			$this->db
				->where('user_id', $userId)
				->where('group_id !=', $destGroupId)
				->delete('user_group');

			$memberships = $this->db
				->select('id')
				->from('user_group')
				->where(['user_id' => $userId, 'group_id' => $destGroupId])
				->order_by('id', 'ASC')
				->get()
				->result_array();

			if (count($memberships) > 1) {
				$keep = array_shift($memberships);
				$deleteIds = array_column($memberships, 'id');
				$this->db->where_in('id', $deleteIds)->delete('user_group');
			}
		}

		echo "Limpeza de duplicatas em user_group concluída.\n";
	}

	public function down()	{
	}
};