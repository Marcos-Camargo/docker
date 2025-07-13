<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        $this->db->query('SET SESSION foreign_key_checks  = 0;');
        $integrations_logistic = [
            [
                "name" => "sgpweb",
                "description" => "SGP Web",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 0,
                "fields_form" => "{\"token\":{\"name\":\"application_token\",\"type\":\"text\"},\"cart\":{\"name\":\"application_card\",\"type\":\"text\"},\"contract\":{\"name\":\"application_contract\",\"type\":\"text\"},\"type_contract\":{\"name\":\"application_type_contract\",\"type\":\"radio\",\"values\":{\"application_old\":\"old\",\"application_new\":\"new\"}},\"type_integration\":{\"name\":\"application_integration\",\"type\":\"radio\",\"values\":{\"application_sgpweb\":\"sgpweb\",\"application_gestaoenvios\":\"gestaoenvios\"}}}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2023-01-20 19:03:38",
                "date_created" => date('Y-m-d H:i:s'), //"2021-04-27 15:17:40"
            ],
            [
                "name" => "intelipost",
                "description" => "Intelipost",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 0,
                "fields_form" => "{\"account_id\":{\"name\":\"application_own_logistic_code\",\"type\":\"number\"},\"token\":{\"name\":\"application_token\",\"type\":\"text\"}}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2023-01-20 13:18:04",
                "date_created" => date('Y-m-d H:i:s'), //"2021-04-27 15:17:55"
            ],
            [
                "name" => "freterapido",
                "description" => "Frete Rápido",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 0,
                "fields_form" => "{\"token\":{\"name\":\"application_token\",\"type\":\"text\"}}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2023-01-20 18:53:57",
                "date_created" => date('Y-m-d H:i:s'), //"2021-04-27 15:18:07"
            ],
            [
                "name" => "sequoia",
                "description" => "Sequoia",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 0,
                "fields_form" => "{\"token\":{\"name\":\"application_token\",\"type\":\"text\"},\"client_id\":{\"name\":\"application_client_id\",\"type\":\"text\"},\"hash_quote\":{\"name\":\"application_quotation_token\",\"type\":\"text\"}}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2023-01-23 16:44:42",
                "date_created" => date('Y-m-d H:i:s'), //"2021-07-21 15:00:00"
            ],
            [
                "name" => "precode",
                "description" => "Precode",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{\"endpoint\":{\"name\":\"application_own_logistic_endpoint\",\"type\":\"url\"}}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2023-01-27 16:36:32",
                "date_created" => date('Y-m-d H:i:s'), //"2021-07-21 15:00:00"
            ],
            [
                "name" => "pluggto",
                "description" => "Plugg.To",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2022-12-07 16:57:46",
                "date_created" => date('Y-m-d H:i:s'), //"2021-07-21 15:00:00"
            ],
            [
                "name" => "anymarket",
                "description" => "AnyMarket",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2023-01-23 16:44:42",
                "date_created" => date('Y-m-d H:i:s'), //"2021-07-21 15:00:00"
            ],
            [
                "name" => "vtex",
                "description" => "VTEX",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2022-08-25 13:37:05",
                "date_created" => date('Y-m-d H:i:s'), //"2021-07-21 15:00:00"
            ],
            [
                "name" => "viavarejo_b2b",
                "description" => "Via Varejo",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2022-10-06 19:59:57",
                "date_created" => date('Y-m-d H:i:s'), //"2022-03-31 19:39:56"
            ],
            [
                "name" => "tray",
                "description" => "Tray",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2022-08-25 13:33:58",
                "date_created" => date('Y-m-d H:i:s'), //"2022-06-07 13:00:28"
            ],
            [
                "name" => "tiny",
                "description" => "Tiny",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2022-08-24 17:46:07",
                "date_created" => date('Y-m-d H:i:s'), //"2022-08-09 23:39:18"
            ],
            [
                "name" => "hub2b",
                "description" => "Hub2b",
                "use_sellercenter" => 0,
                "use_seller" => 1,
                "active" => 1,
                "only_store" => 1,
                "fields_form" => "{}",
                "user_created" => null,
                "user_updated" => 1,
                "date_updated" => date('Y-m-d H:i:s'), //"2022-08-24 17:46:07",
                "date_created" => date('Y-m-d H:i:s'), //"2022-08-24 17:35:12"
            ]
        ];

        foreach ($integrations_logistic as $integration_logistic) {
            $integrations       = $this->db->where('name', $integration_logistic['name'])->get('integrations_logistic')->result_array();
            $qty_integartions   = count($integrations);

            // se não tiver, cria
            if ($qty_integartions === 0) {
                $this->db->insert('integrations_logistic', $integration_logistic);
            }
            // se tiver, mas tiver duplicado
            else if ($qty_integartions > 1) {

                // salva o ID do primeiro
                $id_integration = $integrations[0]['id'];

                // Define para todos da tabela integration_logistic
                $this->db->where(['integration' => $integration_logistic['name'], 'id_integration !=' => $id_integration])->update("integration_logistic", array('id_integration' => $id_integration));

                // remove os outros.
                $this->db->where(['name' => $integration_logistic['name'], 'id !=' => $id_integration])->delete('integrations_logistic');
            }
        }

        // Se for 'erp' migrar para a integração do seller.
        $integrations = $this->db->where('integration', 'erp')->get('integration_logistic')->result_array();
        foreach ($integrations as $integration) {
            // É integração com marketplace.
            if ($integration['store_id'] == 0) {
                continue;
            }

            // Consulta a integração da loja.
            if ($api_integration = $this->db->where('store_id', $integration['store_id'])->get('api_integrations')->row_array()) {
                // Verifica se é algum erp que tem logística
                if (in_array($api_integration['integration'], array(
                    'pluggto',
                    'anymarket',
                    'viavarejo_b2b_casasbahia',
                    'viavarejo_b2b_pontofrio',
                    'viavarejo_b2b_extra',
                    'tray',
                    'tiny',
                    'hub2b',
                    'precode',
                    'vtex'
                ))) {
                    // Se for via, altero para deixar somente o nomme genérico, sem o sufixo.
                    if (in_array($api_integration['integration'], array(
                        'viavarejo_b2b_casasbahia',
                        'viavarejo_b2b_pontofrio',
                        'viavarejo_b2b_extra')
                    )) {
                        $api_integration['integration'] = 'viavarejo_b2b';
                    }

                    $logistic_integration = $this->db->where('name', $api_integration['integration'])->get('integrations_logistic')->row_array();
                    // encontrou a integração logística pela integração do erp.
                    if ($logistic_integration) {
                        $id_integration = $logistic_integration['id'];
                        $integration    = $logistic_integration['name'];

                        // Atualiza de ERP, para o ERP da loja.
                        $this->db->where(['integration' => 'erp', 'store_id' =>  $api_integration['store_id']])->update("integration_logistic", array(
                            'id_integration' => $id_integration,
                            'integration'    => $integration
                        ));
                    } else {
                        // Não encontrou a logística, então exclui.
                        $this->db->where(['integration' => 'erp', 'store_id' => $api_integration['store_id']])->delete('integration_logistic');
                    }
                } else {
                    // A logística não é ERP
                    $this->db->where(['integration' => 'erp', 'store_id' => $integration['store_id']])->delete('integration_logistic');
                }
            } else {
                // Não tem integração, então não funciona, só excluir.
                $this->db->where(['integration' => 'erp', 'store_id' => $integration['store_id']])->delete('integration_logistic');
            }
        }
        // exclui ERP
        $this->db->where(['name' => 'erp'])->delete('integrations_logistic');
    }

    public function down()	{
        //$this->db->where('name', 'metabase_key')->delete('settings');
    }
};