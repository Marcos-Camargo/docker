<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'validate_commission_limit_for_campaign')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "validate_commission_limit_for_campaign",
                'value' => '1',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Validar comissão cobrada fora do que deve ser cobrado do lojista',
                'description' => 'Caso ativado, irá realizar as validações de que a comissão cobrada na campanha está fora do que deveria ser cobrado do lojista'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'validate_commission_limit_for_campaign')->delete('settings');
	}
};