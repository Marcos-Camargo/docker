<?php defined('BASEPATH') or exit('No direct script access allowed');

return new
/**
 * Class
 * @property CI_DB_query_builder $db
 */
class extends CI_Migration {

    public function up()
    {
        $this->down();
        $attrs = $this->db->select('*')->from('attributes')->where([
            'code' => 'flavor',
            'att_type' => 'products_variation',
        ])->get()->result_array() ?? [];
        foreach ($attrs ?? [] as $k => $attr) {
            if ($k == 0) continue;
            $this->db->delete('attribute_value', [
                'attribute_parent_id' => $attr['id']
            ]);
            $this->db->delete('attributes', ['id' => $attr['id']]);
        }
        $this->db->where('module_path', 'Automation/CreateApplicationAttributes')->delete('calendar_events');
        $this->db->insert('calendar_events', [
            'title' => 'Criar/Atualizar atributos da aplicação e customizados por loja',
            'event_type' => 720,
            'start' => date('Y-m-d H:i:s'),
            'end' => '2200-12-31 23:59:00',
            'module_path' => 'Automation/CreateApplicationAttributes',
            'module_method' => 'run',
            'params' => 'null'
        ]);
        $this->db->insert('job_schedule', [
            'module_path' => "Automation/CreateApplicationAttributes",
            'module_method' => 'run',
            'params' => "null",
            'status' => 0,
            'finished' => 0,
            'date_start' => date('Y-m-d H:i:s', strtotime("+3 minutes")),
            'date_end' => null,
            'server_id' => rand(1000000000, 9999999999)
        ]);
    }

    public function down()
    {
        $this->db->where('module_path', 'Automation/CreateApplicationAttributes')->delete('calendar_events');
    }
};