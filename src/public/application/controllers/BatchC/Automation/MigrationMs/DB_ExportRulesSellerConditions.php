<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_settings $model_settings
 * @property Model_auction $model_auction
 */

class DB_ExportRulesSellerConditions extends BatchBackground_Controller
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
        $this->load->model("model_auction");
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

    private function getIntegrationName($int_to)
    {
        $sql = 'SELECT id
            FROM integrations
            WHERE int_to = ?';
        $query = $this->db->query($sql, array($int_to));
        $row = $query->row_array();
        return $row['id'];
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

        if ($this->conta('rules_seller_conditions', $this->db) > 0) {
            echo "Tabela rules_seller_conditions já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
            return false;
        }

        // $hostdb   = '10.150.17.106';
        // $hostdb   = '10.150.16.113';
        // $hostdb   = '10.150.23.128';
        // $hostdb   = '10.150.23.188';
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.21.189';
        } else {
            $hostdb = '10.151.100.221';
        }
        $database = 'ms_shipping_'.$sellercenter;

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

        $this->conta('auction_rule_type_to_marketplaces', $db_ms);

        $marketplaces = array();
        $offset = 0;
        $limit = 5000;
        while (true) {
            $results = $db_ms->where('store_id', 0)->limit($limit, $offset)->get('auction_rule_type_to_marketplaces')->result_array();

            if (empty($results)) {
                break;
            }

            echo "Processamento iniciando em $offset lido ".count($results)."\n";
            $offset += $limit;

            // MsShipping auction_rule_type_to_marketplaces
            $insert = [];
            foreach ($results as $result) {
                if (!array_key_exists($result["marketplace"], $marketplaces)) {
                    $marketplace = $this->getIntegrationName($result["marketplace"]);
                    $marketplaces[$result["marketplace"]] = $marketplace;
                } else {
                    $marketplace = $marketplaces[$result["marketplace"]];
                }

                $insert[] = array(
                    'id'                                => $result['id'],
                    'store_id'                          => $result['store_id'],
                    'rules_seller_conditions_status_id' => $result['auction_rules_type_id'],
                    'mkt_id'                            => $marketplace,
                );
            }

            if (!empty($insert)) {
                $this->model_auction->createRulesSellerConditionsBatch($insert);
            }
        }

        $this->conta('rules_seller_conditions', $this->db);
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
