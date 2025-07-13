<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_settings $model_settings
 * @property Model_withdrawal_time $model_withdrawal_time
 * @property Model_pickup_point $model_pickup_point
 */
class DB_ExportPickupPoint extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model("model_settings");
        $this->load->model("model_withdrawal_time");
        $this->load->model("model_pickup_point");
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('Automation/' . $this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

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
        } else {
            echo "não achei o sellercenter\n";
            die;
        }

        if ($this->conta('pickup_points', $this->db) > 0 || $this->conta('withdrawal_times', $this->db) > 0) {
            echo "Tabelas pickup_points ou withdrawal_times já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
            return false;
        }

        // $hostdb   = '10.150.17.106';
        // $hostdb   = '10.150.16.113';
        // $hostdb   = '10.150.23.128';
        // $hostdb   = '10.150.23.188';
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.21.245';
            $username = 'ms_pickuppoint';
        } else {
            $hostdb = '10.151.100.137';
            $username = 'admin';
        }
        $database = 'ms_pickup_point_' . $sellercenter;

        $db_migra = [
            'dsn' => '',
            'hostname' => $hostdb,
            'username' => $username,
            'password' => $params,
            'database' => $database,
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => false,
            'db_debug' => true,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt' => FALSE,
            'compress' => FALSE,
            'stricton' => FALSE,
            'failover' => array(),
            'save_queries' => TRUE

        ];
        $db_ms = $this->load->database($db_migra, TRUE);

        $this->conta('pickup_points', $db_ms);
        $this->conta('withdrawal_times', $db_ms);

        $offset = 0;
        $limit = 10;
        while (true) {
            $results = $db_ms
                ->limit($limit, $offset)
                ->get('pickup_points')
                ->result_array();

            if (empty($results)) {
                break;
            }

            echo "Processamento iniciando em $offset lido " . count($results) . "\n";
            $offset += $limit;

            // Pickup Points
            $insert_pickup_point = $results;
            $insert_withdrawal_times = array();

            foreach ($results as $result) {
                $insert_withdrawal_times = array_merge(
                    $insert_withdrawal_times,
                    $db_ms->where('pickup_point_id', $result['id'])
                        ->get('withdrawal_times')
                        ->result_array()
                );
            }

            $this->model_pickup_point->create_batch($insert_pickup_point);
            $this->model_withdrawal_time->create_batch($insert_withdrawal_times);
        }

        $this->conta('pickup_points', $this->db);
        $this->conta('withdrawal_times', $this->db);
    }

    function conta($table, $dbvar)
    {
        if ($dbvar->database == 'conectala') {
            echo "[Monolito] ";
        } else {
            echo "[MS] ";
        }

        $ret = $dbvar->query('select count(*) as cnt from ' . $table);
        $cnt = $ret->row_array();
        echo "tabela " . $table . " com " . $cnt['cnt'] . " registros\n";
        return $cnt['cnt'];
    }
}
