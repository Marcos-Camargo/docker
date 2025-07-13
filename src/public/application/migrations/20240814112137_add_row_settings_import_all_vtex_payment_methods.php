<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'import_all_vtex_payment_methods')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "import_all_vtex_payment_methods",
                'value' => '1',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Importar todos os meios de pagamentos cadastrados na vtex',
                'description' => 'Por padrão, o sistema busca apenas os meios de pagamento que estão disponível geral na Vtex, mas alguns meios de pagamento estão atrelados á política comercial, ativando essa opção, fará com que o sistema importe todos os meios de pagamento atualmente disponível na Vtex.'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'import_all_vtex_payment_methods')->delete('settings');
	}
};