<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if (strtotime('2024-11-30 00:00:00') < strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {
            return;
        }

        $module_paths = array(
            'SellerCenter/Vtex/VtexOrders',
            'SellerCenter/Vtex/VtexOrdersStatus',
            'SellerCenter/Wake/WakeOrders',
            'SellerCenter/Wake/WakeOrdersStatus',
            'SellerCenter/OCC/OccOrdersStatus',
            'SellerCenter/OCC/OccOrdersSync',
            'Marketplace/Conectala/OrdersStatus',
            'Marketplace/Conectala/GetOrders',
        );

        $calendar_events = $this->db->where_in('module_path', $module_paths)->get('calendar_events')->result_array();
        foreach ($calendar_events as $calendar_event) {
            $start = date('Y-m-d', strtotime($calendar_event['start'])) . ' 00:10:00';
            $this->db->update('calendar_events', array('start' => $start), array('ID' => $calendar_event['ID']));
        }
	}

	public function down()	{
	}
};