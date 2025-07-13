<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        // Atualiza o configuration_form deixando apenas o campo token_microvix
        $this->db->where('name', 'linx_microvix')->update('integration_erps', [
            'configuration_form' => json_encode([
                'token_microvix' => [
                    'name' => 'token_microvix',
                    'label' => 'Chave',
                    'type' => 'text',
                ]
            ], JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function down()
    {
        // Restaura a configuração original completa
        $this->db->query(
            "UPDATE `integration_erps` 
         SET `configuration_form` = '{
            \"api_url_entry\": {\"name\": \"api_url\", \"label\": \"URL Entrada Webservice\", \"type\": \"text\"},
            \"api_url_exit\": {\"name\": \"api_url_exit\", \"label\": \"URL Saída Webservice\", \"type\": \"text\"},
            \"user\": {\"name\": \"user\", \"label\": \"Usuário\", \"type\": \"text\"},
            \"password\": {\"name\": \"password\", \"label\": \"Senha\", \"type\": \"password\"},
            \"access_key\": {\"name\": \"access_key\", \"label\": \"Chave de Acesso\", \"type\": \"text\"},
            \"min_stock_quantity\": {\"name\": \"min_stock_quantity\", \"label\": \"Quantidade Mínima de Estoque\", \"type\": \"number\"}
         }'
         WHERE `name` = 'linx_microvix';"
        );
    }
};
