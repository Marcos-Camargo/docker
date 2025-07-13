<?php

require 'system/libraries/Vendor/autoload.php';

class DB_ExportApiIntegrations extends BatchBackground_Controller
{   
    private $integrations = array(
        "precode"                   => "1",
        "pluggto"                   => "2",
        "anymarket"                 => "3",
        "vtex"                      => "4",
        "tiny"                      => "5",
        "hub2b"                     => "6",
        "viavarejo_b2b"             => "7",
        "viavarejo_b2b_casasbahia"  => "7",
        "viavarejo_b2b_extra"       => "7",
        "viavarejo_b2b_pontofrio"   => "7",
        "tray"                      => "8"
    );

    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' 		=> 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' 	=> 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return ;
        }

        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");


        $this->processExport("Conectala2020#");
        
        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function getStatus($store_id, $integration_erp_id)
    {
        $sql = 'SELECT credentials
            FROM api_integrations
            WHERE integration_erp_id = ?';
        $query = $this->db->query($sql, array($integration_erp_id));
        $row = $query->row_array();
        return $row['credentials'];
    }

    private function processExport($params)
    {
        $offset = 0;
        
        // parte nova para conectar no banco do MS
        $this->load->model("model_settings");
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
		if ($settingSellerCenter) {
            $sellercenter = $settingSellerCenter['value'];
        }
		else {
			echo "não achei o sellercenter\n";
			die;			
		}

        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.17.106';
        } else {
            $hostdb = '10.151.100.164';
        }
        $database = 'ms_shipping_integrator_'.$sellercenter;

        $db_migra = [
            'dsn'   => '',
            'hostname' => $hostdb,
            'username' => 'admin',
            'password' => $params,
            'database' => $database ,
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => false,
            'db_debug'  => true,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt'  => FALSE,
            'compress' => FALSE,
            'stricton' => FALSE,
            'failover' => array(),
            'save_queries' => TRUE
    
        ];
        $db_ms = $this->load->database($db_migra,TRUE);

        if ($this->conta('configures', $db_ms) > 0) {
            echo "Tabelas configures já contém registro em Shipping Integrator. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }

        $this->conta('integration_logistic', $this->db);
        $this->conta('api_integrations', $this->db);
        // fim da parte nova

        while (true) {
            $query = $this->db->query(
                "SELECT integration_logistic.id, integration_logistic.store_id, api_integrations.integration AS api_integration, integration_logistic.integration,integration_logistic.active, api_integrations.credentials, integration_logistic.credentials as log_credentials, integration_logistic.date_created, integration_logistic.date_updated
                FROM integration_logistic
                LEFT JOIN api_integrations ON integration_logistic.store_id = api_integrations.store_id
                WHERE integration_logistic.integration NOT IN (?,?,?,?,?)
                ORDER BY integration_logistic.id ASC
                LIMIT ?, 50000",
            array('sgpweb','intelipost','freterapido','sequoia','correios',$offset));

            $results = $query->result_array();
            echo "Processando iniciando em ".$offset." lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            $insert = [];
            foreach ($results as $result) {
                $id                 = $result["id"];
                $store_id           = $result["store_id"];
                $integration_name   = addslashes($result["integration"]);
                $credentials        = $result['log_credentials'];

                // A credencial está na tabela integration_logistic.
                if (!is_null($result['credentials'])) {
                    $credentials = $result['credentials'];
                }
                
                if (in_array(
                    $result['api_integration'],
                    array(
                        'viavarejo_b2b_casasbahia',
                        'viavarejo_b2b_pontofrio',
                        'viavarejo_b2b_extra'
                    ))
                ) {
                    $result['api_integration'] = 'viavarejo_b2b';
                }

                // A logística é diferente da integração de produto.
                if (!is_null($result['api_integration']) && $result['integration'] != 'precode' && $result['api_integration'] != $result['integration']) {
                    $credentials = '[]';
                }
                
                if (array_key_exists($integration_name, $this->integrations)) {
                    $integration_id = $this->integrations[$integration_name];
                } else{
                    echo "Falhou na busca pelas credencias na integração: ".$integration_name." store_id: ".$store_id."\n";
                    continue;
                }

                $created_at = $result["date_created"];
                $updated_at = $result["date_updated"];

                $insert[] = [
                    'id'                => $id,
                    'integration_name'  => $integration_name,
                    'integration_id'    => $integration_id,
                    'credentials'       => $credentials,
                    'store_id'          => $store_id,
                    'active'            => $result["active"] == 1 ? 1 : 0,
                    'created_at'        => $created_at,
                    'updated_at'        => $updated_at
                ];
                
            }

            if (!empty($insert)) {
                $db_ms->insert_batch('configures', $insert);
            }

            $db_ms->update('integrations', ['use_sellercenter' => '0', 'use_seller' => '1', 'active' => '1']);
        }
        $this->conta('configures', $db_ms);
    }

    function conta($table, $dbvar) {
        if ($dbvar->database == 'conectala') {
            echo "[Monolito] ";
        } else {
            echo "[MS] ";
        }

        $ret = $dbvar->query('select count(*) as cnt from '.$table);
        $cnt = $ret->row_array();        
        echo "tabela ".$table." com ".$cnt['cnt']." registros\n";
        return $cnt['cnt'];
    }
}
