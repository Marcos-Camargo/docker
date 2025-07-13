<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'custom_seller_id_for_import_all_vtex_payment_methods')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "custom_seller_id_for_import_all_vtex_payment_methods",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Importar todos os meios de pagamentos cadastrados na vtex a partir de um seller_id',
                'description' => 'Caso informado e o parametro import_all_vtex_payment_methods estiver ativo, o sistema irá importar os meios de pagamento do seller_id específico'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'custom_seller_id_for_import_all_vtex_payment_methods')->delete('settings');
	}
};