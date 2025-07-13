<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if (strtotime('2024-11-29 12:00:00') < strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {
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
            $date = '2024-11-29 02:00:00';
            while (true) {
                $date = date(DATETIME_INTERNATIONAL, strtotime( '+ 10 minutes', strtotime($date)));
                if (strtotime($date) >= strtotime('2024-11-29 '.date('H:i:s', strtotime($calendar_event['start'])))) {
                    break;
                }

                $this->db->insert('job_schedule', array(
                    'module_path' => $calendar_event['module_path'],
                    'module_method' => $calendar_event['module_method'],
                    'params' => $calendar_event['params'],
                    'status' => 0,
                    'finished' => 0,
                    'error' => null,
                    'error_count' => 0,
                    'error_msg' => null,
                    'date_start' => $date,
                    'date_end' => null,
                    'server_id' => $calendar_event['ID'],
                    'alert_after' => $calendar_event['alert_after'],
                    'start_alert' => null,
                    'server_batch_ip' => null
                ));
            }
        }

	}

	public function down()	{
	}
};