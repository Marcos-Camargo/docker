<?php

require 'system/libraries/Vendor/autoload.php';

class ExportFreteRegiaoProvider extends BatchBackground_Controller
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
                'SELECT idfrete_regiao, id_provider, id_regiao, id_estado, valor, qtd_dias, capital_valor, interior_valor, capital_qtd_dias, interior_qtd_dias
                FROM frete_regiao_provider
                ORDER BY idfrete_regiao ASC
                LIMIT ?, 50000',
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // SIMPLIFIED TABLES
            $insert = "SET NAMES utf8; INSERT INTO simplified_tables (id, shipping_company_id, region_id, state_id, shipping_price, days_amount, capital_price, capital_days, interior_price, interior_days, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $id = $result["idfrete_regiao"];
                $shipping_company_id = $result["id_provider"];
                $region_id = $result["id_regiao"];

                $state_id = 'null';
                if ($result["id_estado"]) {
                    $state_id = $result["id_estado"];
                }

                $shipping_price = 'null';
                if ($result["valor"]) {
                    $shipping_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["valor"])));
                }

                $days_amount = 'null';
                if ($result["qtd_dias"]) {
                    $days_amount = $result["qtd_dias"];
                }

                $capital_price = 'null';
                if ($result["capital_valor"]) {
                    $capital_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["capital_valor"])));
                }

                $capital_days = 'null';
                if ($result["capital_qtd_dias"]) {
                    $capital_days = $result["capital_qtd_dias"];
                }

                $interior_price = 'null';
                if ($result["interior_valor"]) {
                    $interior_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["interior_valor"])));
                }

                $interior_days = 'null';
                if ($result["interior_qtd_dias"]) {
                    $interior_days = $result["interior_qtd_dias"];
                }

                $created_at = date("Y-m-d H:i:s");
                $updated_at = date("Y-m-d H:i:s");

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "($id, '$shipping_company_id', '$region_id', $state_id, $shipping_price, $days_amount, $capital_price, $capital_days, $interior_price, $interior_days, '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/simplified_tables_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
