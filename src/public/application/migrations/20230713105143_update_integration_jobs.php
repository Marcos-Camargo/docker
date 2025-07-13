<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {
    public function up()
    {
        $query = $this->db->query("SELECT * FROM settings WHERE name = 'sellercenter'");
        $sellercenter = $query->row_array()['name'] ?? '';
        if (in_array($sellercenter, ['oscarcalcados', 'youplay'])) {
            return;
        }
        $query = $this->db->query("SELECT * FROM calendar_events where module_path in ('Integration_v2/Product/bling/CreateProduct', 'Integration_v2/Product/bling/UpdateProduct')");
        $calendarEvents = $query->result_array();

        $events = [];
        foreach ($calendarEvents ?? [] as $calendarEvent) {
            $params = array_filter(explode(' ', trim($calendarEvent['params'])), function ($param) {
                return is_numeric($param);
            });
            $param = (int)substr((current($params) ?: $calendarEvent['id']), -1);
            $roundUnMinute = ($param) % 10 >= 5 ? 10 : 5;
            $roundDecMinute = (int)($param / 10);
            $mStart = $roundUnMinute == 5 ? "{$roundDecMinute}0" : "{$roundDecMinute}5";
            $groupDate = date("Y-m-d H:{$mStart}:00");

            $events[] = [
                'id' => $calendarEvent['id'] ?? $calendarEvent['ID'],
                "event_type" => 71,
                "start" => $groupDate
            ];
        }
        $chunckSize = (int)(count($events) / 24);
        $chuckEvents = array_chunk($events, $chunckSize > 0 ? $chunckSize : 1);
        foreach ($chuckEvents ?? [] as $hour => $chuckEvent) {
            foreach ($chuckEvent as $event) {
                $startDate = $event['start'];
                $event['start'] = date("Y-m-d H:i:s", strtotime("+{$hour} hours", strtotime($startDate)));
                $this->db->update('calendar_events', $event, ['ID' => $event['id']]);
            }
        }
    }

    public function down()
    {

    }
};