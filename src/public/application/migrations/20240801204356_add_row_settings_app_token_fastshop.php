<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'app_token_fastshop')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "app_token_fastshop",
                'value' => '-',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'App Token integração com Fastshop',
                'description' => 'App Token utilizado na integração com Fastshop para notificação de pagamento, cancelamento, devolução e conciliação.'

            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'app_token_fastshop')->delete('settings');
	}
};