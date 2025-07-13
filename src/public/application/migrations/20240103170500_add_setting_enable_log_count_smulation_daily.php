<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'enable_log_count_simulation_daily')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "enable_log_count_simulation_daily",
                'value' => 'Ativar job',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Ativar logs de contagem de consulta de disponibilidade do marketplace.',
                'description' => 'Logs de contagem de consulta de disponibilidade do marketplace.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'enable_log_count_simulation_daily')->delete('settings');
	}
};