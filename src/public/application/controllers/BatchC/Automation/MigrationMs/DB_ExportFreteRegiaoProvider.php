<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_settings $model_settings
 * @property Model_shipping_company $model_shipping_company
 */
class DB_ExportFreteRegiaoProvider extends BatchBackground_Controller
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

        if ($this->conta('frete_regiao_provider', $this->db) > 0) {
            echo "Tabela frete_regiao_provider já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
            return false;
        }

        // $hostdb   = '10.150.17.106';
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

        $this->conta('simplified_tables', $db_ms);

        $limit = 5000;
        while (true) {
            $results = $db_ms->limit($limit, $offset)->get('simplified_tables')->result_array();

            if (empty($results)) {
                break;
            }

            echo "Processamento iniciando em ".$offset." lido ".count($results)."\n";
            $offset += $limit;

            // SIMPLIFIED TABLES
            $insert = [];
            foreach ($results as $result) {
                $insert[] = array(
                    'idfrete_regiao'    => $result["id"],
                    'id_provider'       => $result['shipping_company_id'],
                    'id_regiao'         => $result['region_id'],
                    'id_estado'         => $result['state_id'],
                    'valor'             => is_null($result['shipping_price'])   ? '' : $result['shipping_price'],
                    'qtd_dias'          => is_null($result['days_amount'])      ? 0 : $result['days_amount'],
                    'capital_valor'     => !is_null($result['state_id']) && is_null($result['capital_price'])   ? '' : $result['capital_price'],
                    'interior_valor'    => !is_null($result['state_id']) && is_null($result['interior_price'])  ? '' : $result['interior_price'],
                    'capital_qtd_dias'  => !is_null($result['state_id']) && is_null($result['capital_days'])    ? 0 : $result['capital_days'],
                    'interior_qtd_dias' => !is_null($result['state_id']) && is_null($result['interior_days'])   ? 0 : $result['interior_days'],
                );
            }

            if (!empty($insert)) {
                $this->model_shipping_company->createFreteRegiaoProviderBatch($insert);
            }
        }
        $this->conta('frete_regiao_provider', $this->db);
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
