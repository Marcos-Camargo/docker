<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'days_of_week_window_maintenance_batch')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "days_of_week_window_maintenance_batch",
                'value' => '2',
                'status' => 1,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Dias da semana da janela de manutenção.',
                'description' => 'Informe os dias da semana da janela de manutenção, separados por vírgula, sendo o domingo=0, segunda=1 e assim sucessivamente.'
            ));
        }
        if ($this->db->where('name', 'start_time_window_maintenance_batch')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "start_time_window_maintenance_batch",
                'value' => '04:00:00',
                'status' => 1,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Hora inicial da janela de manutenção.',
                'description' => 'Informe a hora inicial da janela de manutenção, com hora, minuto e segundo, hh:mm:ss.'
            ));
        }
        if ($this->db->where('name', 'end_time_window_maintenance_batch')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "end_time_window_maintenance_batch",
                'value' => '05:00:00',
                'status' => 1,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Hora final da janela de manutenção.',
                'description' => 'Informe a hora final da janela de manutenção, com hora, minuto e segundo, hh:mm:ss.'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'days_of_week_window_maintenance_batch')->delete('settings');
        $this->db->where('name', 'start_time_window_maintenance_batch')->delete('settings');
        $this->db->where('name', 'end_time_window_maintenance_batch')->delete('settings');
	}
};