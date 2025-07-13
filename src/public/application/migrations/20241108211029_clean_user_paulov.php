<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$email = 'paulosilva@conectala.com.br';

        $user = $this->db->select('id')->from('users')->where('email', $email)->get()->row();

        if ($user) {
            $this->db->where('user_id', $user->id)->delete('user_group');
            $this->db->where('email', $email)->delete('users');

        } else {
            echo "Usuário não encontrado.";
        }

	}

	public function down()	{

	}
};