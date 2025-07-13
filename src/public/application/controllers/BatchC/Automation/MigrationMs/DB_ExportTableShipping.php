<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_table_shipping_regions $model_table_shipping_regions
 * @property Model_settings $model_settings
 */
class DB_ExportTableShipping extends BatchBackground_Controller
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
        $this->load->model('model_table_shipping_regions');
        $this->load->model("model_settings");
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

        $tables_region = $this->db->where('status', 1)
            ->group_by('table')
            ->get('table_shipping_regions')
            ->result_array();

        foreach ($tables_region as $table_region) {
            if ($this->conta($table_region['table'], $this->db) > 0) {
                echo "Tabela $table_region[table] já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
                return false;
            }
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

        foreach ($tables_region as $table_region) {
            echo "--------------------------------------------------\n";
            $table_ms = 'shipping_tables_' . str_replace('table_shipping_', '', $table_region['table']);
            $this->conta($table_ms, $db_ms);

            $limit = 2500;
            $offset = 0;
            while (true) {
                $results = $db_ms->order_by('id', 'ASC')
                    ->limit($limit, $offset)
                    ->get($table_ms)
                    ->result_array();

                if (empty($results)) {
                    break;
                }

                $uf = str_replace('shipping_tables_', '', $table_ms);
                echo "[$uf]Processamento iniciando em $offset lido " . count($results) . "\n";
                $offset += $limit;

                // SIMPLIFIED TABLES
                $insert = [];
                foreach ($results as $result) {
                    $insert[] = array(
                        'idtable_shipping'      => $result['id'],
                        'idproviders_to_seller' => $result['shipping_company_id'],
                        'id_file'               => $result['file_id'],
                        'dt_envio'              => $result['created_at'],
                        'region'                => $result['region'],
                        'CEP_start'             => $result['zip_code_start'],
                        'CEP_end'               => $result['zip_code_end'],
                        'weight_minimum'        => $result['weight_minimum'],
                        'weight_maximum'        => $result['weight_maximum'],
                        'shipping_price'        => $result['shipping_price'],
                        'qtd_days'              => $result['days_amount'],
                        'status'                => $result['status']
                    );
                }

                if (!empty($insert)) {
                    $this->model_table_shipping_regions->createStateBatch($insert, $uf);
                }
            }
            $this->conta($table_region['table'], $this->db);
        }
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
