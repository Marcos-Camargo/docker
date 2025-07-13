<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'promissory_payment_method_id')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "promissory_payment_method_id",
                'value' => '-',
                'status' => 2,
                'user_id' => 0,
                'setting_category_id' => 4,
                'friendly_name' => 'Quando Ativo executar a requisição Vtex Notes',
                'description' => 'Quando Ativo com valor diferente dos ID da coluna method_id da tabela vtex_payment_methods ou valor vazio, deve permanecer o fluxo atual do seller center.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'promissory_payment_method_id')->delete('settings');
	}
};