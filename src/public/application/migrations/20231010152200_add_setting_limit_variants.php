<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'variacao_limite')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "variacao_limite",
                'value' => 'Habilita limite de variacoes em no maximo 2.',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Limite de variacao',
                'description' => 'Limita o numero maximo de variações do produto em até duas'

            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'variacao_limite')->delete('settings');
	}
};