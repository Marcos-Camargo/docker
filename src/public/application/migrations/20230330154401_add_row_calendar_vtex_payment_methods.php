<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Vtex/CampaignsV2')->where('module_method', 'updatePaymentMethods')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Importar métodos de pagamento da VTEX",
                'event_type' => '60',
                'start' => '2022-09-19 00:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'Vtex/CampaignsV2',
                'module_method' => 'updatePaymentMethods',
                'params' => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'Vtex/CampaignsV2')->where('module_method', 'updatePaymentMethods')->delete('calendar_events');
	}
};