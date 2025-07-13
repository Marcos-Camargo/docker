<?php

require 'system/libraries/Vendor/autoload.php';

class ExportRegions extends BatchBackground_Controller
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
                'SELECT idRegiao, Nome
                FROM regions
                ORDER BY idRegiao ASC
                LIMIT ?, 50000',
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // REGIONS
            $insert = "SET NAMES utf8; INSERT INTO regions (id, name, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $id = $result["idRegiao"];
                $name = $result["Nome"];

                $created_at = date("Y-m-d H:i:s");
                $updated_at = date("Y-m-d H:i:s");

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "($id, '$name', '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/regions_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
