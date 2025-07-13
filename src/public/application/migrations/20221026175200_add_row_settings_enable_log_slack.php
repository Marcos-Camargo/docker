<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'enable_log_slack')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "enable_log_slack",
                'value' => 'Quando habilitado irá gravar registros de log no Slack. Inative para parar de gravar',
                'status' => in_array(ENVIRONMENT, array('production', 'production_x')) ? 1: 2,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'enable_log_slack')->delete('settings');
	}
};