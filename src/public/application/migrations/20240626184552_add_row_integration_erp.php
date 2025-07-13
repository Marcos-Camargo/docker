<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'bling_v3')->get('integration_erps')->num_rows() === 0) {
            $this->db->insert('integration_erps', array(
                'name' => "bling_v3",
                'description' => "Bling v3",
                'type' => 1,
                'hash' => "f05f79fdcb60186d3149d36514c0cc0ed5110865",
                'active' => 1,
                'visible' => 0,
                'support_link' => '[{"title":"Como integrar a URL de callback?","link":"https://ajuda.bling.com.br/hc/pt-br/articles/360046387754-Callback-de-altera%C3%A7%C3%A3o-de-estoque-API"}]',
                'configuration_form' => '{"homologation_link":{"name":"Integrations/homologation/bling_v3","label":"Link de Homologação","type":"link"},"client_id":{"name":"client_id","label":"Client Id","type":"text"},"client_secret":{"name":"client_secret","label":"Client Secret","type":"text"}}',
                'configuration' => NULL,
                'image' => "bling_v3.png",
                'date_created' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL),
                'date_updated' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)
            ));
        }
    }

	public function down()	{
        $this->db->delete('integration_erps', array('name' => 'bling_v3'));
	}
};