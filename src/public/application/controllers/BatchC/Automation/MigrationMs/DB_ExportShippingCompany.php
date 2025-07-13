<?php

require 'system/libraries/Vendor/autoload.php';


/**
 * @property Model_settings $model_settings
 * @property Model_shipping_company $model_shipping_company
 */

class DB_ExportShippingCompany extends BatchBackground_Controller
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
        $this->load->model("model_shipping_company");
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return ;
        }

        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");

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
        }
		else {
			echo "não achei o sellercenter\n";
			die;			
		}

        if ($this->conta('type_table_shipping', $this->db) > 0 || $this->conta('shipping_company', $this->db) > 0) {
            echo "Tabelas type_table_shipping ou shipping_company já contém registro. Não pode ser migrada novamente. Caso precisa migrar, deve limpar a tabela.\n";
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

        $this->conta('shipping_companies', $db_ms);
        $this->conta('shipping_company_address', $db_ms);
        $this->conta('shipping_company_responsible', $db_ms);

        $offset = 0;
        $limit = 500;
        while (true) {
            $results = $db_ms
                ->select('*, sc.id, sc.created_at, sc.updated_at')
                ->join('shipping_company_address sca', 'sc.id = sca.shipping_company_id')
                ->join('shipping_company_responsible scr', 'sc.id = scr.shipping_company_id')
                ->join('shipping_company_to_store scts', 'sc.id = scts.shipping_company_id')
                ->group_by('sc.id')
                ->limit($limit, $offset)
                ->get('shipping_companies sc')
                ->result_array();

            if (empty($results)) {
                break;
            }

            echo "Processamento iniciando em $offset lido ".count($results)."\n";
            $offset += $limit;

            // SHIPPING COMPANIES
            $insert_type_table_shipping = array();
            $insert_shipping_company = array();

            foreach ($results as $result) {
                $insert_type_table_shipping[] = array(
                    'id_provider'   => $result['shipping_company_id'],
                    'id_type'       => $result['type'],
                );

                $store_id = null;
                $company_id = null;

                if ($result['freight_seller']) {
                    $store_id = $result['store_id'];
                    $company_id = $result['company_id'];
                }

                $check_credit_value = empty($result['credit_value']) || $result['credit_value'] == '0.00';
                $check_minimum_shipping = empty($result['minimum_shipping']) || $result['minimum_shipping'] == '0.00';
                $check_minimum_quantity = empty($result['minimum_quantity']) || $result['minimum_quantity'] == '0.00';

                $insert_shipping_company[] = array(
                    'id'                            => $result['shipping_company_id'],
                    'name'                          => $result['name'],
                    'razao_social'                  => $result['corporate_name'],
                    'insc_estadual'                 => $result['state_registration'],
                    'cnpj'                          => $result['cnpj'],
                    'phone'                         => $result['phone'],
                    'address'                       => $result['address_place'],
                    'addr_num'                      => $result['address_number'],
                    'addr_compl'                    => $result['address_complement'],
                    'zipcode'                       => $result['address_zipcode'],
                    'addr_neigh'                    => $result['address_neighborhood'],
                    'addr_city'                     => $result['address_city'],
                    'addr_uf'                       => $result['address_state'],
                    'responsible_name'              => $result['responsible_name'],
                    'responsible_cpf'               => $result['responsible_cpf'],
                    'responsible_email'             => $result['responsible_email'],
                    'bank'                          => $result['bank'],
                    'agency'                        => $result['agency'],
                    'account_type'                  => $result['account_type'],
                    'account'                       => $result['account'],
                    'active'                        => $result['active'],
                    'responsible_oper_name'         => $result['responsible_operational_name'],
                    'responsible_oper_cpf'          => $result['responsible_operational_cpf'],
                    'responsible_oper_email'        => $result['responsible_operational_email'],
                    'responsible_finan_name'        => $result['responsible_financial_name'],
                    'responsible_finan_cpf'         => $result['responsible_financial_cpf'],
                    'responsible_finan_email'       => $result['responsible_financial_email'],
                    'tipo_fornecedor'               => 'Transportadora',
                    'active_token_api'              => 0,
                    'token_api'                     => null,
                    'observacao'                    => $result['observation'],
                    'percentual_loja'               => null,
                    'ativo_percentual_loja'         => null,
                    'regiao_entrega'                => $result['delivery_region'],
                    'regiao_coleta'                 => $result['region_collection'],
                    'tempo_coleta'                  => $result['collection_time'],
                    'fluxo_fin'                     => $result['cash_flow'],
                    'credito'                       => $check_credit_value      ? '' : $result['credit_value'],
                    'val_credito'                   => $check_credit_value      ? 'Nao' : 'Sim',
                    'ship_min'                      => $check_minimum_shipping  ? '' : $result['minimum_shipping'],
                    'val_ship_min'                  => $check_minimum_shipping  ? 'Nao' : 'Sim',
                    'qtd_min'                       => $check_minimum_quantity  ? '' : $result['minimum_quantity'],
                    'val_qtd_min'                   => $check_minimum_quantity  ? 'Nao' : 'Sim',
                    'tipo_pagamento'                => $result['payment_type'],
                    'tipo_produto'                  => $result['product_type'],
                    'tracking_web_site'             => $result['tracking_website'],
                    'slc_tipo_cubage'               => empty($result['cubage_factor']) ? 0 : 1,
                    'cubage_factor'                 => $result['cubage_factor'],
                    'ad_valorem'                    => $result['ad_valorem'],
                    'gris'                          => $result['gris'],
                    'toll'                          => $result['toll'],
                    'shipping_revenue'              => $result['shipping_revenue'],
                    'freight_calculation_standard'  => $result['freight_calculation_standard'],
                    'freight_seller'                => $result['freight_seller'],
                    'deleted'                       => false,
                    'store_id'                      => $store_id,
                    'company_id'                    => $company_id,
                    'date_updated'                  => $result['updated_at'],
                    'date_created'                  => $result['created_at'],
                );
            }

            $this->model_shipping_company->createShippingCompanyBatch($insert_shipping_company);
            $this->model_shipping_company->createTypeTableShippingBatch($insert_type_table_shipping);
        }

        $this->conta('type_table_shipping', $this->db);
        $this->conta('shipping_company', $this->db);
    }

    function conta($table, $dbvar) {
        if ($dbvar->database == 'conectala') {
            echo "[Monolito] ";
        } else {
            echo "[MS] ";
        }

        $ret = $dbvar->query('select count(*) as cnt from '.$table);
        $cnt = $ret->row_array();        
        echo "tabela ".$table." com ".$cnt['cnt']." registros\n";
        return $cnt['cnt'];
    }
}
