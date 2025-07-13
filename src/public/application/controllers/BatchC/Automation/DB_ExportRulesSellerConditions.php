<?php

require 'system/libraries/Vendor/autoload.php';

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

    private function getIntegrationName($mkt_id)
    {
        $sql = 'SELECT int_to
            FROM integrations
            WHERE id = ?';
        $query = $this->db->query($sql, array($mkt_id));
        $row = $query->row_array();
        return $row['int_to'];
    }

    private function processExport($params)
    {
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
        // $hostdb   = '10.150.23.128';
        // $hostdb   = '10.150.23.188';
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.23.188';
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

        if ($this->conta('auction_rule_type_to_marketplaces', $db_ms) > 0) {
            echo "Tabelas auction_rule_type_to_marketplaces já contém registro em Shipping. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }

        $this->conta('rules_seller_conditions', $this->db);
        // fim da parte nova

        $offset = 0;
        while (true) {
            $results = $this->db->where('store_id', 0)
                ->order_by('id', 'ASC')
                ->limit(50000, $offset)
                ->get('rules_seller_conditions')
                ->result_array();

            echo "Processando iniciando em $offset lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            // MsShipping auction_rule_type_to_marketplaces
            $insert = [];
            foreach ($results as $result) {
                $id                    = $result["id"];
                $store_id              = $result["store_id"];
                $auction_rules_type_id = $result["rules_seller_conditions_status_id"];
                
                $marketplace = $this->getIntegrationName($result["mkt_id"]);
                
                if ($marketplace > 0) {
                    echo "Falhou a busca pelo marketplace no ID: $id\n";
                    continue;
                }

                $created_at = date("Y-m-d H:i:s");
                $updated_at = $created_at;

                $insert[] = [
                    'id'                    => $id,
                    'auction_rules_type_id' => $auction_rules_type_id,
                    'store_id'              => $store_id,
                    'marketplace'           => $marketplace,
                    'created_at'            => $created_at,
                    'updated_at'            => $updated_at
                ];
            }

            if (!empty($insert)) {
                $db_ms->insert_batch('auction_rule_type_to_marketplaces', $insert);
            }
        }

        $this->conta('auction_rule_type_to_marketplaces', $db_ms);
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
