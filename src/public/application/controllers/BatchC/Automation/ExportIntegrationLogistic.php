<?php

require 'system/libraries/Vendor/autoload.php';

class ExportIntegrationLogistic extends BatchBackground_Controller
{   
    private $integrations = array(
        "sgpweb"      => "1",
        "intelipost"  => "2",
        "freterapido" => "3",
        "sequoia"     => "4"
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

    private function processExport()
    {
        $proceed = true;
        $offset = 0;

        while ($proceed) {
            $query = $this->db->query(
                "SELECT integration_logistic.id, integration_logistic.store_id, integration_logistic.integration, integration_logistic.credentials,integration_logistic.active, integration_logistic.date_created, integration_logistic.date_updated
                FROM integration_logistic
                WHERE integration_logistic.integration IN ('sgpweb','intelipost','freterapido','sequoia')
                LIMIT ?, 50000",
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // MsShippingCarrier configures
            $insert = "SET NAMES utf8; INSERT INTO configures (id,integration_name, integration_id, credentials, store_id, active, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $id               = $result["id"];
                
                $store_id         = $result["store_id"];            
                if($store_id == 0) { $store_id = 'null';}

                $integration_name = addslashes($result["integration"]);

                $credentials            = null;
                if (!is_null($result["credentials"]) && !empty($result["credentials"])) {
                    $credentials        = $result["credentials"];
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

            $query_integrations_logistic = $this->db->query(
                "SELECT name, use_sellercenter 
                FROM integrations_logistic il 
                WHERE name IN ('sgpweb','intelipost','freterapido','sequoia')");

            $results_integrations_logistic = $query_integrations_logistic->result_array();

            foreach ($results_integrations_logistic as $result) {
                $insert .= "UPDATE integrations SET use_sellercenter = '".$result["use_sellercenter"]."', use_seller = '1', active = '1' WHERE name = '".$result["name"]."';";
            }

            //$insert .= "UPDATE integrations SET use_sellercenter = '1', use_seller = '0', active = '1' WHERE id IN (SELECT integration_id FROM configures GROUP by integration_id);";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/Ms_shipping_carrier_configures_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
