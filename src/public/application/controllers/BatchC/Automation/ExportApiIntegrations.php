<?php

require 'system/libraries/Vendor/autoload.php';

class ExportApiIntegrations extends BatchBackground_Controller
{   
    private $integrations = array(
        "precode"                   => "1",
        "pluggto"                   => "2",
        "anymarket"                 => "3",
        "vtex"                      => "4",
        "tiny"                      => "5",
        "hub2b"                     => "6",
        "viavarejo_b2b"             => "7",
        "viavarejo_b2b_casasbahia"  => "7",
        "viavarejo_b2b_extra"       => "7",
        "viavarejo_b2b_pontofrio"   => "7",
        "tray"                      => "8"
    );

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
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return ;
        }

        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");

        $this->processExport();
        
        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function getStatus($store_id, $integration_erp_id)
    {
        $sql = 'SELECT credentials
            FROM api_integrations
            WHERE integration_erp_id = ?';
        $query = $this->db->query($sql, array($integration_erp_id));
        $row = $query->row_array();
        return $row['credentials'];
    }

    private function processExport()
    {
        $proceed = true;
        $offset = 0;

        while ($proceed) {
            $query = $this->db->query(
                "SELECT integration_logistic.id, integration_logistic.store_id, integration_logistic.integration,integration_logistic.active, api_integrations.credentials, integration_logistic.credentials as log_credentials, integration_logistic.date_created, integration_logistic.date_updated
                FROM integration_logistic
                JOIN api_integrations ON integration_logistic.store_id = api_integrations.store_id
                WHERE integration_logistic.integration NOT IN ('sgpweb','intelipost','freterapido','sequoia')
                ORDER BY integration_logistic.id ASC
                LIMIT ?, 50000",
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // MsShippingIntegrator configures
            $insert = "SET NAMES utf8; INSERT INTO configures (id,integration_name, integration_id, credentials, store_id, active, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $id               = $result["id"];
                $store_id            = $result["store_id"];

                $integration_name = addslashes($result["integration"]);              

                $credentials            = null;
                if (!is_null($result["credentials"]) && !empty($result["credentials"])) {
                    $credentials = $result["credentials"];
                    if($integration_name == 'precode' && $credentials == '[]') {
                        $credentials  = $result["log_credentials"];
                    }
                }
                
                if (array_key_exists($integration_name, $this->integrations)) {
                    $integration_id = $this->integrations[$integration_name];
                } else{
                    echo "Falhou na busca peloas credencias na integração: ".$integration_name." store_id: ".$store_id;
                    continue;
                }

                $active = $result["active"];
                if ($active != 1) {
                    $active = 0;
                }

                $created_at = $result["date_created"];
                $updated_at = $result["date_updated"];

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                if (is_null($credentials) || empty($credentials) || $credentials == '') {
                    $insert .= "($id, '$integration_name', $integration_id, NULL, $store_id, $active, '$created_at', '$updated_at')";
                } else{
                    $insert .= "($id, '$integration_name', $integration_id, '$credentials', $store_id, $active, '$created_at', '$updated_at')";
                }
                
            }
            $insert .= ";";
            $insert .= "UPDATE integrations SET use_sellercenter = '0', use_seller = '1', active = '1' WHERE id IN (SELECT integration_id FROM configures GROUP by integration_id);";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/ms_shipping_integrator_configures_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
