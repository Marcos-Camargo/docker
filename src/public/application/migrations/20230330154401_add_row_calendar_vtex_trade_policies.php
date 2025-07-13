<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Vtex/CampaignsV2')->where('module_method', 'updateTradePolicies')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Importar políticas comerciais da VTEX",
                'event_type' => '60',
                'start' => '2022-09-19 00:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'Vtex/CampaignsV2',
                'module_method' => 'updateTradePolicies',
                'params' => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'Vtex/CampaignsV2')->where('module_method', 'updateTradePolicies')->delete('calendar_events');
	}
};