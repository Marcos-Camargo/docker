<?php

require 'system/libraries/Vendor/autoload.php';

class DB_ExportIntegrationLogistic extends BatchBackground_Controller
{   
    private $integrations = array(
        "sgpweb"      => "1",
        "intelipost"  => "2",
        "freterapido" => "3",
        "sequoia"     => "4",
        "correios"    => "5"
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

        // $hostdb   = '10.150.17.106';
        // $hostdb   = '10.150.16.113';
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.23.128';
        } else {
            $hostdb = '10.151.100.67';
        }
        $database = 'ms_shipping_carrier_'.$sellercenter;

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
            echo "Tabelas configures já contém registro em Shipping Carrier. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }

        $this->conta('integration_logistic', $this->db);
        // fim da parte nova

        while (true) {
            $query = $this->db->query(
                "SELECT integration_logistic.id, integration_logistic.store_id, integration_logistic.integration, integration_logistic.credentials,integration_logistic.active, integration_logistic.date_created, integration_logistic.date_updated
                FROM integration_logistic
                WHERE integration_logistic.integration IN (?,?,?,?,?)
                LIMIT ?, 50000",
            array('sgpweb','intelipost','freterapido','sequoia','correios',$offset));

            $results = $query->result_array();
            echo "Processando iniciando em ".$offset." lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            // MsShippingCarrier configures
            $insert = [];
            foreach ($results as $result) {
                $id = $result["id"];
                $store_id = $result["store_id"];

                if ($store_id == 0) {
                    $store_id = null;
                }

                $integration_name = addslashes($result["integration"]);

                $credentials = !empty($result["credentials"]) ? $result["credentials"] : null;

                if (array_key_exists($integration_name, $this->integrations)) {
                    $integration_id = $this->integrations[$integration_name];
                } else {
                    echo "Falhou na busca peloas credencias na integração: ".$integration_name." store_id: ".$store_id;
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

            $results_integrations_logistic = $this->db->select('name, use_sellercenter')
                ->where_in('name', array('sgpweb','intelipost','freterapido','sequoia','correios'))
                ->get('integrations_logistic')
                ->result_array();
            
            foreach ($results_integrations_logistic as $result) {
                $db_ms->update('integrations', [
                    'use_sellercenter' => $result["use_sellercenter"],
                    'active' => 1
                ], [
                    'name' => $result["name"]
                ]);
            }

            $db_ms->update('integrations', [
                'use_seller' => 1,
                'active' => 1
            ]);
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
