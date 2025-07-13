<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'cancellation_commission_calculate_campaign')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "cancellation_commission_calculate_campaign",
                'value' => 'Aplicar calculo de campanhas junto ao calculo de cancelamento e estorno',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Aplicar calculo de campanhas junto ao calculo de cancelamento e estorno',
                'description' => 'Caso esta opção seja ativada, será aplicado o seguinte calculo:     
                <b>Cancelamento Com retenção Sem Estorno</b><br>
Será realizada a cobrança integral da comissão, comissão do produto sem desconto custeado pelo canal:<br>
Comissão Produto + Comissão Desconto custedo pelo canal
<br><br>
<b>Cancelamento Com Retenção Com Estorno</b><br>
Será realizada o estorno do valor retido<br>
(Comissão Produto + Comissão Desconto custeado pelo canal) * -1'));
        }
	}

	public function down()	{
        $this->db->where('name', 'cancellation_commission_calculate_campaign')->delete('settings');
	}
};