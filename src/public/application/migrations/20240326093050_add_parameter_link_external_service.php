<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {

        // Verifica se o parâmetro 'sac_url' existe na tabela
        if ($this->db->where('name', 'sac_url')->get('settings')->num_rows() > 0) {
            $this->db->where('name', 'sac_url')->update('settings', array(
                'name' => "link_atendimento_externo",
                'value' => '*novo_texto*, *nova_url*',
                'status' => 2,
                'user_id' => 0,
                'setting_category_id' => 6,
                'friendly_name' => 'Atendimento Externo',
                'description' => 'Link de atendimento ou pesquisa que será inserido no submenu de atendimentos, defina da seguinte forma: Nome do atendimento, url do atendimento'
            ));
        }else{
            $this->db->insert('settings', array(
                'name' => "link_atendimento_externo",
                'value' => '*texto*, *url*',
                'status' => 2,
                'user_id' => 0,
                'setting_category_id' => 6,
                'friendly_name' => 'Atendimento Externo',
                'description' => 'Link de atendimento ou pesquisa que será inserido no submenu de atendimentos, defina da seguinte forma: Nome do atendimento, url do atendimento'
            ));
        }
        
    }

    public function down() {
        // Remove o parâmetro 'link_de_atendimento_externo' se existir
        $this->db->where('name', 'link_atendimento_externo')->delete('settings');
        
        // Remove o parâmetro 'sac_url' se existir
        $this->db->where('name', 'sac_url')->delete('settings');
    }
};
