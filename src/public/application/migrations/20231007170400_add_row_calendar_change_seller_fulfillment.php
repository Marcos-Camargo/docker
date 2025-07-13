<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'MultiChannelFulfillment/ChangeSeller')) {
            $this->db->insert('calendar_events', array(
                'title'         => "Realizar troca de loja de pedido Multi CD se não faturado a tempo.",
                'event_type'    => '10',
                'start'         => '2023-10-07 04:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'MultiChannelFulfillment/ChangeSeller',
                'module_method' => 'run',
                'params'        => 'null'
            ));
        }
	}

	public function down()	{
        $this->db->where('module_path', 'MultiChannelFulfillment/ChangeSeller')->delete('calendar_events');
	}
};