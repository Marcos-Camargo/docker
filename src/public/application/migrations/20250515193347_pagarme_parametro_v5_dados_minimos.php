<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        $this->db->where('name', 'pagarme_v5_dados_minimos');
        $query = $this->db->get('settings');
        if ($query->num_rows() == 0) {
            $data = array(
                'name' => 'pagarme_v5_dados_minimos',
                'value' => 'Parâmetro de ativação dos dados mínimos V5 Pagar.me',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Parâmetro de ativação dos dados mínimos V5 Pagar.me',
                'description' => 'Ative esse parâmetro para que as novas lojas cadastradas passem a enviar as informações de dados mínimos da Pagar.me. Cada loja é controlada via banco de dados para saber se foi criada antes ou depois da ativação do parâmetro',
                'date_updated' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('settings', $data);
        }
    }
};