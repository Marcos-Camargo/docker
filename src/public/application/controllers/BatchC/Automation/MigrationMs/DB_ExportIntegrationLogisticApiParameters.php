<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_settings $model_settings
 * @property Model_integration_logistic_api_parameters $model_integration_logistic_api_parameters
 */
class DB_ExportIntegrationLogisticApiParameters extends BatchBackground_Controller
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
        $this->load->model("model_settings");
        $this->load->model("model_integration_logistic_api_parameters");
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
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
		if ($settingSellerCenter) {
            $sellercenter = $settingSellerCenter['value'];
        }
		else {
			echo "não achei o sellercenter\n";
			die;			
		}

        if ($this->conta('integration_logistic_api_parameters', $this->db) > 0) {
            echo "Tabela integration_logistic_api_parameters já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
            return false;
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

        $this->conta('integration_logistic_api_parameters', $db_ms);

        $limit = 5000;
        while (true) {
            $results = $db_ms->limit($limit, $offset)->get('integration_logistic_api_parameters')->result_array();

            if (empty($results)) {
                break;
            }

            echo "Processamento iniciando em ".$offset." lido ".count($results)."\n";
            $offset += $limit;

            $insert = [];
            foreach ($results as $result) {
                $insert[] = array(
                    'integration_logistic_id'   => $result['integration_logistic_id'],
                    'type'                      => $result['type'],
                    'key'                       => $result['key'],
                    'value'                     => $result['value']
                );
            }

            if (!empty($insert)) {
                $this->model_integration_logistic_api_parameters->createBatch($insert);
            }
        }

        $this->conta('integration_logistic_api_parameters', $this->db);
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
