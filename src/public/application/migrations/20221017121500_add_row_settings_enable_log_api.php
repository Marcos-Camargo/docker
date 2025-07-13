<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'enable_log_api')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "enable_log_api",
                'value' => 'Quando habilitado irá gravar registros de log de APIs em log_history_api. Inative para parar de gravar',
                'status' => 2,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'enable_log_api')->delete('settings');
	}
};