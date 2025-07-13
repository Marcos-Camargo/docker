<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'valor_declarado_correios')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "valor_declarado_correios",
                'value' => '',
                'status' => 2,
                'user_id' => 0,
                'setting_category_id' => 5,
                'friendly_name' => 'Valor Declarado Correios',
                'description' => 'Este parâmetro definirá o valor minimo da indenização dos correios (valor declarado).'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'valor_declarado_correios')->delete('settings');
	}
};