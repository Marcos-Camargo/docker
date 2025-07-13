<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Microservice/Shipping/Seller')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Atualizar dados das lojas no microsserviço Shipping",
                'event_type' => '5',
                'start' => '2024-02-19 00:00:00',
                'end' => '2023-12-31 23:59:59',
                'module_path' => 'Microservice/Shipping/Seller',
                'module_method' => 'run',
                'params' => '9'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Microservice/Shipping/Seller'));
	}
};