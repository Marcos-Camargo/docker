<?php

require 'system/libraries/Vendor/autoload.php';

class ExportSettings extends BatchBackground_Controller
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
                'SELECT id, name, value, status, date_updated
                FROM settings
                ORDER BY id ASC
                LIMIT ?, 50000',
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // SETTINGS
            $insert = "SET NAMES utf8; INSERT INTO settings (id, name, value, active, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

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

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "($id, '$name', '$value', $active, '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/settings_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
