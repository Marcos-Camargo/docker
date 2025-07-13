<?php

require 'system/libraries/Vendor/autoload.php';

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

        echo "Limpando tabelas \n";
        // fim da parte nova

        $tables_region = $this->db->where('status', 1)
            ->group_by('table')
            ->get('table_shipping_regions')
            ->result_array();

        foreach ($tables_region as $table_region) {
            $table_ms = 'shipping_tables_' . str_replace('table_shipping_', '', $table_region['table']);

            if ($this->conta($table_ms, $db_ms) > 0) {
                echo "Tabela $table_ms já contém registro em Freight Tables. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
                echo "--------------------------------------------------\n";
                continue;
            }

            $this->conta($table_region['table'], $this->db);

            $offset = 0;
            while (true) {
                $results = $this->db->select('idtable_shipping, idproviders_to_seller, id_file, dt_envio, region, CEP_start, CEP_end, weight_minimum, weight_maximum, shipping_price, qtd_days, status')
                    ->order_by('idtable_shipping', 'ASC')
                    ->limit(50000, $offset)
                    ->get($table_region['table'])
                    ->result_array();

                if (empty($results)) {
                    break;
                }

                echo "Processando iniciando em $offset lido " . count($results) . "\n";
                $offset += 50000;

                // SIMPLIFIED TABLES
                $insert = [];
                foreach ($results as $result) {
                    $id                  = $result["idtable_shipping"];
                    $shipping_company_id = $result["idproviders_to_seller"];
                    $file_id             = $result["id_file"];
                    $region              = $result["region"];
                    $zip_code_start      = addslashes(onlyNumbers($result["CEP_start"]));
                    $zip_code_end        = addslashes(onlyNumbers($result["CEP_end"]));
                    $weight_minimum      = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["weight_minimum"])));
                    $weight_maximum      = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["weight_maximum"])));
                    $shipping_price      = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["shipping_price"])));
                    $days_amount         = addslashes(onlyNumbers($result["qtd_days"]));
                    $status              = addslashes(onlyNumbers($result["status"]));
                    $created_at          = $result['dt_envio'];
                    $updated_at          = date("Y-m-d H:i:s");

                    $insert[] = [
                        'id'                    => $id,
                        'shipping_company_id'   => $shipping_company_id,
                        'file_id'               => $file_id,
                        'region'                => $region,
                        'zip_code_start'        => $zip_code_start,
                        'zip_code_end'          => $zip_code_end,
                        'weight_minimum'        => $weight_minimum,
                        'weight_maximum'        => $weight_maximum,
                        'shipping_price'        => $shipping_price,
                        'days_amount'           => $days_amount,
                        'status'                => $status,
                        'created_at'            => $created_at,
                        'updated_at'            => $updated_at
                    ];
                }

                if (!empty($insert)) {
                    $db_ms->insert_batch($table_ms, $insert);
                }
            }
            $this->conta($table_ms, $db_ms);
            echo "--------------------------------------------------\n";
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
