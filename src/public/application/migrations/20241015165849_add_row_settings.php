<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'return_net_product_fee_api')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "return_net_product_fee_api",
                'value' => 'Retornar valor líquido da taxa do produto na API',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Retornar valor líquido da taxa do produto na API',
                'description' => 'Retornar valor líquido da taxa do produto. [Detalhamento de Taxas] (Valor Comissão Produto + Valor Comissão Campanha - Reembolso Marketplace)'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'return_net_product_fee_api')->delete('settings');
	}
};