<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'tracking_url_default')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "tracking_url_default",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 5,
                'friendly_name' => 'Url de rastreio padrão',
                'description' => 'Este parâmetro utilizará a url definida no campo valor para preencher a url de rastreio na atualização de pedidos para enviado quando não for enviada uma url pelas integrações que utilizam a API.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'tracking_url_default')->delete('settings');
	}
};