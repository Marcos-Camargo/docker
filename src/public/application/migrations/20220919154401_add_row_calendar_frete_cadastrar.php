<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'FreteCadastrar')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Cadastrar lojas nas plataformas logísticas.",
                'event_type' => '60',
                'start' => '2022-09-19 03:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'FreteCadastrar',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'FreteCadastrar')->delete('calendar_events');
	}
};