<?php

require 'system/libraries/Vendor/autoload.php';

class ExportTableShipping extends BatchBackground_Controller
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
                'SELECT idtable_shipping, idproviders_to_seller, id_file, dt_envio, region, CEP_start, CEP_end, weight_minimum, weight_maximum, shipping_price, qtd_days, status
                FROM table_shipping
                ORDER BY idtable_shipping ASC
                LIMIT ?, 200000',
            array($offset));

            $offset += 200000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // SIMPLIFIED TABLES
            $insert = "SET NAMES utf8; INSERT INTO shipping_tables (id, shipping_company_id, file_id, region, zip_code_start, zip_code_end, weight_minimum, weight_maximum,  shipping_price, days_amount, status, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $id = $result["idtable_shipping"];
                $shipping_company_id = $result["idproviders_to_seller"];
                $file_id = $result["id_file"];
                $region = $result["region"];

                if ($result["CEP_start"]) {
                    $zip_code_start = addslashes(preg_replace("/[^0-9]/", "", $result["CEP_start"]));
                }

                if ($result["CEP_end"]) {
                    $zip_code_end = addslashes(preg_replace("/[^0-9]/", "", $result["CEP_end"]));
                }

                if ($result["weight_minimum"]) {
                    $weight_minimum = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["weight_minimum"])));
                }

                if ($result["weight_maximum"]) {
                    $weight_maximum = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["weight_maximum"])));
                }

                if ($result["shipping_price"]) {
                    $shipping_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["shipping_price"])));
                }

                if ($result["qtd_days"]) {
                    $days_amount = addslashes(preg_replace("/[^0-9]/", "", $result["qtd_days"]));
                }

                $status = 1;
                if ($result["status"]) {
                    $status = addslashes(preg_replace("/[^0-9]/", "", $result["status"]));
                }

                $created_at = date("Y-m-d H:i:s");
                $updated_at = date("Y-m-d H:i:s");

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "($id, '$shipping_company_id', '$file_id', '$region', '$zip_code_start', '$zip_code_end', $weight_minimum, $weight_maximum, $shipping_price, $days_amount, $status, '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/shipping_tables_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
