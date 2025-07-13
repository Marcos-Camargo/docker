<?php defined('BASEPATH') OR exit('No direct script access allowed');
return new class extends CI_Migration
{
    public function up()
    {
        $this->db->where('name', 'largura_fora_caixa');
        $query = $this->db->get('settings');
        if ($query->num_rows() == 0) {
            $data = array(
                'name' => 'largura_fora_caixa',
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Largura do produto fora da caixa',
                'description' => 'Altere o status desse parâmetro para ativo e adicione o nome do atributo, para que seus valores sejam enviados ao marketplace',
                'date_updated' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('settings', $data);
        }
    }
    public function down()
    {
        //$this->db->where('name', 'largura_fora_caixa');
        //$this->db->delete('settings');
    }
};