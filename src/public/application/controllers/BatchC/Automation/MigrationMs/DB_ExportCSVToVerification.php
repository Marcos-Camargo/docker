<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_settings $model_settings
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_users $model_users
 */
class DB_ExportCSVToVerification extends BatchBackground_Controller
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
        $this->load->model("model_csv_to_verifications");
        $this->load->model("model_users");
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
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

        if ($this->conta('csv_to_verification', $this->db, 'WHERE checked = 1 AND module = "Shippingcompany"') > 0) {
            echo "Tabela integration_logistic já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
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
     
        $this->conta('csv_verifications', $db_ms);

        $limit = 5000;
        while (true) {
            $results = $db_ms->where(['checked' => 1])->limit($limit, $offset)->get('csv_verifications')->result_array();

            echo "Processamento iniciando em ".$offset." lido ".count($results)."\n";

            if (empty($results)) {
                break;
            }
            $offset += $limit;

            $insert = [];
            foreach ($results as $result) {
                $user_email = 'useremail_migrated_microservice';
                $user_name = 'username_migrated_microservice';
                $user = $this->model_users->getUserById($result['user_id']);
                if ($user) {
                    $user_email = $user['email'];
                    $user_name = $user['username'];
                }

                $insert[] = [
                    'upload_file'           => $result['directory'].$result['file_name'],
                    'user_id'               => $result['user_id'],
                    'username'              => $user_name,
                    'user_email'            => $user_email,
                    'usercomp'              => $result['company_user_id'],
                    'checked'               => 1, //$result['checked'],
                    'final_situation'       => $result['error_found'] ? 'err' : 'success',
                    'allow_delete'          => 1,
                    'created_at'            => $result['created_at'],
                    'update_at'             => $result['updated_at'],
                    'module'                => 'Shippingcompany',
                    'form_data'             => json_encode(['shippingCompanyId' => $result['shipping_company_id'], 'dt_fim' => dateFormat($result['expiration_date'], DATE_BRAZIL)]), // {"shippingCompanyId":"292","dt_fim":"31\/01\/2026"}
                    'processing_response'   => $result['processing_response'],
                    'store_id'              => $result['store_id'],
                ];
            }

            if (!empty($insert)) {
                $this->model_csv_to_verifications->createBatch($insert);
            }
        }
        $this->conta('csv_to_verification', $this->db, 'WHERE checked = 1 AND module = "Shippingcompany"');
    }

    function conta($table, $dbvar, $where = '') {
        if ($dbvar->database == 'conectala') {
            echo "[Monolito] ";
        } else {
            echo "[MS] ";
        }

        $ret = $dbvar->query("select count(*) as cnt from $table $where");
        $cnt = $ret->row_array();        
        echo "tabela ".$table." com ".$cnt['cnt']." registros\n";
        return $cnt['cnt'];
    }
}
