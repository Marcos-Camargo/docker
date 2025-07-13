<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_settings $model_settings
 * @property Model_shipping_company $model_shipping_company
 * @property Model_stores $model_stores
 */
class DB_ExportProvidersToSeller extends BatchBackground_Controller
{   
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
        $this->load->model("model_shipping_company");
        $this->load->model("model_stores");
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
        // parte nova para conectar no banco do MS
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
		if ($settingSellerCenter) {
            $sellercenter = $settingSellerCenter['value'];
        }
		else {
			echo "não achei o sellercenter\n";
			die;			
		}

        if ($this->conta('providers_to_seller', $this->db) > 0) {
            echo "Tabela providers_to_seller já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
            return false;
        }

        // $hostdb   = '10.150.17.106';
        // $hostdb   = '10.150.16.113';
        // $hostdb   = '10.150.23.128';
        // $hostdb   = '10.150.23.188';
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.16.113';
        } else {
            $hostdb = '10.151.100.250';
        }
        $database = 'ms_freight_tables_'.$sellercenter;

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

        $this->conta('shipping_company_to_store', $db_ms);

        $limit = 5000;
        $offset = 0;
        $stores = [];
        while (true) {
            $results = $db_ms->limit($limit, $offset)->get('shipping_company_to_store')->result_array();

            if (empty($results)) {
                break;
            }

            echo "Processamento iniciando em $offset lido ".count($results)."\n";
            $offset += $limit;

            // SHIPPING COMPANY TO STORE
            $insert = [];
            foreach ($results as $result) {
                $company_id = $result['company_id'];

                if (!$company_id) {
                    if (!key_exists($result['store_id'], $stores)) {
                        $store = $this->model_stores->getStoresData($result['store_id']);

                        if (!$store) {
                            echo "Loja $result[store_id] não encontrada. ".json_encode($result)."\n";
                            continue;
                        }
                        $company_id = $store['company_id'];

                        $stores[$result['store_id']] = $company_id;
                    } else {
                        $company_id = $stores[$result['store_id']];
                    }
                }

                $insert[] = array(
                    'idproviders_to_seller' => $result['id'],
                    'provider_id'           => $result['shipping_company_id'],
                    'company_id'            => $company_id,
                    'store_id'              => $result['store_id'],
                    'dt_cadastro'           => $result['created_at'],
                    'user_id'               => 1,
                );
            }

            if (!empty($insert)) {
                $this->model_shipping_company->createProvidersToSellerBatch($insert);
            }
        }

        $this->conta('providers_to_seller', $this->db);
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
