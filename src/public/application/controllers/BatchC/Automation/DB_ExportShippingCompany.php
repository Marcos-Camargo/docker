<?php

require 'system/libraries/Vendor/autoload.php';

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

        $cnt_shipping_companies = $this->conta('shipping_companies', $db_ms);
        $cnt_shipping_company_address = $this->conta('shipping_company_address', $db_ms);
        $cnt_shipping_company_responsible = $this->conta('shipping_company_responsible', $db_ms);

        if ($cnt_shipping_companies > 0 || $cnt_shipping_company_address > 0 || $cnt_shipping_company_responsible > 0) {
            echo "Tabelas shipping_companies($cnt_shipping_companies), shipping_company_address($cnt_shipping_company_address) ou shipping_company_responsible($cnt_shipping_company_responsible) já contém registro em Freight Tables. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }
        
        $this->conta('type_table_shipping', $this->db);
        // fim da parte nova

        $table_type = $this->db->select('idtype_table_shipping AS id, id_provider, id_type')
            ->order_by('idtype_table_shipping', 'ASC')
            ->get('type_table_shipping')
            ->result_array();

        $offset = 0;
        while (true) {
            $results = $this->db->select('id, active, name, razao_social, insc_estadual, cnpj, phone, observacao, regiao_entrega, regiao_coleta, tempo_coleta, fluxo_fin, val_credito, ship_min, val_ship_min, qtd_min, val_qtd_min, tipo_pagamento, tipo_produto, tracking_web_site, cubage_factor, ad_valorem, gris, toll, shipping_revenue, freight_calculation_standard, freight_seller, date_created, date_updated, address, addr_num, addr_compl, zipcode, addr_neigh, addr_city, addr_uf, responsible_name, responsible_cpf, responsible_email, bank, agency, account_type, account, responsible_oper_name, responsible_oper_cpf, responsible_oper_email, responsible_finan_name, responsible_finan_cpf, responsible_finan_email')
                ->order_by('id', 'ASC')
                ->limit(50000, $offset)
                ->get('shipping_company')
                ->result_array();

            echo "Processando iniciando em $offset lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            // SHIPPING COMPANIES
            $insert_shipping_company = [];
            $insert_shipping_company_address = [];
            $insert_shipping_company_responsible = [];
            foreach ($results as $result) {
                $id                             = $result["id"];
                $active                         = addslashes($result["active"]);
                $name                           = addslashes($result["name"]);
                $corporate_name                 = addslashes($result["razao_social"]);
                $cnpj                           = addslashes(onlyNumbers($result["cnpj"]));
                $phone                          = addslashes(onlyNumbers($result["phone"]));
                $state_registration             = null;
                $observation                    = null;
                $delivery_region                = null;
                $region_collection              = null;
                $collection_time                = null;
                $cash_flow                      = null;
                $credit_value                   = "0.00";
                $minimum_shipping               = "0.00";
                $minimum_quantity               = 0;
                $payment_type                   = null;
                $product_type                   = null;
                $tracking_website               = null;
                $cubage_factor                  = "0.00";
                $ad_valorem                     = "0.00";
                $gris                           = "0.00";
                $toll                           = "0.00";
                $shipping_revenue               = "0.00";
                $freight_seller                 = 1;
                $type                           = 0;
                $freight_calculation_standard   = $result['freight_calculation_standard'];
                $created_at                     = $result["date_created"];
                $updated_at                     = $result["date_updated"];
                $shipping_company_id            = $result["id"];
                $address_place                  = null;
                $address_number                 = null;
                $address_complement             = null;
                $address_zipcode                = null;
                $address_neighborhood           = null;
                $address_city                   = null;
                $address_state                  = null;
                $responsible_name               = null;
                $responsible_cpf                = null;
                $responsible_email              = null;
                $bank                           = null;
                $agency                         = null;
                $account_type                   = null;
                $account                        = null;
                $responsible_operational_name   = null;
                $responsible_operational_cpf    = null;
                $responsible_operational_email  = null;
                $responsible_financial_name     = null;
                $responsible_financial_cpf      = null;
                $responsible_financial_email    = null;

                if (!empty($result["insc_estadual"])) {
                    $state_registration = addslashes(onlyNumbers($result["insc_estadual"]));
                }
                if (!empty($result["observacao"])) {
                    $observation = addslashes($result["observacao"]);
                }
                if (!empty($result["regiao_entrega"])) {
                    $delivery_region = addslashes($result["regiao_entrega"]);
                }
                if (!empty($result["regiao_coleta"])) {
                    $region_collection = addslashes($result["regiao_coleta"]);
                }
                if (!empty($result["tempo_coleta"])) {
                    $collection_time = addslashes($result["tempo_coleta"]);
                }
                if (!empty($result["fluxo_fin"])) {
                    $cash_flow = addslashes($result["fluxo_fin"]);
                }
                if (!empty($result["val_credito"])) {
                    $credit_value = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["val_credito"])));
                }
                if (!empty($result["ship_min"]) && ($result["ship_min"] == "Sim")) {
                    $minimum_shipping = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["val_ship_min"])));
                }
                if (!empty($result["qtd_min"]) && ($result["qtd_min"] == "Sim")) {
                    $minimum_quantity = addslashes(onlyNumbers($result["val_qtd_min"]));
                }
                if (!empty($result["tipo_pagamento"])) {
                    $payment_type = addslashes($result["tipo_pagamento"]);
                }
                if (!empty($result["tipo_produto"])) {
                    $product_type = addslashes($result["tipo_produto"]);
                }
                if (!empty($result["tracking_web_site"])) {
                    $tracking_website = addslashes($result["tracking_web_site"]);
                }
                if (!empty($result["cubage_factor"])) {
                    $cubage_factor = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["cubage_factor"])));
                }
                if (!empty($result["ad_valorem"])) {
                    $ad_valorem = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["ad_valorem"])));
                }
                if (!empty($result["gris"])) {
                    $gris = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["gris"])));
                }
                if (!empty($result["toll"])) {
                    $toll = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["toll"])));
                }
                if (!empty($result["shipping_revenue"])) {
                    $shipping_revenue = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["shipping_revenue"])));
                }
                if (!empty($result["freight_seller"])) {
                    $freight_seller = addslashes($result["freight_seller"]);
                }

                $search_type = getArrayByValueIn($table_type, $shipping_company_id, 'id_provider');
                if (!empty($search_type)) {
                    $type = addslashes($search_type["id_type"]);
                    if ($type == 2) {
                        $type = 0;
                    }
                }

                $insert_shipping_company[] = [
                    'id'                            => $id,
                    'active'                        => $active,
                    'name'                          => $name,
                    'corporate_name'                => $corporate_name,
                    'state_registration'            => $state_registration,
                    'cnpj'                          => $cnpj,
                    'phone'                         => $phone,
                    'observation'                   => $observation,
                    'delivery_region'               => $delivery_region,
                    'region_collection'             => $region_collection,
                    'collection_time'               => $collection_time,
                    'cash_flow'                     => $cash_flow,
                    'credit_value'                  => $credit_value,
                    'minimum_shipping'              => $minimum_shipping,
                    'minimum_quantity'              => $minimum_quantity,
                    'payment_type'                  => $payment_type,
                    'product_type'                  => $product_type,
                    'tracking_website'              => $tracking_website,
                    'cubage_factor'                 => $cubage_factor,
                    'ad_valorem'                    => $ad_valorem,
                    'gris'                          => $gris,
                    'toll'                          => $toll,
                    'shipping_revenue'              => $shipping_revenue,
                    'freight_seller'                => $freight_seller,
                    'type'                          => $type,
                    'freight_calculation_standard'  => $freight_calculation_standard,
                    'created_at'                    => $created_at,
                    'updated_at'                    => $updated_at
                ];

                if (!empty($result["address"])) {
                    $address_place = addslashes($result["address"]);
                }
                if (!empty($result["addr_num"])) {
                    $address_number = addslashes($result["addr_num"]);
                }
                if (!empty($result["addr_compl"])) {
                    $address_complement = addslashes($result["addr_compl"]);
                }
                if (!empty($result["zipcode"])) {
                    $address_zipcode = addslashes(onlyNumbers($result["zipcode"]));
                }
                if (!empty($result["addr_neigh"])) {
                    $address_neighborhood = addslashes($result["addr_neigh"]);
                }
                if (!empty($result["addr_city"])) {
                    $address_city = addslashes($result["addr_city"]);
                }
                if (!empty($result["addr_uf"])) {
                    $address_state = addslashes($result["addr_uf"]);
                }

                $insert_shipping_company_address[] = [
                    'shipping_company_id'   => $shipping_company_id,
                    'address_place'         => $address_place,
                    'address_number'        => $address_number,
                    'address_complement'    => $address_complement,
                    'address_zipcode'       => $address_zipcode,
                    'address_neighborhood'  => $address_neighborhood,
                    'address_city'          => $address_city,
                    'address_state'         => $address_state,
                    'created_at'            => $created_at,
                    'updated_at'            => $updated_at
                ];

                if (!empty($result["responsible_name"])) {
                    $responsible_name = addslashes($result["responsible_name"]);
                }
                if (!empty($result["responsible_cpf"])) {
                    $responsible_cpf = addslashes(onlyNumbers($result["responsible_cpf"]));
                }
                if (!empty($result["responsible_email"])) {
                    $responsible_email = addslashes($result["responsible_email"]);
                }
                if (!empty($result["bank"])) {
                    $bank = addslashes($result["bank"]);
                }
                if (!empty($result["agency"])) {
                    $agency = addslashes($result["agency"]);
                }
                if (!empty($result["account_type"])) {
                    $account_type = addslashes($result["account_type"]);
                }
                if (!empty($result["account"])) {
                    $account = addslashes($result["account"]);
                }
                if (!empty($result["responsible_oper_name"])) {
                    $responsible_operational_name = addslashes($result["responsible_oper_name"]);
                }
                if (!empty($result["responsible_oper_cpf"])) {
                    $responsible_operational_cpf = addslashes(onlyNumbers($result["responsible_oper_cpf"]));
                }
                if (!empty($result["responsible_oper_email"])) {
                    $responsible_operational_email = addslashes($result["responsible_oper_email"]);
                }
                if (!empty($result["responsible_finan_name"])) {
                    $responsible_financial_name = addslashes($result["responsible_finan_name"]);
                }
                if (!empty($result["responsible_finan_cpf"])) {
                    $responsible_financial_cpf = addslashes(onlyNumbers($result["responsible_finan_cpf"]));
                }
                if (!empty($result["responsible_finan_email"])) {
                    $responsible_financial_email = addslashes($result["responsible_finan_email"]);
                }

                $insert_shipping_company_responsible[] = [
                    'shipping_company_id'           => $shipping_company_id,
                    'responsible_name'              => $responsible_name,
                    'responsible_cpf'               => $responsible_cpf,
                    'responsible_email'             => $responsible_email,
                    'bank'                          => $bank,
                    'agency'                        => $agency,
                    'account_type'                  => $account_type,
                    'account'                       => $account,
                    'responsible_operational_name'  => $responsible_operational_name,
                    'responsible_operational_cpf'   => $responsible_operational_cpf,
                    'responsible_operational_email' => $responsible_operational_email,
                    'responsible_financial_name'    => $responsible_financial_name,
                    'responsible_financial_cpf'     => $responsible_financial_cpf,
                    'responsible_financial_email'   => $responsible_financial_email,
                    'created_at'                    => $created_at,
                    'updated_at'                    => $updated_at
                ];
            }

            if (!empty($insert_shipping_company)) {
                $db_ms->insert_batch('shipping_companies', $insert_shipping_company);
            }

            if (!empty($insert_shipping_company_address)) {
                $db_ms->insert_batch('shipping_company_address', $insert_shipping_company_address);
            }

            if (!empty($insert_shipping_company_responsible)) {
                $db_ms->insert_batch('shipping_company_responsible', $insert_shipping_company_responsible);
            }
        }
        $this->conta('shipping_companies', $db_ms);
        $this->conta('shipping_company_address', $db_ms);
        $this->conta('shipping_company_responsible', $db_ms);
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
