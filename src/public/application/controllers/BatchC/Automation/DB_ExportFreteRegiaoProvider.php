<?php

require 'system/libraries/Vendor/autoload.php';

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

        if ($this->conta('simplified_tables', $db_ms) > 0) {
            echo "Tabelas simplified_tables já contém registro em Freight Tables. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }

        $this->conta('frete_regiao_provider', $this->db);
        // fim da parte nova

        while (true) {
            $query = $this->db->query(
                'SELECT idfrete_regiao, id_provider, id_regiao, id_estado, valor, qtd_dias, capital_valor, interior_valor, capital_qtd_dias, interior_qtd_dias
                FROM frete_regiao_provider
                ORDER BY idfrete_regiao ASC
                LIMIT ?, 50000',
            array($offset));

            $results = $query->result_array();
            echo "Processando iniciando em ".$offset." lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            // SIMPLIFIED TABLES
            $insert = [];
            foreach ($results as $result) {
                $id = $result["idfrete_regiao"];
                $shipping_company_id = $result["id_provider"];
                $region_id = $result["id_regiao"];

                $state_id = null;
                if ($result["id_estado"]) {
                    $state_id = $result["id_estado"];
                }

                $shipping_price = null;
                if ($result["valor"]) {
                    $shipping_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["valor"])));
                }

                $days_amount = null;
                if ($result["qtd_dias"]) {
                    $days_amount = $result["qtd_dias"];
                }

                $capital_price = null;
                if ($result["capital_valor"]) {
                    $capital_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["capital_valor"])));
                }

                $capital_days = null;
                if ($result["capital_qtd_dias"]) {
                    $capital_days = $result["capital_qtd_dias"];
                }

                $interior_price = null;
                if ($result["interior_valor"]) {
                    $interior_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["interior_valor"])));
                }

                $interior_days = null;
                if ($result["interior_qtd_dias"]) {
                    $interior_days = $result["interior_qtd_dias"];
                }

                $created_at = date("Y-m-d H:i:s");
                $updated_at = $created_at;

                $insert[] = [
                    'id'                    => $id,
                    'shipping_company_id'   => $shipping_company_id,
                    'region_id'             => $region_id,
                    'state_id'              => $state_id,
                    'shipping_price'        => $shipping_price,
                    'days_amount'           => $days_amount,
                    'capital_price'         => $capital_price,
                    'capital_days'          => $capital_days,
                    'interior_price'        => $interior_price,
                    'interior_days'         => $interior_days,
                    'created_at'            => $created_at,
                    'updated_at'            => $updated_at
                ];
            }

            if (!empty($insert)) {
                $db_ms->insert_batch('simplified_tables', $insert);
            }
        }
        $this->conta('simplified_tables', $db_ms);
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
