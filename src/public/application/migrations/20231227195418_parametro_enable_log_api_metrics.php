<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'enable_log_api_metrics')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "enable_log_api_metrics",
                'value' => 'Habilita os logs de métrica das APIs',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Metria API',
                'description' => 'Habilita o log de métricas das APIs verificando as chamadas, tempo de execução, retornos e parâmetros enviados'

            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'enable_log_api_metrics')->delete('settings');
	}
};