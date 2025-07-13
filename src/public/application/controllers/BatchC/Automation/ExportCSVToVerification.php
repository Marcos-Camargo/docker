<?php

require 'system/libraries/Vendor/autoload.php';

class ExportCSVToVerification extends BatchBackground_Controller
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
            return;
        }

        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");

        $this->processExport();
        
        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function getStatus($store_id, $shipping_company_id)
    {
        $sql = 'SELECT file_table_shippingcol AS filename, status
            FROM file_table_shipping
            WHERE shipping_company_id = ?
            ORDER BY idfile_table_shipping DESC';
        $query = $this->db->query($sql, array($shipping_company_id));
        return $query->result_array();
    }

    private function processExport()
    {
        $proceed = true;
        $offset = 0;

        while ($proceed) {
            $query = $this->db->query(
                'SELECT id, upload_file, user_id, usercomp, checked, final_situation, form_data, processing_response, store_id, created_at, update_at 
                FROM csv_to_verification
                WHERE module = "Shippingcompany"
                ORDER BY id ASC
                LIMIT ?, 50000',
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            $insert = 'SET NAMES utf8; INSERT INTO csv_verifications (id, directory, file_name, expiration_date, checked, processing_response, status, user_id, shipping_company_id, company_user_id, store_id, error_found, created_at, updated_at) VALUES '; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $expiration_date = 0;

                $form_data = $result['form_data'];
                if (empty($form_data)) {
                    continue;
                } else {
                    $form_data = json_decode($form_data);
                    $shipping_company_id = $form_data->shippingCompanyId;

                    // '31/12/2021' -> '2023-01-19 11:05:49'
                    $expiration_date = $form_data->dt_fim;
                    $aux = explode('/', $expiration_date);
                    $expiration_date = $aux[2] . '-' . $aux[1] . '-' . $aux[0];
                }

                $company_id = $result['usercomp'];
                $store_id = $result['store_id'];
                if (empty($store_id)) {
                    continue;
                }

                $sc_status = $this->getStatus($store_id, $shipping_company_id);
                if (count($sc_status) == 0) {
                    continue;
                }

                $upload_file = $result['upload_file'];
                $pos = strrpos($upload_file, "/") + 1;
                $directory = substr($upload_file, 0, $pos);
                $file_name = substr($upload_file, $pos);

                $status = 0;
                foreach ($sc_status as $s) {
                    if (($s['filename'] == isset($file_name)) && ($s['status'] == 1)) {
                        $status = 1;
                    } else if (($s['filename'] == isset($file_name)) && ($s['status'] == 0)) {
                        $status = 1;
                    } else {
                        continue;
                    }
                }
                $status = $status;

                $error_found = 0;
                if ($result['processing_response'] != 'Arquivo processado com sucesso!') {
                    $error_found = 1;
                }

                $iteration += 1;
                $id = $result["id"];
                $checked = $result['checked'];

                // {"messages":["File 63a5fc298c5f8.csv uploaded."],"errors":[]}
                if ($error_found == 0) {
                    $processing_response = '{"messages":["File ' . $file_name . ' uploaded."],"errors":[]}';
                } else {
                    $processing_response = '{"messages":["File ' . $file_name . ' uploaded."],"errors":["' . addslashes($result['processing_response']) . '"]}';
                }

                $user_id = $result['user_id'];
                $created_at = $result["created_at"];
                $updated_at = $result["update_at"];

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ', ';
                }

                $insert .= "($id, '$directory', '$file_name', '$expiration_date', '$checked', '$processing_response', '$status', '$user_id', '$shipping_company_id', '$company_id', '$store_id', '$error_found', '$created_at', '$updated_at')";
            }
            $insert .= ";";
            // print_r($insert);

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/csv_verifications_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
