<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (in_array(ENVIRONMENT, array(
            'local',
            'development',
            'development_gcp',
        ))) {
            if ($this->db->where('module_path', 'SellerCenter/Tests/UpdateStatusOrders')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
                $this->db->insert('calendar_events', array(
                    'title' => "Atualiza pedidos de testes",
                    'event_type' => '5',
                    'start' => '2023-04-18 00:00:00',
                    'end' => '2200-12-31 23:59:00',
                    'module_path' => 'SellerCenter/Tests/UpdateStatusOrders',
                    'module_method' => 'run',
                    'params' => 'null'
                ));
            }
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'SellerCenter/Tests/UpdateStatusOrders')->where('module_method', 'run')->delete('calendar_events');
	}
};