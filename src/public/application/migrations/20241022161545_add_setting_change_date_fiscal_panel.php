<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $data = array(
            'name' => 'change_date_fiscal_panel',
            'value' => 'escolher a data do painel fiscal altera para a possibilidade do usuário escolher qualquer data.',
            'status' => 2,
            'user_id' => 1,
            'setting_category_id' => 3,
            'friendly_name' => 'Change Date Fiscal Panel',
            'description' => 'escolher a data do painel fiscal altera para a possibilidade do usuário escolher qualquer data.',
            'date_updated' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('settings', $data);
    }

    public function down()
    {
        $this->db->where('name', 'change_date_fiscal_panel');
        $this->db->delete('settings');
    }
};