<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'enable_log_quotes')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "enable_log_quotes",
                'value' => 'Quando habilitado irá gravar registros de log de APIs em log_quotes. Inative para parar de gravar',
                'status' => 1,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'enable_log_quotes')->delete('settings');
	}
};