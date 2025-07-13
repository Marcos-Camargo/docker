<?php

require 'system/libraries/Vendor/autoload.php';

class DB_ExportSettings extends BatchBackground_Controller
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

        // Shipping
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
        $db_ms1 = $this->load->database($db_migra,TRUE);

        // Shipping Integrator
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.17.106';
        } else {
            $hostdb = '10.151.100.164';
        }
        $database = 'ms_shipping_integrator_'.$sellercenter;
        $db_migra['hostname'] = $hostdb;
        $db_migra['database'] = $database ;
        $db_ms2 = $this->load->database($db_migra,TRUE);

        // Freight Tables
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.16.113';
        } else {
            $hostdb = '10.151.100.250';
        }
        $database = 'ms_freight_tables_'.$sellercenter;
        $db_migra['hostname'] = $hostdb;
        $db_migra['database'] = $database ;
        $db_ms3 = $this->load->database($db_migra,TRUE);

        // Shipping Carrier
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.23.128';
        } else {
            $hostdb = '10.151.100.67';
        }
        $database = 'ms_shipping_carrier_'.$sellercenter;
        $db_migra['hostname'] = $hostdb;
        $db_migra['database'] = $database ;
        $db_ms4 = $this->load->database($db_migra,TRUE);

        // PickUp Point
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.21.245';
        } else {
            $hostdb = '10.151.100.137';
        }
        $database = 'ms_pickup_point_'.$sellercenter;
        $db_migra['hostname'] = $hostdb;
        $db_migra['database'] = $database ;
        $db_ms5 = $this->load->database($db_migra,TRUE);

        $settings_db_ms1 = $this->conta('settings', $db_ms1);
        $settings_db_ms2 = $this->conta('settings', $db_ms2);
        $settings_db_ms3 = $this->conta('settings', $db_ms3);
        $settings_db_ms4 = $this->conta('settings', $db_ms4);
        $settings_db_ms5 = $this->conta('settings', $db_ms5);

        if (
            $settings_db_ms1 > 0 ||
            $settings_db_ms2 > 0 ||
            $settings_db_ms3 > 0 ||
            $settings_db_ms4 > 0 ||
            $settings_db_ms5 > 0
        ) {
            echo "Tabelas settings já contém registro em algum MS. 
            shipping($settings_db_ms1), 
            shipping_integrator($settings_db_ms2), 
            freight_tables($settings_db_ms3), 
            shipping_carrier($settings_db_ms4), 
            pickup_point($settings_db_ms5). 
            Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }
        
        $this->conta('settings', $this->db);
        // fim da parte nova

        $offset = 0;
        while (true) {
            $results = $this->db->select('id, name, value, status, date_updated')
                ->order_by('id', 'ASC')
                ->limit(50000, $offset)
                ->get('settings')
                ->result_array();

            echo "Processando iniciando em ".$offset." lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            // SETTINGS
            $insert = [];
            foreach ($results as $result) {
                $id = $result["id"];
                $name = addslashes($result["name"]);

                $value = $result["value"];
                if (empty($value)) {
                    $value = '-';
                }
                $value = addslashes($value);

                $active = $result["status"];
                if ($active != 1) {
                    $active = 0;
                }

                $created_at = $result["date_updated"];
                $updated_at = $result["date_updated"];

                $insert[] = [
                    'id'            => $id,
                    'name'          => $name,
                    'value'         => $value,
                    'active'        => $active,
                    'created_at'    => $created_at,
                    'updated_at'    => $updated_at
                ];
            }

            if (!empty($insert)) {
                $db_ms1->insert_batch('settings', $insert);
                $db_ms2->insert_batch('settings', $insert);
                $db_ms3->insert_batch('settings', $insert);
                $db_ms4->insert_batch('settings', $insert);
                $db_ms5->insert_batch('settings', $insert);
            }
        }

        $this->conta('settings', $db_ms1);
        $this->conta('settings', $db_ms2);
        $this->conta('settings', $db_ms3);
        $this->conta('settings', $db_ms4);
        $this->conta('settings', $db_ms5);
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
