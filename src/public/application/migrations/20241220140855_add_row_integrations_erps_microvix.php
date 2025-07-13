<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        // Inserir a nova integração na tabela integration_erps
        $this->db->query(
            "INSERT INTO `integration_erps` ( `name`, `description`, `type`, `hash`, `active`, `visible`, `support_link`, `configuration_form`, `configuration`, `image`, `provider_id`, `user_created`, `user_updated`, `date_created`, `date_updated`) " .
            "VALUES ( " .
            "'linx_microvix', " .
            "'Linx Microvix', " .
            "1, " .
            "'', " .
            "1, " .
            "1, " .
            "NULL, " .
            "'{" .
            "\"api_url_entry\": {\"name\": \"api_url\", \"label\": \"URL Entrada Webservice\", \"type\": \"text\"}, " .
            "\"api_url_exit\": {\"name\": \"api_url_exit\", \"label\": \"URL Saída Webservice\", \"type\": \"text\"}, " .
            "\"user\": {\"name\": \"user\", \"label\": \"Usuário\", \"type\": \"text\"}, " .
            "\"password\": {\"name\": \"password\", \"label\": \"Senha\", \"type\": \"password\"}, " .
            "\"access_key\": {\"name\": \"access_key\", \"label\": \"Chave de Acesso\", \"type\": \"text\"}, " .
            "\"min_stock_quantity\": {\"name\": \"min_stock_quantity\", \"label\": \"Quantidade Mínima de Estoque\", \"type\": \"number\"} " .
            "}', " .
            "NULL, " .
            "'microvix.png', " .
            "NULL, " .
            "NULL, " .
            "NULL, " .
            "'" . date('Y-m-d H:i:s') . "', " .
            "'" . date('Y-m-d H:i:s') . "');"
        );
    }

    public function down()
    {
        // Remover a integração microvix
        $this->db->query("DELETE FROM integration_erps WHERE `name` = 'linx_microvix';");
    }
};
