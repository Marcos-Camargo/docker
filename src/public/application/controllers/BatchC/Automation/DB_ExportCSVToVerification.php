<?php

require 'system/libraries/Vendor/autoload.php';

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

    private function getStatus($store_id, $shipping_company_id)
    {
        $sql = 'SELECT file_table_shippingcol AS filename, status
            FROM file_table_shipping
            WHERE shipping_company_id = ?
            ORDER BY idfile_table_shipping DESC';
        $query = $this->db->query($sql, array($shipping_company_id));
        return $query->result_array();
    }

    private function processExport($params)
    {
        $offset = 0;

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

        if ($this->conta('csv_verifications', $db_ms) > 0) {
            echo "Tabelas csv_verifications já contém registro em Freight Tables. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }
     
        $this->conta('csv_to_verification', $this->db, "WHERE module = 'Shippingcompany' AND checked = 1");
        // fim da parte nova

        while (true) {
            $results = $this->db
                ->select('id, upload_file, user_id, usercomp, checked, final_situation, form_data, processing_response, store_id, created_at, update_at')
                ->where([
                    'module'    => 'Shippingcompany',
                    'checked'   => 1
                ])
                ->limit(50000, $offset)
                ->order_by('id', 'ASC')
                ->get('csv_to_verification')
                ->result_array();

            echo "Processando iniciando em ".$offset." lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            $insert = [];
            foreach ($results as $result) {
                $form_data = $result['form_data'];
                if (empty($form_data)) {
                    echo "Campo form_data em branco para o id $result[id]\n";
                    continue;
                } else {
                    $form_data = json_decode($form_data);
                    $shipping_company_id = $form_data->shippingCompanyId;
                    $expiration_date = dateBrazilToDateInternational($form_data->dt_fim);
                }

                $company_id = $result['usercomp'];
                $store_id = $result['store_id'];
                if (empty($store_id)) {
                    $providers_to_seller = $this->db->select('store_id')
                        ->where('provider_id', $shipping_company_id)
                        ->get('providers_to_seller')
                        ->row_array();
                    if (empty($providers_to_seller) || empty($providers_to_seller['store_id'])) {
                        echo "Loja não encontrada para o id $result[id]\n";
                        continue;
                    }

                    $store_id = $providers_to_seller['store_id'];
                }

                $sc_status = $this->getStatus($store_id, $shipping_company_id);
                if (count($sc_status) == 0) {
                    echo "Não encontrou o arquivo da tabeal de frete para o id $result[id]\n";
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
                    }
                }

                $error_found = 0;
                if ($result['processing_response'] != 'Arquivo processado com sucesso!') {
                    $error_found = 1;
                }

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

                $insert[] = [
                    'id'                    => $id,
                    'directory'             => $directory,
                    'file_name'             => $file_name,
                    'expiration_date'       => $expiration_date,
                    'checked'               => $checked,
                    'processing_response'   => $processing_response,
                    'status'                => $status,
                    'user_id'               => $user_id,
                    'shipping_company_id'   => $shipping_company_id,
                    'company_user_id'       => $company_id,
                    'store_id'              => $store_id,
                    'error_found'           => $error_found,
                    'created_at'            => $created_at,
                    'updated_at'            => $updated_at
                ];
            }

            if (!empty($insert)) {
                $db_ms->insert_batch('csv_verifications', $insert);
            }
        }
        $this->conta('csv_verifications', $db_ms);
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
