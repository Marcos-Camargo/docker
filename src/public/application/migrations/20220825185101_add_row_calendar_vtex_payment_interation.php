<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $query = $this->db->where('module_path', 'SellerCenter/Vtex/VtexOrders')->get('calendar_events')->result_object();

        if (count($query)) {
            foreach ($query as $mkt) {
                $this->db->insert('calendar_events', array(
                    'title' => "Baixar interações de pagamento dos pedidos VTEX $mkt->params",
                    'event_type' => '30',
                    'start' => '2022-08-25 03:00:00',
                    'end' => '2200-12-31 23:59:00',
                    'module_path' => 'SellerCenter/Vtex/VtexPaymentInteration',
                    'module_method' => 'run',
                    'params' => $mkt->params
                ));
            }
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'SellerCenter/Vtex/VtexPaymentInteration')->delete('calendar_events');
	}
};