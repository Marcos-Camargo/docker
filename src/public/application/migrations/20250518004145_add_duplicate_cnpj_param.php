<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'allow_duplicate_cnpj')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "allow_duplicate_cnpj",
                'value' => 'Permite criação de lojas com CNPJ já utilizado.',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Permite criação de lojas com CNPJ já utilizado.',
                'description' => 'Se ativo, permite que lojas sejam criadas com CNPJs duplicados.'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'allow_duplicate_cnpj')->delete('settings');
	}
};;